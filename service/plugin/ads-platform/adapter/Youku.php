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
 * 优酷广告平台适配器（阿里系）
 *
 * 与淘宝广告同属阿里体系，使用相同的 MD5 签名算法。
 * 金额单位：API 返回「元」，统一存储为「分」（×100）。
 */
class Youku implements PlatformAdapter
{
    protected string $appKey;
    protected string $secret;
    protected string $authBaseUrl = 'https://open.youku.com/oauth/authorize';
    protected string $tokenUrl    = 'https://open.youku.com/oauth/token';
    protected string $apiBaseUrl  = 'https://api.youku.com/ad/v1/';

    public function __construct()
    {
        $this->appKey = env('YOUKU_APP_KEY', '');
        $this->secret = env('YOUKU_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string
    {
        return 'youku';
    }

    public function name(): string
    {
        return '优酷广告';
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
            'client_id'     => $this->appKey,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
        ]);
        return $this->authBaseUrl . '?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->tokenRequest([
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->appKey,
            'client_secret' => $this->secret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]);
        return [
            'access_token'   => $resp['access_token'] ?? '',
            'refresh_token'  => $resp['refresh_token'] ?? '',
            'expires_in'     => (int) ($resp['expires_in'] ?? 86400),
            'advertiser_ids' => $resp['advertiser_ids'] ?? [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->tokenRequest([
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->appKey,
            'client_secret' => $this->secret,
            'refresh_token' => $refreshToken,
        ]);
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
            'campaign_type' => 'NORMAL',
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
     * OAuth token endpoint request.
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
            throw new RuntimeException("Youku OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Youku OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $desc = $decoded['error_description'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Youku OAuth error: ' . $desc);
        }
        return $decoded;
    }

    /**
     * Signed API request.
     *
     * 签名算法与淘宝广告一致（阿里系通用）：
     *   sign = strtoupper(md5(secret . sorted_key_value_concat . secret))
     */
    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url  = $this->apiBaseUrl . ltrim($path, '/');
        $signParams = $params;
        $signParams['app_key']   = $this->appKey;
        $signParams['timestamp'] = date('Y-m-d H:i:s');
        $signParams['v']         = '1.0';
        $signParams['format']    = 'json';
        if ($accessToken) {
            $signParams['access_token'] = $accessToken;
        }
        $signParams['sign'] = $this->sign($signParams);

        $ch = curl_init();
        if ($method === 'GET') {
            $url .= '?' . http_build_query($signParams);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($signParams));
        }
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Youku API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Youku API: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error_response'])) {
            $err  = $decoded['error_response'] ?? [];
            $desc = ($err['msg'] ?? $err['message'] ?? '') ?: "HTTP {$httpCode}";
            $code = $err['code'] ?? 0;
            throw new RuntimeException("Youku API error [code {$code}]: {$desc}");
        }
        return $decoded;
    }

    /**
     * 阿里系通用 MD5 签名。
     *
     * sign = strtoupper(md5(secret . sorted_key_value_concat . secret))
     */
    protected function sign(array $params): string
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $k => $v) {
            if ($k === 'sign' || $v === '' || $v === null) {
                continue;
            }
            $signStr .= $k . $v;
        }
        return strtoupper(md5($this->secret . $signStr . $this->secret));
    }
}
