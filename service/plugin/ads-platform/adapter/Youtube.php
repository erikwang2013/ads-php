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

class Youtube implements PlatformAdapter
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $developerToken;
    protected string $loginCustomerId;
    protected string $baseUrl   = 'https://googleads.googleapis.com/v17/';
    protected string $authBase  = 'https://accounts.google.com/o/oauth2/';
    protected string $tokenUrl  = 'https://oauth2.googleapis.com/token';

    public function __construct()
    {
        $this->clientId         = env('YOUTUBE_CLIENT_ID', '');
        $this->clientSecret     = env('YOUTUBE_CLIENT_SECRET', '');
        $this->developerToken   = env('YOUTUBE_DEVELOPER_TOKEN', '');
        $this->loginCustomerId  = env('YOUTUBE_LOGIN_CUSTOMER_ID', '');
    }

    // -------------------------------------------------------------------
    //  Identity
    // -------------------------------------------------------------------

    public function code(): string { return 'youtube'; }

    public function name(): string { return 'YouTube Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    // -------------------------------------------------------------------
    //  OAuth2 — standard Google OAuth2 (same flow as Google Ads adapter)
    // -------------------------------------------------------------------

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/adwords',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
        return $this->authBase . 'auth?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->oauthRequest([
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        return [
            'access_token'   => $resp['access_token'] ?? '',
            'refresh_token'  => $resp['refresh_token'] ?? '',
            'expires_in'     => (int) ($resp['expires_in'] ?? 3600),
            'advertiser_ids' => [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->oauthRequest([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        return [
            'access_token'  => $resp['access_token'] ?? '',
            'refresh_token' => $resp['refresh_token'] ?? $refreshToken,
            'expires_in'    => (int) ($resp['expires_in'] ?? 3600),
        ];
    }

    // -------------------------------------------------------------------
    //  Account
    // -------------------------------------------------------------------

    public function fetchAccountInfo(string $accessToken): array
    {
        $url = $this->baseUrl . 'customers:listAccessibleCustomers';

        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'developer-token: ' . $this->developerToken,
        ];
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("YouTube Ads API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true) ?: [];
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $msg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('YouTube Ads API error: ' . $msg);
        }

        $resourceNames = $decoded['resourceNames'] ?? [];
        $accounts = [];

        // listAccessibleCustomers returns resource names; try to fetch
        // descriptive names via googleAds:search when a login customer is set
        if ($this->loginCustomerId && !empty($resourceNames)) {
            try {
                $query = 'SELECT customer_client.id, customer_client.descriptive_name '
                       . 'FROM customer_client '
                       . 'WHERE customer_client.status = "ENABLED"';
                $resp = $this->searchRequest($accessToken, $this->loginCustomerId, $query);
                $childMap = [];
                foreach (($resp['results'] ?? []) as $row) {
                    $cc = $row['customerClient'] ?? [];
                    $cid = (string) ($cc['id'] ?? '');
                    if ($cid !== '') {
                        $childMap[$cid] = $cc['descriptiveName'] ?? $cid;
                    }
                }
                foreach ($resourceNames as $rn) {
                    $cId = str_replace('customers/', '', $rn);
                    $accounts[] = [
                        'account_id_on_platform' => $cId,
                        'account_name'           => $childMap[$cId] ?? $cId,
                    ];
                }
                return $accounts;
            } catch (\RuntimeException $e) {
                // fall through to IDs-only list
            }
        }

        foreach ($resourceNames as $rn) {
            $cId = str_replace('customers/', '', $rn);
            $accounts[] = [
                'account_id_on_platform' => $cId,
                'account_name'           => $cId,
            ];
        }
        return $accounts;
    }

    // -------------------------------------------------------------------
    //  Campaigns — GAQL search filtered by advertisingChannelType = VIDEO
    // -------------------------------------------------------------------

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $query = 'SELECT campaign.id, campaign.name, campaign.status '
               . 'FROM campaign '
               . 'WHERE campaign.advertising_channel_type = "VIDEO"';

        $resp = $this->searchRequest($accessToken, $accountId, $query);
        foreach (($resp['results'] ?? []) as $row) {
            yield $mapping->map($this->flattenGaqlRow($row));
        }
    }

    // -------------------------------------------------------------------
    //  AdGroups
    // -------------------------------------------------------------------

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adgroupFieldMapping();
        $query = 'SELECT ad_group.id, ad_group.name, ad_group.status, ad_group.campaign '
               . 'FROM ad_group '
               . 'WHERE campaign.id = "' . $campaignId . '"';

        $resp = $this->searchRequest($accessToken, $accountId, $query);
        foreach (($resp['results'] ?? []) as $row) {
            yield $mapping->map($this->flattenGaqlRow($row));
        }
    }

    // -------------------------------------------------------------------
    //  Creatives
    // -------------------------------------------------------------------

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $query = 'SELECT ad_group_ad.ad.id, ad_group_ad.ad.name, ad_group_ad.status, ad_group_ad.ad_group '
               . 'FROM ad_group_ad '
               . 'WHERE ad_group.id = "' . $adGroupId . '"';

        $resp = $this->searchRequest($accessToken, $accountId, $query);
        foreach (($resp['results'] ?? []) as $row) {
            yield $mapping->map($this->flattenGaqlRow($row));
        }
    }

    // -------------------------------------------------------------------
    //  Reports — googleAds:search with video-specific metrics
    // -------------------------------------------------------------------

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $query = 'SELECT '
               . 'campaign.id, campaign.name, campaign.status, '
               . 'metrics.impressions, metrics.clicks, metrics.cost_micros, '
               . 'metrics.ctr, metrics.average_cpm, metrics.average_cpc, '
               . 'metrics.conversions, metrics.video_views, metrics.video_view_rate, '
               . 'metrics.cost_per_view, metrics.video_quartile_100_rate '
               . 'FROM campaign '
               . 'WHERE campaign.advertising_channel_type = "VIDEO" '
               . 'AND segments.date BETWEEN "' . $req->dateStart . '" AND "' . $req->dateEnd . '"';

        if (!empty($req->dimensions)) {
            foreach ($req->dimensions as $dim) {
                if ($dim === 'campaign') {
                    // already implied by SELECT campaign.*
                }
            }
        }

        $resp = $this->searchRequest($accessToken, $accountId, $query);
        foreach (($resp['results'] ?? []) as $row) {
            yield $mapping->map($this->flattenGaqlRow($row));
        }
    }

    // -------------------------------------------------------------------
    //  Delivery operations — googleAds:mutate
    // -------------------------------------------------------------------

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $body = [
            'operations' => [[
                'create' => [
                    'name'                   => $data->name,
                    'status'                 => 'PAUSED',
                    'advertisingChannelType' => 'VIDEO',
                ],
            ]],
        ];

        if ($data->dailyBudget > 0) {
            // fen × 10000 = micros
            $body['operations'][0]['create']['campaignBudget'] = [
                'amountMicros' => (string) ($data->dailyBudget * 10000),
            ];
        }

        $resp = $this->mutateRequest($accessToken, $accountId, 'campaigns', $body);
        $resourceName = $resp['results'][0]['resourceName'] ?? '';
        return (string) $resourceName;
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $updateMask = ['name'];
        $update = [
            'resourceName' => $platformId,
            'name'         => $data->name,
        ];

        if ($data->dailyBudget > 0) {
            $updateMask[] = 'campaign_budget';
            // fen × 10000 = micros
            $update['campaignBudget'] = [
                'amountMicros' => (string) ($data->dailyBudget * 10000),
            ];
        }

        $body = [
            'operations' => [[
                'update'     => $update,
                'updateMask' => implode(',', $updateMask),
            ]],
        ];
        $this->mutateRequest($accessToken, $accountId, 'campaigns', $body);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $body = [
            'operations' => [[
                'update'     => [
                    'resourceName' => $platformId,
                    'status'       => $enabled ? 'ENABLED' : 'PAUSED',
                ],
                'updateMask' => 'status',
            ]],
        ];
        $this->mutateRequest($accessToken, $accountId, 'campaigns', $body);
    }

    // -------------------------------------------------------------------
    //  Field mappings
    // -------------------------------------------------------------------

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign.id'   => 'platform_campaign_id',
            'campaign.name' => 'name',
            'campaign.status' => 'status',
        ], [
            'ENABLED'  => 'enabled',
            'PAUSED'   => 'paused',
            'REMOVED'  => 'deleted',
        ]);
    }

    protected function adgroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'ad_group.id'       => 'platform_ad_group_id',
            'ad_group.name'     => 'name',
            'ad_group.campaign' => 'platform_campaign_id',
            'ad_group.status'   => 'status',
        ], [
            'ENABLED'  => 'enabled',
            'PAUSED'   => 'paused',
            'REMOVED'  => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'ad_group_ad.ad.id'    => 'platform_creative_id',
            'ad_group_ad.ad.name'  => 'title',
            'ad_group_ad.ad_group' => 'platform_ad_group_id',
            'ad_group_ad.status'   => 'status',
        ], [
            'ENABLED'  => 'enabled',
            'PAUSED'   => 'paused',
            'REMOVED'  => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign.id'                     => 'platform_campaign_id',
            'campaign.name'                   => 'campaign_name',
            'campaign.status'                 => 'status',
            'metrics.cost_micros'             => 'cost',
            'metrics.impressions'             => 'impressions',
            'metrics.clicks'                  => 'clicks',
            'metrics.conversions'             => 'conversions',
            'metrics.ctr'                     => 'ctr',
            'metrics.average_cpm'             => 'cpm',
            'metrics.average_cpc'             => 'cpc',
        ], [], function (array $unified): array {
            // Google Ads returns money in micros; divide by 10000 for fen
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) round((float) $unified[$field] / 10000);
                }
            }
            // CTR/CVR are already decimal (e.g. 0.05 = 5%); round for precision
            foreach (['ctr', 'cvr'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = round((float) $unified[$field], 6);
                }
            }
            // Surface video-specific metrics in extra
            foreach (['video_views', 'video_view_rate', 'cost_per_view', 'video_played_to_100'] as $key) {
                $gaqlKey = 'metrics.' . $key;
                if (isset($unified['extra'][$gaqlKey])) {
                    $unified['extra'][$key] = $unified['extra'][$gaqlKey];
                    unset($unified['extra'][$gaqlKey]);
                }
            }
            return $unified;
        });
    }

    // -------------------------------------------------------------------
    //  GAQL helpers
    // -------------------------------------------------------------------

    /**
     * Flatten a GAQL result row's nested objects into dot-notation keys.
     *
     * e.g. {campaign: {id: "123"}, metrics: {impressions: "456"}}
     *   => {"campaign.id": "123", "metrics.impressions": "456"}
     */
    protected function flattenGaqlRow(array $row): array
    {
        $flat = [];
        foreach ($row as $category => $obj) {
            if (is_array($obj)) {
                foreach ($obj as $key => $value) {
                    // GAQL returns all scalars as strings; preserve type where useful
                    $flat[$category . '.' . $key] = $value;
                }
            }
        }
        return $flat;
    }

    // -------------------------------------------------------------------
    //  HTTP layer
    // -------------------------------------------------------------------

    /**
     * OAuth token request — form-encoded body, no extra headers.
     */
    protected function oauthRequest(array $params): array
    {
        $ch = curl_init($this->tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("YouTube Ads OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true) ?: [];
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $msg = $decoded['error_description'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('YouTube Ads OAuth error: ' . $msg);
        }
        return $decoded;
    }

    /**
     * GAQL search — POST to /customers/{customer_id}/googleAds:search
     */
    protected function searchRequest(string $accessToken, string $customerId, string $query): array
    {
        $url = $this->baseUrl . 'customers/' . $customerId . '/googleAds:search';

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'developer-token: ' . $this->developerToken,
        ];
        if ($this->loginCustomerId) {
            $headers[] = 'login-customer-id: ' . $this->loginCustomerId;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['query' => $query], JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("YouTube Ads API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true) ?: [];
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $msg = $decoded['error']['message'] ?? $decoded['error']['status'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('YouTube Ads API error: ' . $msg);
        }
        return $decoded;
    }

    /**
     * GAQL mutate — POST to /customers/{customer_id}/{entity}:mutate
     */
    protected function mutateRequest(string $accessToken, string $customerId, string $entity, array $body): array
    {
        $url = $this->baseUrl . 'customers/' . $customerId . '/' . $entity . ':mutate';

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'developer-token: ' . $this->developerToken,
        ];
        if ($this->loginCustomerId) {
            $headers[] = 'login-customer-id: ' . $this->loginCustomerId;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $bodyStr = curl_exec($ch);
        if ($bodyStr === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("YouTube Ads mutate network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($bodyStr, true) ?: [];
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $msg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('YouTube Ads mutate error: ' . $msg);
        }
        return $decoded;
    }
}
