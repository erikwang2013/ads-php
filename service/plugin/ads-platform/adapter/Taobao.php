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

class Taobao implements PlatformAdapter
{
    protected string $appKey;
    protected string $secret;
    protected string $authBaseUrl = 'https://oauth.taobao.com/authorize';
    protected string $tokenUrl    = 'https://oauth.taobao.com/token';
    protected string $apiBaseUrl  = 'https://api.taobao.com/router/rest';

    public function __construct()
    {
        $this->appKey = env('TAOBAO_APP_KEY', '');
        $this->secret = env('TAOBAO_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string { return 'taobao'; }

    public function name(): string { return '淘宝广告'; }

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
        $resp = $this->request('taobao.advertiser.info.get', [], $accessToken);
        $data = $resp['advertiser_info_get_response'] ?? [];
        $list = $data['advertiser_list'] ?? $data['list'] ?? [];
        if (isset($data['advertiser_id'])) {
            $list = [$data];
        }
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['advertiser_id'] ?? ''),
            'account_name'           => $item['advertiser_name'] ?? '',
        ], $list);
    }

    // ── Campaigns ─────────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $pageNo  = 1;
        do {
            $resp = $this->request('taobao.campaign.get', [
                'campaign_type' => 'NORMAL',
                'page_no'       => $pageNo,
                'page_size'     => 100,
            ], $accessToken);
            $data = $resp['campaign_get_response'] ?? [];
            $list = $data['campaign_list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $pageNo++;
        } while ($hasMore);
    }

    // ── AdGroups ──────────────────────────────────────────────

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        // Taobao does not expose a separate ad-group concept; skip
        yield from [];
    }

    // ── Creatives ─────────────────────────────────────────────

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        // Taobao creative-level fetch is not available through this interface; skip
        yield from [];
    }

    // ── Reports ───────────────────────────────────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $pageNo  = 1;
        do {
            $resp = $this->request('taobao.report.get', [
                'start_date'  => $req->dateStart,
                'end_date'    => $req->dateEnd,
                'granularity' => strtoupper($req->granularity),
                'page_no'     => $pageNo,
                'page_size'   => min($req->pageSize, 200),
            ], $accessToken);
            $data = $resp['report_get_response'] ?? [];
            $list = $data['report_list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $pageNo++;
        } while ($hasMore);
    }

    // ── Delivery operations ───────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->request('taobao.campaign.add', [
            'campaign_name' => $data->name,
            'day_budget'    => $data->dailyBudget / 100,   // fen -> yuan
            'campaign_type' => 'NORMAL',
        ], $accessToken);
        $result = $resp['campaign_add_response'] ?? [];
        return (string) ($result['campaign_id'] ?? '');
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
        $this->request('taobao.campaign.update', $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('taobao.campaign.status.update', [
            'campaign_id'   => $platformId,
            'online_status' => $enabled ? 1 : 0,
        ], $accessToken);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'campaign_name' => 'name',
            'day_budget'    => 'daily_budget',
            'online_status' => 'status',
        ], [
            1 => 'enabled',
            0 => 'paused',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                // API returns yuan; store as fen (x100)
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
            // API returns money in yuan; store as fen (x100)
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) ($unified[$field] * 100);
                }
            }
            // Ratios: API returns percentage (e.g. 5.3 = 5.3%); store as decimal
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
     * OAuth token request -- plain POST, no signing envelope.
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
            throw new RuntimeException("Taobao OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Taobao OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $desc = $decoded['error_description'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Taobao OAuth error: ' . $desc);
        }
        return $decoded;
    }

    /**
     * Signed API request to the Taobao router endpoint.
     *
     * Builds the mandatory system params (method, app_key, timestamp, v, format,
     * sign_method), merges business params, computes an MD5 signature, and POSTs
     * to https://api.taobao.com/router/rest.
     */
    protected function request(string $method, array $params = [], ?string $accessToken = null): array
    {
        $apiParams = [
            'method'      => $method,
            'app_key'     => $this->appKey,
            'timestamp'   => date('Y-m-d H:i:s'),
            'v'           => '2.0',
            'format'      => 'json',
            'sign_method' => 'md5',
        ];
        if ($accessToken) {
            $apiParams['session'] = $accessToken;
        }

        $apiParams = array_merge($apiParams, $params);
        $apiParams['sign'] = $this->sign($apiParams);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiBaseUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($apiParams),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Taobao API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Taobao API: invalid JSON response');
        }

        // Taobao error envelope: {"error_response": {"code": ..., "msg": ..., ...}}
        if ($httpCode !== 200 || isset($decoded['error_response'])) {
            $err  = $decoded['error_response'] ?? [];
            $desc = ($err['msg'] ?? $err['sub_msg'] ?? '') ?: "HTTP {$httpCode}";
            $code = $err['code'] ?? 0;
            throw new RuntimeException("Taobao API error [code {$code}]: {$desc}");
        }
        return $decoded;
    }

    /**
     * Taobao API signature algorithm.
     *
     * sign = strtoupper(md5(secret . sorted_key_value_concat . secret))
     *
     * Keys are sorted alphabetically. The 'sign' key and empty/null values are
     * excluded from the concatenation string.
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
