<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_platform\adapter;

use plugin\ads_platform\src\{
    PlatformAdapter, CampaignData, ReportRequest, FieldMapping
};
use RuntimeException;
use InvalidArgumentException;

class Amazon implements PlatformAdapter
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl = 'https://advertising-api.amazon.com/v2/';

    public function __construct()
    {
        $this->clientId     = env('AMAZON_ADS_CLIENT_ID', '');
        $this->clientSecret = env('AMAZON_ADS_CLIENT_SECRET', '');
    }

    public function code(): string { return 'amazon'; }

    public function name(): string { return 'Amazon Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'oauth']; }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'advertising::campaign_management',
            'response_type' => 'code',
        ]);
        return 'https://www.amazon.com/ap/oa?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request('POST', 'auth/o2/token', [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ], null, false);
        return [
            'access_token'   => $resp['access_token'] ?? '',
            'refresh_token'  => $resp['refresh_token'] ?? '',
            'expires_in'     => $resp['expires_in'] ?? 3600,
            'advertiser_ids' => [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->request('POST', 'auth/o2/token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ], null, false);
        return [
            'access_token'  => $resp['access_token'] ?? '',
            'refresh_token' => $resp['refresh_token'] ?? '',
            'expires_in'    => $resp['expires_in'] ?? 3600,
        ];
    }

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'profiles', [], $accessToken);
        $list = $resp ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['profileId'] ?? ''),
            'account_name'           => $item['countryCode'] . ' - ' . ($item['accountInfo']['marketplaceStringId'] ?? ''),
        ], $list);
    }

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $nextToken = null;
        do {
            $query = [];
            if ($nextToken) {
                $query['nextToken'] = $nextToken;
            }
            $resp = $this->request('GET', 'sp/campaigns', $query, $accessToken, true, $accountId);
            $list = $resp['campaigns'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $nextToken = $resp['nextToken'] ?? null;
        } while ($nextToken);
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adGroupFieldMapping();
        $nextToken = null;
        do {
            $query = ['campaignIdFilter' => $campaignId];
            if ($nextToken) {
                $query['nextToken'] = $nextToken;
            }
            $resp = $this->request('GET', 'sp/adGroups', $query, $accessToken, true, $accountId);
            $list = $resp['adGroups'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $nextToken = $resp['nextToken'] ?? null;
        } while ($nextToken);
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $nextToken = null;
        do {
            $query = ['adGroupIdFilter' => $adGroupId];
            if ($nextToken) {
                $query['nextToken'] = $nextToken;
            }
            $resp = $this->request('GET', 'sp/productAds', $query, $accessToken, true, $accountId);
            $list = $resp['productAds'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $nextToken = $resp['nextToken'] ?? null;
        } while ($nextToken);
    }

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();

        // Step 1: Create report
        $createResp = $this->request('POST', 'reporting/reports', [
            'reportDate'   => $req->dateStart,
            'configuration' => [
                'adProduct'         => 'SPONSORED_PRODUCTS',
                'groupBy'           => [$req->granularity === 'daily' ? 'date' : 'campaign'],
                'columns'           => ['impressions', 'clicks', 'cost', 'purchases', 'sales'],
                'reportTypeId'      => $req->granularity === 'daily' ? 'spCampaignsByDate' : 'spCampaigns',
                'timeUnit'          => 'DAILY',
            ],
        ], $accessToken, true, $accountId);

        $reportId = $createResp['reportId'] ?? '';

        // Step 2: Poll until complete
        $status = 'IN_PROGRESS';
        $maxRetries = 30;
        while ($status === 'IN_PROGRESS' && $maxRetries > 0) {
            sleep(2);
            $statusResp = $this->request('GET', 'reporting/reports/' . $reportId, [], $accessToken, true, $accountId);
            $status = $statusResp['status'] ?? 'FAILURE';
            $maxRetries--;
        }

        if ($status !== 'COMPLETED') {
            throw new RuntimeException('Amazon report generation failed with status: ' . $status);
        }

        // Step 3: Download
        $url = $statusResp['url'] ?? '';
        if ($url) {
            $gzipContent = file_get_contents($url);
            if ($gzipContent === false) {
                return;
            }
            $csvContent = gzdecode($gzipContent);
            if ($csvContent === false) {
                return;
            }
            $lines = explode("\n", trim($csvContent));
            $headers = str_getcsv(array_shift($lines));
            foreach ($lines as $line) {
                $row = array_combine($headers, str_getcsv($line));
                if ($row) {
                    yield $mapping->map($row);
                }
            }
        }
    }

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $payload = [
            'name'       => $data->name,
            'targetingType' => 'manual',
            'state'      => 'ENABLED',
            'dynamicBidding' => [
                'strategy' => 'LEGACY_FOR_SALES',
            ],
            'startDate'  => $data->startDate ?? date('Ymd'),
            'budget'     => [
                'budgetType' => 'daily',
                'budget'     => $data->dailyBudget / 100,
            ],
        ];
        if ($data->endDate) {
            $payload['endDate'] = $data->endDate;
        }
        $resp = $this->request('POST', 'sp/campaigns', $payload, $accessToken, true, $accountId);
        return (string) ($resp['campaignId'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $payload = ['name' => $data->name];
        if ($data->dailyBudget > 0) {
            $payload['budget'] = [
                'budgetType' => 'daily',
                'budget'     => $data->dailyBudget / 100,
            ];
        }
        $this->request('PUT', 'sp/campaigns/' . $platformId, $payload, $accessToken, true, $accountId);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('PUT', 'sp/campaigns/' . $platformId, [
            'state' => $enabled ? 'ENABLED' : 'PAUSED',
        ], $accessToken, true, $accountId);
    }

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaignId' => 'platform_campaign_id',
            'name'       => 'name',
            'budget'     => 'daily_budget',
            'state'      => 'status',
            'startDate'  => 'start_date',
            'endDate'    => 'end_date',
        ], [
            'ENABLED'  => 'enabled',
            'PAUSED'   => 'paused',
            'ARCHIVED' => 'deleted',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget'] * 100);
            }
            return $unified;
        });
    }

    protected function adGroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'adGroupId'   => 'platform_ad_group_id',
            'name'        => 'name',
            'campaignId'  => 'platform_campaign_id',
            'state'       => 'status',
            'defaultBid'  => 'bid',
        ], [
            'ENABLED'  => 'enabled',
            'PAUSED'   => 'paused',
            'ARCHIVED' => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'adId'       => 'platform_creative_id',
            'campaignId' => 'platform_campaign_id',
            'adGroupId'  => 'platform_ad_group_id',
            'state'      => 'status',
        ], [
            'ENABLED'  => 'enabled',
            'PAUSED'   => 'paused',
            'ARCHIVED' => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaignId'  => 'platform_campaign_id',
            'campaign'    => 'campaign_name',
            'cost'        => 'cost',
            'impressions' => 'impressions',
            'clicks'      => 'clicks',
            'purchases'   => 'conversions',
            'sales'       => 'revenue',
            'CTR'         => 'ctr',
            'CPC'         => 'cpc',
        ], [], function (array $unified): array {
            // Amazon returns cost as float dollars, convert to cents
            if (isset($unified['cost'])) {
                $unified['cost'] = (int) round((float) $unified['cost'] * 100);
            }
            return $unified;
        });
    }

    protected function request(
        string  $method,
        string  $path,
        array   $params = [],
        ?string $accessToken = null,
        bool    $isApi = true,
        ?string $profileId = null,
    ): array {
        $url = $isApi ? $this->baseUrl . $path : 'https://api.amazon.com/' . $path;
        $headers = ['Content-Type: application/json'];

        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }
        if ($isApi && $accessToken) {
            $headers[] = 'Amazon-Advertising-API-ClientId: ' . $this->clientId;
        }
        if ($profileId) {
            $headers[] = 'Amazon-Advertising-API-Scope: ' . $profileId;
        }

        $ch = curl_init();
        if ($method === 'GET') {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false || curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Amazon Ads API network error: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode >= 400 || !is_array($decoded)) {
            throw new RuntimeException(
                'Amazon Ads API error: HTTP ' . $httpCode . ' - ' . ($decoded['error_description'] ?? $body)
            );
        }
        return $decoded;
    }
}
