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

class Spotify implements PlatformAdapter
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl = 'https://api.spotify.com/v1/ads/';

    public function __construct()
    {
        $this->clientId     = env('SPOTIFY_ADS_CLIENT_ID', '');
        $this->clientSecret = env('SPOTIFY_ADS_CLIENT_SECRET', '');
    }

    public function code(): string { return 'spotify'; }

    public function name(): string { return 'Spotify Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'oauth']; }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'advertising:read advertising:write',
            'response_type' => 'code',
        ]);
        return 'https://accounts.spotify.com/authorize?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request('POST', 'https://accounts.spotify.com/api/token', [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
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
        $resp = $this->request('POST', 'https://accounts.spotify.com/api/token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ], null, false);
        return [
            'access_token'  => $resp['access_token'] ?? '',
            'refresh_token' => $resp['refresh_token'] ?? '',
            'expires_in'    => $resp['expires_in'] ?? 3600,
        ];
    }

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'accounts', [], $accessToken);
        $list = $resp['items'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['id'] ?? ''),
            'account_name'           => $item['name'] ?? '',
        ], $list);
    }

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $offset = 0;
        do {
            $resp = $this->request('GET', 'campaigns', [
                'account_id' => $accountId,
                'offset'     => $offset,
                'limit'      => 50,
            ], $accessToken);
            $list = $resp['items'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list) && count($list) >= 50;
            $offset += 50;
        } while ($hasMore);
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adGroupFieldMapping();
        $offset = 0;
        do {
            $resp = $this->request('GET', 'ad-sets', [
                'account_id'  => $accountId,
                'campaign_id' => $campaignId,
                'offset'      => $offset,
                'limit'       => 50,
            ], $accessToken);
            $list = $resp['items'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list) && count($list) >= 50;
            $offset += 50;
        } while ($hasMore);
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $offset = 0;
        do {
            $resp = $this->request('GET', 'creatives', [
                'account_id' => $accountId,
                'ad_set_id'  => $adGroupId,
                'offset'     => $offset,
                'limit'      => 50,
            ], $accessToken);
            $list = $resp['items'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list) && count($list) >= 50;
            $offset += 50;
        } while ($hasMore);
    }

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();

        // Step 1: Create async report
        $createResp = $this->request('POST', 'reports', [
            'account_id'  => $accountId,
            'start_date'  => $req->dateStart,
            'end_date'    => $req->dateEnd,
            'granularity' => $req->granularity, // daily, weekly, monthly
            'metrics'     => $req->metrics ?: ['impressions', 'clicks', 'spend', 'conversions'],
        ], $accessToken);

        $reportId = $createResp['id'] ?? '';

        // Step 2: Poll until ready
        $status = 'PROCESSING';
        $maxRetries = 30;
        while ($status === 'PROCESSING' && $maxRetries > 0) {
            sleep(2);
            $statusResp = $this->request('GET', 'reports/' . $reportId, [
                'account_id' => $accountId,
            ], $accessToken);
            $status = $statusResp['status'] ?? 'FAILED';
            $maxRetries--;
        }

        if ($status !== 'COMPLETED') {
            throw new RuntimeException('Spotify Ads report generation failed with status: ' . $status);
        }

        // Step 3: Download / stream results
        $results = $statusResp['results'] ?? [];
        foreach ($results as $row) {
            yield $mapping->map($row);
        }
    }

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->request('POST', 'campaigns', [
            'account_id' => $accountId,
            'name'       => $data->name,
            'budget'     => [
                'amount'       => $data->dailyBudget,
                'currency'     => 'USD',
                'period'       => 'daily',
            ],
            'start_date'  => $data->startDate ?? date('Y-m-d'),
            'status'      => 'ACTIVE',
        ], $accessToken);
        return (string) ($resp['id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $payload = [
            'account_id'  => $accountId,
            'id'          => $platformId,
            'name'        => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $payload['budget'] = [
                'amount'   => $data->dailyBudget,
                'currency' => 'USD',
                'period'   => 'daily',
            ];
        }
        $this->request('PUT', 'campaigns/' . $platformId, $payload, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('PUT', 'campaigns/' . $platformId, [
            'account_id' => $accountId,
            'id'         => $platformId,
            'status'     => $enabled ? 'ACTIVE' : 'PAUSED',
        ], $accessToken);
    }

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'         => 'platform_campaign_id',
            'name'       => 'name',
            'budget'     => 'daily_budget',
            'status'     => 'status',
            'start_date' => 'start_date',
            'end_date'   => 'end_date',
        ], [
            'ACTIVE'  => 'enabled',
            'PAUSED'  => 'paused',
            'STOPPED' => 'deleted',
        ], function (array $unified): array {
            // Spotify budget is an object with amount in cents
            if (isset($unified['daily_budget']) && is_array($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget']['amount'] ?? 0);
            }
            return $unified;
        });
    }

    protected function adGroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'          => 'platform_ad_group_id',
            'name'        => 'name',
            'campaign_id' => 'platform_campaign_id',
            'status'      => 'status',
            'bid_amount'  => 'bid',
        ], [
            'ACTIVE'  => 'enabled',
            'PAUSED'  => 'paused',
            'STOPPED' => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'         => 'platform_creative_id',
            'name'       => 'title',
            'ad_set_id'  => 'platform_ad_group_id',
            'status'     => 'status',
            'format'     => 'creative_type',
        ], [
            'ACTIVE'  => 'enabled',
            'PAUSED'  => 'paused',
            'STOPPED' => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'spend'         => 'cost',
            'impressions'   => 'impressions',
            'clicks'        => 'clicks',
            'conversions'   => 'conversions',
            'ctr'           => 'ctr',
            'cpm'           => 'cpm',
            'cpc'           => 'cpc',
            'completion_rate' => 'completion_rate',
        ], [], function (array $unified): array {
            // Spotify returns spend in micro-cents (1/1000000 of currency unit),
            // convert to cents (divide by 10000)
            if (isset($unified['cost']) && is_numeric($unified['cost'])) {
                $unified['cost'] = (int) round((float) $unified['cost'] / 10000);
            }
            return $unified;
        });
    }

    protected function request(string $method, string $url, array $params = [], ?string $accessToken = null, bool $isAdsApi = true): array
    {
        // Allow full URL override for auth endpoints
        if ($isAdsApi && !str_starts_with($url, 'https://')) {
            $url = $this->baseUrl . $url;
        }

        $headers = ['Content-Type: application/json'];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $ch = curl_init();
        if ($method === 'GET') {
            if (!empty($params)) {
                $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
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
            throw new RuntimeException('Spotify Ads API network error: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode >= 400 || !is_array($decoded)) {
            throw new RuntimeException(
                'Spotify Ads API error: HTTP ' . $httpCode . ' - ' . ($decoded['error']['message'] ?? $body)
            );
        }
        return $decoded;
    }
}
