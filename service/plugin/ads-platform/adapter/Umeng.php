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

class Umeng implements PlatformAdapter
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $baseUrl = 'https://api.open.umeng.com/';

    public function __construct()
    {
        $this->apiKey    = env('UMENG_API_KEY', '');
        $this->apiSecret = env('UMENG_API_SECRET', '');
    }

    public function code(): string { return 'umeng'; }

    public function name(): string { return '友盟'; }

    public function capabilities(): array { return ['report', 'oauth']; }

    // ----------------------------------------------------------------
    //  Authorization — Umeng uses API Key authentication, not OAuth
    // ----------------------------------------------------------------

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        // Umeng uses API Key + Secret setup; there is no OAuth authorization page.
        // Return a placeholder so the UI can guide the user through key configuration.
        return 'https://developer.umeng.com/open/app/';
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        // In Umeng's model the "code" is the user-provided API Key.
        // Validate by making a test call; store the key as the access token.
        $apiKey = $code ?: $this->apiKey;
        $secret = $this->apiSecret;

        if (empty($apiKey) || empty($secret)) {
            throw new RuntimeException('Umeng API Key and Secret are required');
        }

        // Verify credentials with a lightweight account-info call.
        $this->request('GET', 'v1/app/list', [], $apiKey, $secret);

        return [
            'access_token'   => $apiKey,
            'refresh_token'  => '',
            'expires_in'     => 0,
            'advertiser_ids' => [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        // Umeng API Keys do not expire through OAuth flows.
        return [
            'access_token'  => $refreshToken ?: $this->apiKey,
            'refresh_token' => '',
            'expires_in'    => 0,
        ];
    }

    // ----------------------------------------------------------------
    //  Account
    // ----------------------------------------------------------------

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'v1/app/list', [], $accessToken, $this->apiSecret);

        $apps = $resp['data'] ?? [];
        if (isset($resp['app_key'])) {
            // Single-app response shape.
            $apps = [$resp];
        }

        $accounts = [];
        foreach ($apps as $app) {
            $accounts[] = [
                'account_id_on_platform' => $app['app_key'] ?? $app['appkey'] ?? '',
                'account_name'           => $app['app_name'] ?? $app['name'] ?? '',
            ];
        }

        return $accounts;
    }

    // ----------------------------------------------------------------
    //  Campaign / AdGroup / Creative — not supported by Umeng
    // ----------------------------------------------------------------

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        yield from [];
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        yield from [];
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        yield from [];
    }

    // ----------------------------------------------------------------
    //  Reports
    // ----------------------------------------------------------------

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();

        $body = [
            'app_key'    => $accountId,
            'start_date' => $req->dateStart,
            'end_date'   => $req->dateEnd,
            'group_by'   => $req->granularity ?: 'daily',
        ];

        if (!empty($req->dimensions)) {
            $body['dimensions'] = $req->dimensions;
        }

        $page = 1;
        $pageSize = min($req->pageSize ?: 100, 200);
        do {
            $body['page']     = $page;
            $body['per_page'] = $pageSize;

            $resp = $this->request('POST', 'v1/ad_analytics/report', $body, $accessToken, $this->apiSecret);

            $list = $resp['data']['list'] ?? $resp['data'] ?? [];
            if (isset($list['items'])) {
                $list = $list['items'];
            }

            foreach ($list as $row) {
                yield $mapping->map($row);
            }

            $hasMore = !empty($list) && count($list) >= $pageSize;
            $page++;
        } while ($hasMore);
    }

    // ----------------------------------------------------------------
    //  Campaign operations — Umeng is analytics-only
    // ----------------------------------------------------------------

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        throw new RuntimeException('Umeng does not support campaign management');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        throw new RuntimeException('Umeng does not support campaign management');
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        throw new RuntimeException('Umeng does not support campaign management');
    }

    // ----------------------------------------------------------------
    //  Field mappings
    // ----------------------------------------------------------------

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            // Umeng U-Ads analytics fields → unified fields
            'channel'       => 'channel',
            'campaign_name' => 'platform_campaign_name',
            'pv'            => 'impressions',
            'click'         => 'clicks',
            'activation'    => 'conversions',
            'cost_yuan'     => 'cost',
            'cost'          => 'cost',
            'ctr'           => 'ctr',
            'cvr'           => 'cvr',
            'date'          => 'date',
        ], [], function (array $unified): array {
            // Convert yuan to fen (×100) for monetary fields.
            if (isset($unified['cost'])) {
                $unified['cost'] = (int) ((float) $unified['cost'] * 100);
            }
            // Normalize rate fields: API may return percentages (e.g. 3.5 for 3.5%).
            foreach (['ctr', 'cvr'] as $field) {
                if (isset($unified[$field])) {
                    $val = (float) $unified[$field];
                    // If the value looks like a percentage (>1), convert to ratio.
                    if ($val > 1) {
                        $unified[$field] = round($val / 100, 6);
                    } else {
                        $unified[$field] = round($val, 6);
                    }
                }
            }
            // Ensure numeric fields are typed correctly.
            foreach (['impressions', 'clicks', 'conversions'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) $unified[$field];
                }
            }
            return $unified;
        });
    }

    // ----------------------------------------------------------------
    //  HTTP client with Umeng MD5-signature auth
    // ----------------------------------------------------------------

    protected function request(
        string $method,
        string $path,
        array  $params = [],
        ?string $apiKey = null,
        ?string $apiSecret = null
    ): array {
        $key    = $apiKey ?: $this->apiKey;
        $secret = $apiSecret ?: $this->apiSecret;
        $url    = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        $body   = '';
        $timestamp = (string) (time() * 1000);

        // Build signed headers.
        $headers = [
            'Content-Type'    => 'application/json',
            'X-Umeng-API-Key' => $key,
            'X-Umeng-Timestamp' => $timestamp,
        ];

        $ch = curl_init();

        if ($method === 'GET') {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        } else {
            $body = json_encode($params, JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // Umeng signature: md5(method + url + body + api_secret)
        $signStr = strtoupper($method) . $url . $body . $secret;
        $headers['X-Umeng-Sign'] = md5($signStr);

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => array_map(
                fn($k, $v) => "$k: $v",
                array_keys($headers),
                $headers
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $respBody = curl_exec($ch);
        if ($respBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Umeng API network error: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($respBody, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException(
                'Umeng API HTTP error: ' . $httpCode . ' — ' . ($decoded['message'] ?? $respBody)
            );
        }

        // Umeng uses various response codes; treat non-success as error.
        $code = $decoded['code'] ?? $decoded['status'] ?? -1;
        if ($code !== 0 && $code !== 'OK' && $code !== 200 && $code !== '200') {
            throw new RuntimeException(
                'Umeng API error: ' . ($decoded['message'] ?? $decoded['msg'] ?? 'Unknown error')
            );
        }

        return $decoded;
    }
}
