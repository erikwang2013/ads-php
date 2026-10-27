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

class Pinterest implements PlatformAdapter
{
    protected string $appId;
    protected string $appSecret;
    protected string $baseUrl = 'https://api.pinterest.com/v5/';

    public function __construct()
    {
        $this->appId     = env('PINTEREST_APP_ID', '');
        $this->appSecret = env('PINTEREST_APP_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string { return 'pinterest'; }

    public function name(): string { return 'Pinterest Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    // ── OAuth ─────────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->appId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'ads:read,ads:write',
        ]);
        return 'https://www.pinterest.com/oauth/?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $auth = base64_encode($this->appId . ':' . $this->appSecret);

        $ch = curl_init('https://api.pinterest.com/v5/oauth/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $redirectUri,
            ]),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Pinterest OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Pinterest OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['message'])) {
            $desc = $decoded['message'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Pinterest OAuth error: ' . $desc);
        }

        return [
            'access_token'   => $decoded['access_token'] ?? '',
            'refresh_token'  => $decoded['refresh_token'] ?? '',
            'expires_in'     => (int) ($decoded['expires_in'] ?? 86400),
            'advertiser_ids' => $decoded['advertiser_ids'] ?? [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $auth = base64_encode($this->appId . ':' . $this->appSecret);

        $ch = curl_init('https://api.pinterest.com/v5/oauth/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Pinterest OAuth network error [{$errno}]: {$error}");
        }
        curl_close($ch);

        $decoded = json_decode($body, true) ?: [];

        return [
            'access_token'  => $decoded['access_token'] ?? '',
            'refresh_token' => $decoded['refresh_token'] ?? '',
            'expires_in'    => (int) ($decoded['expires_in'] ?? 86400),
        ];
    }

    // ── Account ───────────────────────────────────────────────

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'ad_accounts', [
            'page_size' => 100,
        ], $accessToken);

        $items = $resp['items'] ?? [];
        $accounts = [];
        foreach ($items as $item) {
            $accounts[] = [
                'account_id_on_platform' => (string) ($item['id'] ?? ''),
                'account_name'           => $item['name'] ?? '',
            ];
        }

        // Handle pagination
        $bookmark = $resp['bookmark'] ?? null;
        while ($bookmark) {
            $resp = $this->request('GET', 'ad_accounts', [
                'page_size' => 100,
                'bookmark'  => $bookmark,
            ], $accessToken);
            $items = $resp['items'] ?? [];
            foreach ($items as $item) {
                $accounts[] = [
                    'account_id_on_platform' => (string) ($item['id'] ?? ''),
                    'account_name'           => $item['name'] ?? '',
                ];
            }
            $bookmark = $resp['bookmark'] ?? null;
        }

        return $accounts;
    }

    // ── Campaigns ─────────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping  = $this->campaignFieldMapping();
        $urlPath  = "ad_accounts/{$accountId}/campaigns";
        $bookmark = null;

        do {
            $params = ['page_size' => 100];
            if ($bookmark) {
                $params['bookmark'] = $bookmark;
            }
            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $items = $resp['items'] ?? [];
            foreach ($items as $row) {
                yield $mapping->map($row);
            }
            $bookmark = $resp['bookmark'] ?? null;
        } while ($bookmark);
    }

    // ── AdGroups ──────────────────────────────────────────────

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping  = $this->adgroupFieldMapping();
        $urlPath  = "ad_accounts/{$accountId}/ad_groups";
        $bookmark = null;

        do {
            $params = [
                'page_size'   => 100,
                'campaign_id' => $campaignId,
            ];
            if ($bookmark) {
                $params['bookmark'] = $bookmark;
            }
            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $items = $resp['items'] ?? [];
            foreach ($items as $row) {
                yield $mapping->map($row);
            }
            $bookmark = $resp['bookmark'] ?? null;
        } while ($bookmark);
    }

    // ── Creatives ─────────────────────────────────────────────

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping  = $this->creativeFieldMapping();
        $urlPath  = "ad_accounts/{$accountId}/ads";
        $bookmark = null;

        do {
            $params = [
                'page_size'   => 100,
                'ad_group_id' => $adGroupId,
            ];
            if ($bookmark) {
                $params['bookmark'] = $bookmark;
            }
            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $items = $resp['items'] ?? [];
            foreach ($items as $row) {
                yield $mapping->map($row);
            }
            $bookmark = $resp['bookmark'] ?? null;
        } while ($bookmark);
    }

    // ── Reports (Analytics) ───────────────────────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping  = $this->reportFieldMapping();
        $urlPath  = "ad_accounts/{$accountId}/analytics";
        $bookmark = null;

        do {
            $params = [
                'start_date'   => $req->dateStart,
                'end_date'     => $req->dateEnd,
                'granularity'  => strtoupper($req->granularity),
                'columns'      => implode(',', $req->metrics ?: [
                    'SPEND_IN_MICRO_DOLLAR',
                    'IMPRESSIONS',
                    'CLICKS',
                    'CONVERSIONS',
                    'CTR',
                    'CPM_IN_MICRO_DOLLAR',
                    'CPC_IN_MICRO_DOLLAR',
                ]),
                'campaign_ids' => $req->extra['campaign_ids'] ?? null,
            ];

            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $items = $resp['items'] ?? [];
            foreach ($items as $row) {
                // Flatten: Pinterest analytics returns nested data with dimensions
                $flatRow = array_merge(
                    $row['data'] ?? $row,
                    $row['dimension_values'] ?? $row
                );
                yield $mapping->map($flatRow);
            }
            $bookmark = $resp['bookmark'] ?? null;
        } while ($bookmark);
    }

    // ── Delivery operations ───────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $body = [
            'name'          => $data->name,
            'objective_type' => $data->extra['objective_type'] ?? 'AWARENESS',
            'status'        => 'PAUSED',
        ];

        if ($data->dailyBudget > 0) {
            // fen × 10000 = micro-dollars
            $body['daily_spend_cap'] = $data->dailyBudget * 10000;
        }

        $resp = $this->request('POST', "ad_accounts/{$accountId}/campaigns", $body, $accessToken);
        return (string) ($resp['id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $body = ['id' => $platformId, 'name' => $data->name];
        if ($data->dailyBudget > 0) {
            $body['daily_spend_cap'] = $data->dailyBudget * 10000;
        }
        $this->request('PATCH', "ad_accounts/{$accountId}/campaigns", [$body], $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('PATCH', "ad_accounts/{$accountId}/campaigns", [[
            'id'     => $platformId,
            'status' => $enabled ? 'ACTIVE' : 'PAUSED',
        ]], $accessToken);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'                => 'platform_campaign_id',
            'name'              => 'name',
            'daily_spend_cap'   => 'daily_budget',
            'status'            => 'status',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'   => 'paused',
            'ARCHIVED' => 'deleted',
        ], function (array $unified): array {
            // Convert micro-dollars to fen (÷10000)
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) round((float) $unified['daily_budget'] / 10000);
            }
            return $unified;
        });
    }

    protected function adgroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'            => 'platform_ad_group_id',
            'name'          => 'name',
            'campaign_id'   => 'platform_campaign_id',
            'status'        => 'status',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'   => 'paused',
            'ARCHIVED' => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'           => 'platform_creative_id',
            'name'         => 'title',
            'ad_group_id'  => 'platform_ad_group_id',
            'status'       => 'status',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'   => 'paused',
            'ARCHIVED' => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'CAMPAIGN_ID'              => 'platform_campaign_id',
            'SPEND_IN_MICRO_DOLLAR'    => 'cost',
            'IMPRESSIONS'              => 'impressions',
            'CLICKS'                   => 'clicks',
            'CONVERSIONS'              => 'conversions',
            'CTR'                      => 'ctr',
            'CPM_IN_MICRO_DOLLAR'      => 'cpm',
            'CPC_IN_MICRO_DOLLAR'      => 'cpc',
        ], [], function (array $unified): array {
            // Pinterest returns money in micro-dollars; convert to fen (÷10000)
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) round((float) $unified[$field] / 10000);
                }
            }
            // CTR is typically a decimal; ensure consistent precision
            foreach (['ctr', 'cvr'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = round((float) $unified[$field], 6);
                }
            }
            return $unified;
        });
    }

    // ── HTTP layer ────────────────────────────────────────────

    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url = $this->baseUrl . $path;

        $headers = ['Content-Type: application/json'];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $ch = curl_init();
        if (strtoupper($method) === 'GET') {
            if (!empty($params)) {
                // Remove null values from query params
                $params = array_filter($params, fn($v) => $v !== null);
                $url .= '?' . http_build_query($params);
            }
        } elseif (strtoupper($method) === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
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
            throw new RuntimeException("Pinterest API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Pinterest API: invalid JSON response');
        }
        if ($httpCode >= 400 || isset($decoded['message'])) {
            $msg = $decoded['message'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Pinterest API error: ' . $msg);
        }
        return $decoded;
    }
}
