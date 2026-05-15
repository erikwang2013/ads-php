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

class TheTradeDesk implements PlatformAdapter
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $baseUrl = 'https://api.thetradedesk.com/v3/';

    public function __construct()
    {
        $this->apiKey    = env('TTD_API_KEY', '');
        $this->apiSecret = env('TTD_API_SECRET', '');
    }

    public function code(): string { return 'thetradedesk'; }

    public function name(): string { return 'The Trade Desk'; }

    public function capabilities(): array { return ['report', 'campaign', 'oauth']; }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id'     => $this->apiKey,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'response_type' => 'code',
        ]);
        return 'https://api.thetradedesk.com/v3/authorize?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request('POST', 'authentication/token', [
            'code'         => $code,
            'redirect_uri' => $redirectUri,
            'grant_type'   => 'authorization_code',
        ]);
        return [
            'access_token'   => $resp['access_token'] ?? '',
            'refresh_token'  => $resp['refresh_token'] ?? '',
            'expires_in'     => $resp['expires_in'] ?? 3600,
            'advertiser_ids' => [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->request('POST', 'authentication/token', [
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);
        return [
            'access_token'  => $resp['access_token'] ?? '',
            'refresh_token' => $resp['refresh_token'] ?? '',
            'expires_in'    => $resp['expires_in'] ?? 3600,
        ];
    }

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'advertiser', [], $accessToken);
        $list = $resp['data'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['AdvertiserId'] ?? ''),
            'account_name'           => $item['AdvertiserName'] ?? '',
        ], $list);
    }

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'campaign', [
                'advertiserId' => $accountId,
                'pageIndex'    => $page,
                'pageSize'     => 100,
            ], $accessToken);
            $list = $resp['Result'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = count($list) >= 100;
            $page++;
        } while ($hasMore);
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adGroupFieldMapping();
        $resp = $this->request('GET', 'adgroup', [
            'advertiserId' => $accountId,
            'campaignId'   => $campaignId,
        ], $accessToken);
        $list = $resp['Result'] ?? [];
        foreach ($list as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        // TTD is a DSP — creatives are managed by external ad servers,
        // so this method yields creative references from the ad group
        $mapping = $this->creativeFieldMapping();
        $resp = $this->request('GET', 'creative', [
            'advertiserId' => $accountId,
            'adGroupId'    => $adGroupId,
        ], $accessToken);
        $list = $resp['Result'] ?? [];
        foreach ($list as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();

        // Step 1: Create async report
        $createResp = $this->request('POST', 'report', [
            'advertiserId'  => $accountId,
            'startDate'     => $req->dateStart,
            'endDate'       => $req->dateEnd,
            'dimensions'    => $req->dimensions ?: ['Date'],
            'metrics'       => $req->metrics ?: ['Impressions', 'Clicks', 'AdvertiserCost', 'Conversions'],
            'reportType'    => 'PERFORMANCE',
        ], $accessToken);

        $reportRequestId = $createResp['ReportRequestId'] ?? '';

        // Step 2: Poll until ready
        $status = 'Queued';
        $maxRetries = 30;
        while (in_array($status, ['Queued', 'Processing']) && $maxRetries > 0) {
            sleep(3);
            $statusResp = $this->request('GET', 'report/status', [
                'ReportRequestId' => $reportRequestId,
            ], $accessToken);
            $status = $statusResp['Status'] ?? 'Error';
            $maxRetries--;
        }

        if ($status !== 'Complete') {
            throw new RuntimeException('TTD report generation failed with status: ' . $status);
        }

        // Step 3: Download report data
        $reportUrl = $statusResp['ReportUrl'] ?? '';
        if ($reportUrl) {
            $csvContent = file_get_contents($reportUrl);
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
        $resp = $this->request('POST', 'campaign', [
            'advertiserId'  => $accountId,
            'CampaignName'  => $data->name,
            'Budget'        => [
                'Amount'   => $data->dailyBudget,  // already in cents
                'Interval' => 'Daily',
            ],
        ], $accessToken);
        return (string) ($resp['CampaignId'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $payload = [
            'advertiserId' => $accountId,
            'CampaignId'   => $platformId,
            'CampaignName' => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $payload['Budget'] = [
                'Amount'   => $data->dailyBudget,
                'Interval' => 'Daily',
            ];
        }
        $this->request('PUT', 'campaign', $payload, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('PUT', 'campaign', [
            'advertiserId' => $accountId,
            'CampaignId'   => $platformId,
            'Status'       => $enabled ? 'ACTIVE' : 'PAUSED',
        ], $accessToken);
    }

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'CampaignId'   => 'platform_campaign_id',
            'CampaignName' => 'name',
            'Budget'       => 'daily_budget',
            'Status'       => 'status',
            'StartDate'    => 'start_date',
            'EndDate'      => 'end_date',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'    => 'paused',
            'ARCHIVED' => 'deleted',
        ], function (array $unified): array {
            // TTD Budget is an object with Amount (cents), extract the value
            if (isset($unified['daily_budget']) && is_array($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget']['Amount'] ?? 0);
            }
            return $unified;
        });
    }

    protected function adGroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'AdGroupId'   => 'platform_ad_group_id',
            'AdGroupName' => 'name',
            'CampaignId'  => 'platform_campaign_id',
            'Status'      => 'status',
            'Bid'         => 'bid',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'    => 'paused',
            'ARCHIVED' => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'CreativeId'   => 'platform_creative_id',
            'CreativeName' => 'name',
            'AdGroupId'    => 'platform_ad_group_id',
            'Status'       => 'status',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'    => 'paused',
            'ARCHIVED' => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'CampaignId'     => 'platform_campaign_id',
            'CampaignName'   => 'campaign_name',
            'AdvertiserCost' => 'cost',
            'Impressions'    => 'impressions',
            'Clicks'         => 'clicks',
            'Conversions'    => 'conversions',
            'CTR'            => 'ctr',
            'CPM'            => 'cpm',
            'CPC'            => 'cpc',
        ], [], function (array $unified): array {
            // TTD returns cost in cents — no conversion needed
            if (isset($unified['cost'])) {
                $unified['cost'] = (int) $unified['cost'];
            }
            return $unified;
        });
    }

    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url = $this->baseUrl . $path;
        $timestamp = (string) time();
        $bodyStr = '';
        $headers = ['Content-Type: application/json'];

        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }
        $headers[] = 'TTD-ApiKey: ' . $this->apiKey;
        $headers[] = 'TTD-Timestamp: ' . $timestamp;

        if ($method === 'GET') {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        } else {
            $bodyStr = json_encode($params, JSON_UNESCAPED_UNICODE);
        }

        // HMAC-SHA256 signature: timestamp + method + path + body
        $signingString = $timestamp . $method . '/' . $path . $bodyStr;
        $signature = hash_hmac('sha256', $signingString, $this->apiSecret);
        $headers[] = 'TTD-Signature: ' . $signature;

        $ch = curl_init();
        if ($method === 'GET') {
            // no extra settings
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyStr);
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
            throw new RuntimeException('The Trade Desk API network error: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode >= 400 || !is_array($decoded)) {
            throw new RuntimeException(
                'The Trade Desk API error: HTTP ' . $httpCode . ' - ' . ($decoded['Message'] ?? $body)
            );
        }
        return $decoded;
    }
}
