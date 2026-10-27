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

class Tencent implements PlatformAdapter
{
    protected string $appId;
    protected string $secret;
    protected string $authBaseUrl = 'https://developers.e.qq.com/oauth/';
    protected string $tokenUrl   = 'https://api.e.qq.com/oauth/token';
    protected string $apiBaseUrl = 'https://api.e.qq.com/v3.0/';

    public function __construct()
    {
        $this->appId  = env('TENCENT_APP_ID', '');
        $this->secret = env('TENCENT_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string { return 'tencent'; }

    public function name(): string { return '腾讯广告'; }

    public function capabilities(): array
    {
        return ['report', 'campaign', 'creative', 'oauth'];
    }

    // ── OAuth ─────────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id'     => $this->appId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'campaign_management,reports,ads_management,account_management',
            'response_type' => 'code',
        ]);
        return $this->authBaseUrl . 'authorize?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request('GET', $this->tokenUrl, [
            'client_id'          => $this->appId,
            'client_secret'      => $this->secret,
            'grant_type'         => 'authorization_code',
            'authorization_code' => $code,
            'redirect_uri'       => $redirectUri,
        ]);
        $data = $resp['data'] ?? [];
        $advertiserIds = [];
        if (!empty($data['authorizer_info']['account_id'])) {
            $advertiserIds[] = (string) $data['authorizer_info']['account_id'];
        }
        return [
            'access_token'   => $data['access_token'] ?? '',
            'refresh_token'  => $data['refresh_token'] ?? '',
            'expires_in'     => (int) ($data['access_token_expires_in'] ?? 86400),
            'advertiser_ids' => $advertiserIds,
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->request('GET', $this->tokenUrl, [
            'client_id'     => $this->appId,
            'client_secret' => $this->secret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
        $data = $resp['data'] ?? [];
        return [
            'access_token'  => $data['access_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? '',
            'expires_in'    => (int) ($data['access_token_expires_in'] ?? 86400),
        ];
    }

    // ── Account ───────────────────────────────────────────────

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->apiRequest('GET', 'advertiser/get', [
            'fields' => '["account_id","account_name"]',
        ], $accessToken);
        $list = $resp['data']['list'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['account_id'] ?? ''),
            'account_name'           => $item['account_name'] ?? '',
        ], $list);
    }

    // ── Campaigns ─────────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $page = 1;
        do {
            $resp = $this->apiRequest('GET', 'campaigns/get', [
                'account_id' => (int) $accountId,
                'page'       => $page,
                'page_size'  => 100,
                'fields'     => '["campaign_id","campaign_name","daily_budget","configured_status"]',
            ], $accessToken);
            $list = $resp['data']['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $pageInfo = $resp['data']['page_info'] ?? [];
            $hasMore = $page < ($pageInfo['total_page'] ?? 0);
            $page++;
        } while ($hasMore);
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        yield from [];
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $page = 1;
        do {
            $resp = $this->apiRequest('GET', 'dynamic_creatives/get', [
                'account_id' => (int) $accountId,
                'page'       => $page,
                'page_size'  => 100,
                'fields'     => '["dynamic_creative_id","dynamic_creative_name","configured_status"]',
            ], $accessToken);
            $list = $resp['data']['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $pageInfo = $resp['data']['page_info'] ?? [];
            $hasMore = $page < ($pageInfo['total_page'] ?? 0);
            $page++;
        } while ($hasMore);
    }

    // ── Reports (async: create → poll → fetch) ────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $reportFields = '["date","campaign_id","cost","view_count","valid_click_count","conversions_count","ctr","cpm","cpc","cvr"]';

        // Step 1 – create async report job
        $createResp = $this->apiRequest('POST', 'daily_reports/add', [
            'account_id' => (int) $accountId,
            'level'      => 'REPORT_LEVEL_CAMPAIGN',
            'date_range' => [
                'start_date' => $req->dateStart,
                'end_date'   => $req->dateEnd,
            ],
            'time_line'  => 'REQUEST_TIME',
            'fields'     => $reportFields,
        ], $accessToken);
        $taskId = $createResp['data']['task_id'] ?? '';
        if (!$taskId) {
            throw new RuntimeException('Tencent report: failed to create report job');
        }

        // Step 2 – poll until completed (max 60 s)
        $maxRetries = 30;
        $taskStatus = 'PROCESSING';
        while ($maxRetries > 0 && $taskStatus !== 'SUCCESS') {
            sleep(2);
            $pollResp = $this->apiRequest('GET', 'daily_reports/get', [
                'account_id' => (int) $accountId,
                'task_id'    => $taskId,
                'fields'     => '["task_id","task_status"]',
            ], $accessToken);
            $taskStatus = $pollResp['data']['task_status'] ?? 'FAIL';
            if ($taskStatus === 'FAIL') {
                throw new RuntimeException('Tencent report: report job failed');
            }
            $maxRetries--;
        }
        if ($taskStatus !== 'SUCCESS') {
            throw new RuntimeException('Tencent report: report job timed out');
        }

        // Step 3 – pull paginated report data
        $page = 1;
        do {
            $dataResp = $this->apiRequest('GET', 'daily_reports/get', [
                'account_id' => (int) $accountId,
                'task_id'    => $taskId,
                'page'       => $page,
                'page_size'  => min($req->pageSize, 200),
                'fields'     => $reportFields,
            ], $accessToken);
            $list = $dataResp['data']['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $pageInfo = $dataResp['data']['page_info'] ?? [];
            $hasMore = $page < ($pageInfo['total_page'] ?? 0);
            $page++;
        } while ($hasMore);
    }

    // ── Delivery operations ───────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->apiRequest('POST', 'campaigns/add', [
            'account_id'    => (int) $accountId,
            'campaign_name' => $data->name,
            'campaign_type' => 'CAMPAIGN_TYPE_NORMAL',
            'daily_budget'  => $data->dailyBudget,
        ], $accessToken);
        return (string) ($resp['data']['campaign_id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $params = [
            'account_id'    => (int) $accountId,
            'campaign_id'   => (int) $platformId,
            'campaign_name' => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $params['daily_budget'] = $data->dailyBudget;
        }
        $this->apiRequest('POST', 'campaigns/update', $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->apiRequest('POST', 'campaigns/update_configured_status', [
            'account_id'        => (int) $accountId,
            'campaign_id'       => (int) $platformId,
            'configured_status' => $enabled ? 'AD_STATUS_NORMAL' : 'AD_STATUS_SUSPEND',
        ], $accessToken);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'       => 'platform_campaign_id',
            'campaign_name'     => 'name',
            'daily_budget'      => 'daily_budget',
            'configured_status' => 'status',
        ], [
            'AD_STATUS_NORMAL'  => 'enabled',
            'AD_STATUS_SUSPEND' => 'paused',
            'AD_STATUS_DELETE'  => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'dynamic_creative_id'   => 'platform_creative_id',
            'dynamic_creative_name' => 'title',
            'configured_status'     => 'status',
        ], [
            'AD_STATUS_NORMAL'  => 'enabled',
            'AD_STATUS_SUSPEND' => 'paused',
            'AD_STATUS_DELETE'  => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'       => 'platform_campaign_id',
            'cost'              => 'cost',
            'view_count'        => 'impressions',
            'valid_click_count' => 'clicks',
            'conversions_count' => 'conversions',
            'ctr'               => 'ctr',
            'cpm'               => 'cpm',
            'cpc'               => 'cpc',
            'cvr'               => 'cvr',
        ], [], function (array $unified): array {
            // Money is already in fen — no conversion needed
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
     * OAuth token request — no access-token auth, uses client credentials.
     */
    protected function request(string $method, string $url, array $params = []): array
    {
        $ch = curl_init();

        if (strtoupper($method) === 'GET') {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Tencent OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Tencent OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            throw new RuntimeException(
                'Tencent OAuth error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode)
            );
        }
        return $decoded;
    }

    /**
     * API request — access_token + nonce + timestamp in URL, JSON POST body.
     */
    protected function apiRequest(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url = $this->apiBaseUrl . $path;

        $authParams = [
            'access_token' => $accessToken ?? '',
            'timestamp'    => time(),
            'nonce'        => bin2hex(random_bytes(16)),
        ];
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($authParams);

        $ch = curl_init();
        $headers = ['Content-Type: application/json'];

        if (strtoupper($method) === 'GET') {
            $url .= '&' . http_build_query($params);
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
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Tencent API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Tencent API: invalid JSON response');
        }
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            $msg = $decoded['message'] ?? $decoded['message_cn'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Tencent API error: ' . $msg);
        }
        return $decoded;
    }
}
