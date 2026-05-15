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

/**
 * 搜狗推广平台适配器
 *
 * 鉴权方式：API Key + Secret，通过自定义请求头传递。
 * 签名：md5(api_key . timestamp . api_secret)
 * 金额单位：API 返回「元」，统一存储为「分」（×100）。
 * 状态映射：1→enabled, 0→paused
 */
class Sogou implements PlatformAdapter
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $authBaseUrl = 'https://open.sogou.com/oauth/authorize';
    protected string $tokenUrl    = 'https://open.sogou.com/oauth/token';
    protected string $apiBaseUrl  = 'https://api.sogou.com/ad/v2/';

    public function __construct()
    {
        $this->apiKey    = env('SOGOU_API_KEY', '');
        $this->apiSecret = env('SOGOU_API_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string
    {
        return 'sogou';
    }

    public function name(): string
    {
        return '搜狗推广';
    }

    public function capabilities(): array
    {
        return ['report', 'campaign', 'oauth'];
    }

    // ── OAuth ─────────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->apiKey,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
        ]);
        return $this->authBaseUrl . '?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $params = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->apiKey,
            'client_secret' => $this->apiSecret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ];
        $resp = $this->tokenRequest($params);
        return [
            'access_token'   => $resp['access_token'] ?? '',
            'refresh_token'  => $resp['refresh_token'] ?? '',
            'expires_in'     => (int) ($resp['expires_in'] ?? 86400),
            'advertiser_ids' => $resp['advertiser_ids'] ?? [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $params = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->apiKey,
            'client_secret' => $this->apiSecret,
            'refresh_token' => $refreshToken,
        ];
        $resp = $this->tokenRequest($params);
        return [
            'access_token'  => $resp['access_token'] ?? '',
            'refresh_token' => $resp['refresh_token'] ?? '',
            'expires_in'    => (int) ($resp['expires_in'] ?? 86400),
        ];
    }

    // ── Account ───────────────────────────────────────────────

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'account/info', [], $accessToken);
        $list = $resp['data']['list'] ?? $resp['data'] ?? [];
        if (isset($list['account_id'])) {
            $list = [$list];
        }
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['account_id'] ?? ''),
            'account_name'           => $item['account_name'] ?? '',
        ], $list);
    }

    // ── Campaigns ─────────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $page    = 1;
        do {
            $resp = $this->request('GET', 'campaigns', [
                'page_no'   => $page,
                'page_size' => 100,
            ], $accessToken);
            $list = $resp['data']['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $page++;
        } while ($hasMore);
    }

    // ── AdGroups ──────────────────────────────────────────────

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        yield from [];
    }

    // ── Creatives ─────────────────────────────────────────────

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        yield from [];
    }

    // ── Reports ───────────────────────────────────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $page    = 1;
        do {
            $resp = $this->request('GET', 'reports', [
                'start_date'  => $req->dateStart,
                'end_date'    => $req->dateEnd,
                'granularity' => strtoupper($req->granularity),
                'page_no'     => $page,
                'page_size'   => min($req->pageSize, 200),
            ], $accessToken);
            $list = $resp['data']['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $page++;
        } while ($hasMore);
    }

    // ── Delivery operations ───────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->request('POST', 'campaigns', [
            'campaign_name' => $data->name,
            'day_budget'    => $data->dailyBudget / 100,   // fen -> yuan
            'status'        => 1,                           // enabled
        ], $accessToken);
        return (string) ($resp['data']['campaign_id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $params = [
            'campaign_id'   => $platformId,
            'campaign_name' => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $params['day_budget'] = $data->dailyBudget / 100;
        }
        $this->request('POST', 'campaigns/' . $platformId, $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('POST', 'campaigns/' . $platformId . '/status', [
            'status' => $enabled ? 1 : 0,
        ], $accessToken);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'campaign_name' => 'name',
            'day_budget'    => 'daily_budget',
            'status'        => 'status',
        ], [
            1 => 'enabled',
            0 => 'paused',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget'] * 100);
            }
            return $unified;
        });
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'  => 'platform_campaign_id',
            'cost'         => 'cost',
            'impressions'  => 'impressions',
            'clicks'       => 'clicks',
            'conversions'  => 'conversions',
            'ctr'          => 'ctr',
            'cpm'          => 'cpm',
            'cpc'          => 'cpc',
            'cvr'          => 'cvr',
        ], [], function (array $unified): array {
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) ($unified[$field] * 100);
                }
            }
            foreach (['ctr', 'cvr'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = round((float) $unified[$field] / 100, 6);
                }
            }
            return $unified;
        });
    }

    // ── HTTP layer ────────────────────────────────────────────

    /**
     * OAuth token endpoint request (plain POST, no auth headers).
     */
    protected function tokenRequest(array $params): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->tokenUrl,
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
            throw new RuntimeException("Sogou OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Sogou OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $desc = $decoded['error_description'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Sogou OAuth error: ' . $desc);
        }
        return $decoded;
    }

    /**
     * Signed API request with custom auth headers.
     *
     * 签名算法：
     *   timestamp = 当前秒级时间戳
     *   signStr   = api_key . timestamp . api_secret
     *   sign      = md5(signStr)
     *
     * 请求头：X-Api-Key, X-Api-Sign, X-Api-Timestamp
     */
    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url       = $this->apiBaseUrl . ltrim($path, '/');
        $timestamp = (string) time();
        $signStr   = $this->apiKey . $timestamp . $this->apiSecret;

        $headers = [
            'Content-Type'    => 'application/json',
            'X-Api-Key'       => $this->apiKey,
            'X-Api-Sign'      => md5($signStr),
            'X-Api-Timestamp' => $timestamp,
        ];
        if ($accessToken) {
            $headers['X-Access-Token'] = $accessToken;
        }

        $ch = curl_init();
        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Sogou API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Sogou API: invalid JSON response');
        }
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            $desc = $decoded['message'] ?? "HTTP {$httpCode}";
            $code = $decoded['code'] ?? 0;
            throw new RuntimeException("Sogou API error [code {$code}]: {$desc}");
        }
        return $decoded;
    }
}
