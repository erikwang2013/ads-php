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

class Twitter implements PlatformAdapter
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl = 'https://ads-api.x.com/v12/';

    public function __construct()
    {
        $this->clientId     = env('TWITTER_CLIENT_ID', '');
        $this->clientSecret = env('TWITTER_CLIENT_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string { return 'twitter'; }

    public function name(): string { return 'Twitter/X Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    // ── OAuth ─────────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        // Twitter OAuth 2.0 with PKCE support; uses OAuth 2.0 Bearer token
        $query = http_build_query([
            'response_type'         => 'code',
            'client_id'             => $this->clientId,
            'redirect_uri'          => $redirectUri,
            'state'                 => $state,
            'scope'                 => 'ads.read ads.write offline.access',
            'code_challenge_method' => 'plain',
        ]);
        return 'https://x.com/i/oauth2/authorize?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);

        $ch = curl_init('https://api.x.com/2/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $redirectUri,
                'client_id'     => $this->clientId,
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
            throw new RuntimeException("Twitter OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Twitter OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $desc = $decoded['error_description'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Twitter OAuth error: ' . $desc);
        }

        return [
            'access_token'   => $decoded['access_token'] ?? '',
            'refresh_token'  => $decoded['refresh_token'] ?? '',
            'expires_in'     => (int) ($decoded['expires_in'] ?? 7200),
            'advertiser_ids' => [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);

        $ch = curl_init('https://api.x.com/2/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id'     => $this->clientId,
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
            throw new RuntimeException("Twitter token refresh network error [{$errno}]: {$error}");
        }
        curl_close($ch);

        $decoded = json_decode($body, true) ?: [];

        return [
            'access_token'  => $decoded['access_token'] ?? '',
            'refresh_token' => $decoded['refresh_token'] ?? '',
            'expires_in'    => (int) ($decoded['expires_in'] ?? 7200),
        ];
    }

    // ── Account ───────────────────────────────────────────────

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'accounts', [
            'count' => 200,
        ], $accessToken);

        $data = $resp['data'] ?? [];
        $accounts = [];
        foreach ($data as $item) {
            $accounts[] = [
                'account_id_on_platform' => (string) ($item['id'] ?? ''),
                'account_name'           => $item['name'] ?? '',
            ];
        }

        // Twitter pagination via cursor
        $cursor = $resp['next_cursor'] ?? null;
        while ($cursor) {
            $resp = $this->request('GET', 'accounts', [
                'count'  => 200,
                'cursor' => $cursor,
            ], $accessToken);
            $data = $resp['data'] ?? [];
            foreach ($data as $item) {
                $accounts[] = [
                    'account_id_on_platform' => (string) ($item['id'] ?? ''),
                    'account_name'           => $item['name'] ?? '',
                ];
            }
            $cursor = $resp['next_cursor'] ?? null;
        }

        return $accounts;
    }

    // ── Campaigns ─────────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $urlPath = "accounts/{$accountId}/campaigns";
        $cursor   = null;

        do {
            $params = [
                'count'          => 200,
                'campaign_ids'   => null,
                'with_deleted'   => 'true',
            ];
            if ($cursor) {
                $params['cursor'] = $cursor;
            }

            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $cursor = $resp['next_cursor'] ?? null;
        } while ($cursor);
    }

    // ── AdGroups (Line Items in Twitter) ──────────────────────

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adgroupFieldMapping();
        $urlPath = "accounts/{$accountId}/line_items";
        $cursor   = null;

        do {
            $params = [
                'count'        => 200,
                'campaign_id'  => $campaignId,
                'with_deleted' => 'true',
            ];
            if ($cursor) {
                $params['cursor'] = $cursor;
            }

            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $cursor = $resp['next_cursor'] ?? null;
        } while ($cursor);
    }

    // ── Creatives (Promoted Tweets / Cards) ───────────────────

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $urlPath = "accounts/{$accountId}/promoted_tweets";
        $cursor   = null;

        do {
            $params = [
                'count'        => 200,
                'line_item_id' => $adGroupId,
                'with_deleted' => 'true',
            ];
            if ($cursor) {
                $params['cursor'] = $cursor;
            }

            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $cursor = $resp['next_cursor'] ?? null;
        } while ($cursor);
    }

    // ── Reports (Stats) ───────────────────────────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $urlPath = "stats/accounts/{$accountId}";
        $cursor   = null;

        do {
            $params = [
                'start_time'     => $req->dateStart . 'T00:00:00Z',
                'end_time'       => $req->dateEnd . 'T23:59:59Z',
                'granularity'    => strtoupper($req->granularity),
                'entity'         => 'CAMPAIGN',
                'metric_groups'  => implode(',', $req->metrics ?: [
                    'ENGAGEMENT',
                    'BILLING',
                    'WEB_CONVERSION',
                ]),
                'placement'      => 'ALL_ON_TWITTER',
                'count'          => min($req->pageSize, 200),
            ];
            if ($cursor) {
                $params['cursor'] = $cursor;
            }

            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                // Twitter stats uses id_data to map entity IDs
                $flatRow = array_merge(
                    $row['id_data'][0] ?? $row,
                    $row
                );
                yield $mapping->map($flatRow);
            }
            $cursor = $resp['next_cursor'] ?? null;
        } while ($cursor);
    }

    // ── Delivery operations ───────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $body = [
            'name'               => $data->name,
            'entity_status'      => 'PAUSED',
            'funding_instrument_id' => $data->extra['funding_instrument_id'] ?? '',
        ];

        if ($data->dailyBudget > 0) {
            // Twitter budget in cents; fen == cents, no conversion
            $body['daily_budget_amount_local_micro'] = $data->dailyBudget * 10000;
        }
        if ($data->totalBudget) {
            $body['total_budget_amount_local_micro'] = $data->totalBudget * 10000;
        }

        $resp = $this->request('POST', "accounts/{$accountId}/campaigns", $body, $accessToken);
        $result = $resp['data'] ?? $resp;
        return (string) ($result['id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $body = [
            'name' => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $body['daily_budget_amount_local_micro'] = $data->dailyBudget * 10000;
        }
        $this->request('PUT', "accounts/{$accountId}/campaigns/{$platformId}", $body, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('PUT', "accounts/{$accountId}/campaigns/{$platformId}", [
            'entity_status' => $enabled ? 'ACTIVE' : 'PAUSED',
        ], $accessToken);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'                              => 'platform_campaign_id',
            'name'                            => 'name',
            'daily_budget_amount_local_micro' => 'daily_budget',
            'entity_status'                   => 'status',
        ], [
            'ACTIVE'  => 'enabled',
            'PAUSED'  => 'paused',
            'DELETED' => 'deleted',
        ], function (array $unified): array {
            // Twitter uses "local micro" for budget: micro-cents.
            // 1 micro-cent = 0.000001 cent. In fen: micro-cent / 10000 = fen.
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
            'entity_status' => 'status',
        ], [
            'ACTIVE'  => 'enabled',
            'PAUSED'  => 'paused',
            'DELETED' => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'            => 'platform_creative_id',
            'line_item_id'  => 'platform_ad_group_id',
            'entity_status' => 'status',
        ], [
            'ACTIVE'  => 'enabled',
            'PAUSED'  => 'paused',
            'DELETED' => 'deleted',
        ], function (array $unified): array {
            // Extract tweet text as title when available
            if (isset($unified['extra']['tweet_text'])) {
                $unified['title'] = $unified['extra']['tweet_text'];
            }
            return $unified;
        });
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id' => 'platform_campaign_id',
            'billed_charge_local_micro' => 'cost',
            'impressions'               => 'impressions',
            'clicks'                    => 'clicks',
            'conversions'               => 'conversions',
            'ctr'                       => 'ctr',
            'cpm'                       => 'cpm',
            'cpc'                       => 'cpc',
        ], [], function (array $unified): array {
            // Twitter billing in micro-cents. Convert micro-cents to fen: ÷10000
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) round((float) $unified[$field] / 10000);
                }
            }
            // CTR/CVR are decimals; ensure consistent precision
            foreach (['ctr', 'cvr'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = round((float) $unified[$field], 6);
                }
            }
            // Extract campaign_id from id_data if nested
            if (empty($unified['platform_campaign_id']) && isset($unified['extra']['entity_id'])) {
                $unified['platform_campaign_id'] = (string) $unified['extra']['entity_id'];
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
        } elseif (strtoupper($method) === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
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
            throw new RuntimeException("Twitter API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Twitter API: invalid JSON response');
        }
        if ($httpCode >= 400 || (isset($decoded['errors']) && !empty($decoded['errors']))) {
            $errors = $decoded['errors'] ?? [];
            $msg = '';
            if (!empty($errors)) {
                $msgs = array_map(fn($e) => ($e['code'] ?? '') . ': ' . ($e['message'] ?? 'unknown'), $errors);
                $msg = implode('; ', $msgs);
            }
            if (!$msg) {
                $msg = $decoded['detail'] ?? $decoded['title'] ?? "HTTP {$httpCode}";
            }
            throw new RuntimeException('Twitter API error: ' . $msg);
        }
        return $decoded;
    }
}
