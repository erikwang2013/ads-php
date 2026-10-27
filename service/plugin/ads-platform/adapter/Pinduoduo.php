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
 * 拼多多广告 (多多进宝) adapter.
 *
 * PDD uses a gateway pattern: all API requests go to /api/router with a
 * type param naming the method. Sign algorithm is:
 * md5(client_id + method + timestamp + data_type + access_token + secret).
 * Money is in FEN natively -- no unit conversion needed.
 */
class Pinduoduo implements PlatformAdapter
{
    protected string $clientId;
    protected string $secret;
    protected string $authBaseUrl = 'https://mms.pinduoduo.com/open.html';
    protected string $tokenUrl    = 'https://gw-api.pinduoduo.com/api/router';
    protected string $apiBaseUrl  = 'https://gw-api.pinduoduo.com/api/router';

    public function __construct()
    {
        $this->clientId = env('PINDUODUO_CLIENT_ID', '');
        $this->secret   = env('PINDUODUO_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string { return 'pinduoduo'; }

    public function name(): string { return '拼多多广告'; }

    public function capabilities(): array
    {
        return ['report', 'campaign', 'oauth'];
    }

    // ── OAuth ─────────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
        ]);
        return $this->authBaseUrl . '?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->tokenRequest([
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->secret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]);
        $data = $resp['access_token_response'] ?? $resp;
        return [
            'access_token'   => $data['access_token'] ?? '',
            'refresh_token'  => $data['refresh_token'] ?? '',
            'expires_in'     => (int) ($data['expires_in'] ?? 86400),
            'advertiser_ids' => $data['advertiser_ids'] ?? [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->tokenRequest([
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->secret,
            'refresh_token' => $refreshToken,
        ]);
        $data = $resp['refresh_token_response'] ?? $resp;
        return [
            'access_token'  => $data['access_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? '',
            'expires_in'    => (int) ($data['expires_in'] ?? 86400),
        ];
    }

    // ── Account ───────────────────────────────────────────────

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('pdd.advertiser.info.get', [], $accessToken);
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

    // ── Campaigns (PDD calls them "plans") ────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $pageNo  = 1;
        do {
            $resp = $this->request('pdd.ad.api.plan.list', [
                'page_no'   => $pageNo,
                'page_size' => 100,
            ], $accessToken);
            $data = $resp['pdd_ad_api_plan_list_response'] ?? [];
            $list = $data['plan_list'] ?? [];
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
        yield from [];
    }

    // ── Creatives ─────────────────────────────────────────────

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        // PDD does not expose a creative endpoint at this level
        yield from [];
    }

    // ── Reports ───────────────────────────────────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $pageNo  = 1;
        do {
            $resp = $this->request('pdd.ad.api.report.query', [
                'start_date'  => $req->dateStart,
                'end_date'    => $req->dateEnd,
                'granularity' => strtoupper($req->granularity),
                'page_no'     => $pageNo,
                'page_size'   => min($req->pageSize, 200),
            ], $accessToken);
            $data = $resp['pdd_ad_api_report_query_response'] ?? [];
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
        // PDD uses fen natively -- dailyBudget is passed directly (no /100)
        $resp = $this->request('pdd.ad.api.plan.create', [
            'plan_name'  => $data->name,
            'day_budget' => $data->dailyBudget,
        ], $accessToken);
        $result = $resp['pdd_ad_api_plan_create_response'] ?? [];
        return (string) ($result['plan_id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $params = [
            'plan_id'   => $platformId,
            'plan_name' => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $params['day_budget'] = $data->dailyBudget;
        }
        $this->request('pdd.ad.api.plan.update', $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('pdd.ad.api.plan.status.update', [
            'plan_id'       => $platformId,
            'online_status' => $enabled ? 1 : 0,
        ], $accessToken);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'plan_id'       => 'platform_campaign_id',
            'plan_name'     => 'name',
            'day_budget'    => 'daily_budget',
            'online_status' => 'status',
        ], [
            1 => 'enabled',
            0 => 'paused',
        ], function (array $unified): array {
            // PDD uses fen natively; ensure integer
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) $unified['daily_budget'];
            }
            return $unified;
        });
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'plan_id'     => 'platform_campaign_id',
            'cost'        => 'cost',
            'impressions' => 'impressions',
            'clicks'      => 'clicks',
            'conversions' => 'conversions',
            'ctr'         => 'ctr',
            'cpm'         => 'cpm',
            'cpc'         => 'cpc',
            'cvr'         => 'cvr',
        ], [], function (array $unified): array {
            // PDD uses fen natively for cost/cpm/cpc; ensure integer
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) $unified[$field];
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
            throw new RuntimeException("Pinduoduo OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Pinduoduo OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $desc = $decoded['error_description'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Pinduoduo OAuth error: ' . $desc);
        }
        return $decoded;
    }

    /**
     * Signed API request to the PDD router endpoint.
     *
     * Builds mandatory system params (type, client_id, access_token, timestamp,
     * data_type=JSON), merges business params, computes the PDD-specific MD5
     * signature, and POSTs to https://gw-api.pinduoduo.com/api/router.
     */
    protected function request(string $method, array $params = [], ?string $accessToken = null): array
    {
        $timestamp = date('Y-m-d H:i:s');
        $dataType  = 'JSON';

        $apiParams = [
            'type'      => $method,
            'client_id' => $this->clientId,
            'timestamp' => $timestamp,
            'data_type' => $dataType,
        ];
        if ($accessToken) {
            $apiParams['access_token'] = $accessToken;
        }

        $apiParams = array_merge($apiParams, $params);
        $apiParams['sign'] = $this->sign($method, $timestamp, $dataType, $accessToken);

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
            throw new RuntimeException("Pinduoduo API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Pinduoduo API: invalid JSON response');
        }

        if ($httpCode !== 200 || isset($decoded['error_response'])) {
            $err  = $decoded['error_response'] ?? [];
            $desc = ($err['error_msg'] ?? $err['msg'] ?? '') ?: "HTTP {$httpCode}";
            $code = $err['code'] ?? 0;
            throw new RuntimeException("Pinduoduo API error [code {$code}]: {$desc}");
        }
        return $decoded;
    }

    /**
     * PDD API signature algorithm.
     *
     * sign = strtoupper(md5(client_id . method . timestamp . data_type . access_token . secret))
     *
     * The access_token may be empty (e.g. for public endpoints or token requests).
     */
    protected function sign(string $method, string $timestamp, string $dataType, ?string $accessToken = null): string
    {
        $signStr = $this->clientId . $method . $timestamp . $dataType . ($accessToken ?? '') . $this->secret;
        return strtoupper(md5($signStr));
    }
}
