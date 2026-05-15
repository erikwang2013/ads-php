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

class Netflix implements PlatformAdapter
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $tokenUrl = 'https://api.netflix.com/oauth2/token';
    protected string $baseUrl = 'https://api.netflix.com/ads/v1/';

    public function __construct()
    {
        $this->clientId     = env('NETFLIX_ADS_CLIENT_ID', '');
        $this->clientSecret = env('NETFLIX_ADS_CLIENT_SECRET', '');
    }

    public function code(): string { return 'netflix'; }

    public function name(): string { return 'Netflix Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'oauth']; }

    // Netflix uses client_credentials grant (machine-to-machine).
    // Since there is no user-facing OAuth flow, buildAuthUrl returns a placeholder
    // that instructs the user to configure client credentials in the Netflix Ads portal.
    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        // Netflix Ads uses client_credentials only — no browser-based auth.
        // Return a placeholder URL that triggers the exchangeToken flow directly.
        return 'netflix-ads://client-credentials?state=' . urlencode($state);
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        // Netflix Ads uses client_credentials grant (M2M).
        // The "code" parameter is part of the interface but unused here.
        $resp = $this->requestToken([
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => 'ads:read ads:write',
        ]);
        return [
            'access_token'   => $resp['access_token'] ?? '',
            'refresh_token'  => '',
            'expires_in'     => $resp['expires_in'] ?? 3600,
            'advertiser_ids' => [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        // client_credentials does not use refresh tokens — re-authenticate.
        return $this->exchangeToken('', '');
    }

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'advertisers', [], $accessToken);
        $list = $resp['data'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['advertiserId'] ?? ''),
            'account_name'           => $item['advertiserName'] ?? '',
        ], $list);
    }

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'campaigns', [
                'advertiserId' => $accountId,
                'page'         => $page,
                'size'         => 50,
            ], $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = count($list) >= 50;
            $page++;
        } while ($hasMore);
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        // Netflix Ads has a simplified structure — campaigns directly contain placements,
        // similar to ad groups but accessed inline with campaigns.
        $mapping = $this->adGroupFieldMapping();
        $resp = $this->request('GET', 'campaigns/' . $campaignId . '/adGroups', [
            'advertiserId' => $accountId,
        ], $accessToken);
        $list = $resp['data'] ?? [];
        foreach ($list as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $resp = $this->request('GET', 'adGroups/' . $adGroupId . '/creatives', [
            'advertiserId' => $accountId,
        ], $accessToken);
        $list = $resp['data'] ?? [];
        foreach ($list as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'reports', [
                'advertiserId' => $accountId,
                'startDate'    => $req->dateStart,
                'endDate'      => $req->dateEnd,
                'granularity'  => $req->granularity,
                'metrics'      => implode(',', $req->metrics ?: ['impressions', 'clicks', 'spend', 'conversions']),
                'page'         => $page,
                'size'         => min($req->pageSize, 100),
            ], $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = count($list) >= min($req->pageSize, 100);
            $page++;
        } while ($hasMore);
    }

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        // Note: Netflix Ads is the newest CTV entrant — API surface is limited
        // and minimum spend requirements are high (~$10M).
        $resp = $this->request('POST', 'campaigns', [
            'advertiserId' => $accountId,
            'name'         => $data->name,
            'budget'       => [
                'amount'   => $data->dailyBudget,
                'type'     => 'daily',
            ],
            'startDate'    => $data->startDate ?? date('Y-m-d'),
            'status'       => 'ACTIVE',
        ], $accessToken);
        return (string) ($resp['campaignId'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $payload = [
            'advertiserId' => $accountId,
            'name'         => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $payload['budget'] = [
                'amount' => $data->dailyBudget,
                'type'   => 'daily',
            ];
        }
        $this->request('PUT', 'campaigns/' . $platformId, $payload, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('PUT', 'campaigns/' . $platformId, [
            'advertiserId' => $accountId,
            'status'       => $enabled ? 'ACTIVE' : 'PAUSED',
        ], $accessToken);
    }

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaignId' => 'platform_campaign_id',
            'name'       => 'name',
            'budget'     => 'daily_budget',
            'status'     => 'status',
            'startDate'  => 'start_date',
            'endDate'    => 'end_date',
        ], [
            'ACTIVE' => 'enabled',
            'PAUSED' => 'paused',
            'ENDED'  => 'deleted',
        ], function (array $unified): array {
            // Netflix budget is an object with amount (cents), extract
            if (isset($unified['daily_budget']) && is_array($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget']['amount'] ?? 0);
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
            'status'      => 'status',
            'targeting'   => 'targeting',
        ], [
            'ACTIVE' => 'enabled',
            'PAUSED' => 'paused',
            'ENDED'  => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'creativeId'  => 'platform_creative_id',
            'name'        => 'title',
            'adGroupId'   => 'platform_ad_group_id',
            'status'      => 'status',
            'duration'    => 'duration',
        ], [
            'ACTIVE' => 'enabled',
            'PAUSED' => 'paused',
            'ENDED'  => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaignId'   => 'platform_campaign_id',
            'campaignName' => 'campaign_name',
            'spend'        => 'cost',
            'impressions'  => 'impressions',
            'clicks'       => 'clicks',
            'conversions'  => 'conversions',
            'ctr'          => 'ctr',
            'cpm'          => 'cpm',
            'cpc'          => 'cpc',
            'date'         => 'date',
            'completedViews' => 'video_views',
        ], [], function (array $unified): array {
            // Netflix returns spend in cents — no conversion
            if (isset($unified['cost'])) {
                $unified['cost'] = (int) round((float) $unified['cost']);
            }
            return $unified;
        });
    }

    protected function requestToken(array $params): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->tokenUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false || curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Netflix Ads OAuth network error: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode >= 400 || !is_array($decoded)) {
            throw new RuntimeException(
                'Netflix Ads OAuth error: HTTP ' . $httpCode . ' - ' . ($decoded['error_description'] ?? $body)
            );
        }
        return $decoded;
    }

    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url = $this->baseUrl . $path;
        $headers = ['Content-Type: application/json'];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
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
            throw new RuntimeException('Netflix Ads API network error: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode >= 400 || !is_array($decoded)) {
            throw new RuntimeException(
                'Netflix Ads API error: HTTP ' . $httpCode . ' - ' . ($decoded['error'] ?? $body)
            );
        }
        return $decoded;
    }
}
