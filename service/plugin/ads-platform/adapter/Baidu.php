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

class Baidu implements PlatformAdapter
{
    protected string $appId;
    protected string $secret;
    protected string $authBaseUrl = 'https://u.baidu.com/oauth/';
    protected string $apiBaseUrl  = 'https://api.baidu.com/';

    public function __construct()
    {
        $this->appId  = env('BAIDU_APP_ID', '');
        $this->secret = env('BAIDU_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string { return 'baidu'; }

    public function name(): string { return '百度营销'; }

    public function capabilities(): array
    {
        return ['report', 'campaign', 'adgroup', 'creative', 'oauth'];
    }

    // ── OAuth ─────────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'app_id'        => $this->appId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'basic,campaign,report',
            'response_type' => 'code',
        ]);
        return $this->authBaseUrl . 'authorize?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request('POST', $this->authBaseUrl . 'token', [
            'app_id'      => $this->appId,
            'secret'      => $this->secret,
            'code'        => $code,
            'redirect_uri'=> $redirectUri,
            'grant_type'  => 'authorization_code',
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
        $resp = $this->request('POST', $this->authBaseUrl . 'token', [
            'app_id'        => $this->appId,
            'secret'        => $this->secret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
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
        $resp = $this->signedRequest('GET', 'account/info', [], $accessToken);
        $body = $resp['body'] ?? [];
        $data = $body['data'] ?? [];
        $list = $data['list'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['accountId'] ?? ''),
            'account_name'           => $item['accountName'] ?? '',
        ], $list);
    }

    // ── Campaigns ─────────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping  = $this->campaignFieldMapping();
        $pageNum  = 0;
        do {
            $resp = $this->signedRequest('POST', 'campaign/search', [
                'pageNum'  => $pageNum,
                'pageSize' => 100,
            ], $accessToken);
            $body = $resp['body'] ?? [];
            $data = $body['data'] ?? [];
            $list = $data['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $pageNum++;
        } while ($hasMore);
    }

    // ── AdGroups ──────────────────────────────────────────────

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adgroupFieldMapping();
        $pageNum = 0;
        do {
            $resp = $this->signedRequest('POST', 'adgroup/search', [
                'campaignId' => $campaignId,
                'pageNum'    => $pageNum,
                'pageSize'   => 100,
            ], $accessToken);
            $body = $resp['body'] ?? [];
            $data = $body['data'] ?? [];
            $list = $data['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $pageNum++;
        } while ($hasMore);
    }

    // ── Creatives ─────────────────────────────────────────────

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $pageNum = 0;
        do {
            $resp = $this->signedRequest('POST', 'creative/search', [
                'adGroupId' => $adGroupId,
                'pageNum'   => $pageNum,
                'pageSize'  => 100,
            ], $accessToken);
            $body = $resp['body'] ?? [];
            $data = $body['data'] ?? [];
            $list = $data['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $pageNum++;
        } while ($hasMore);
    }

    // ── Reports (async: create → poll → fetch) ────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();

        // Step 1 – create report job
        $createResp = $this->signedRequest('POST', 'report/create', [
            'startDate'   => $req->dateStart,
            'endDate'     => $req->dateEnd,
            'granularity' => strtoupper($req->granularity),
            'dimensions'  => $req->dimensions ?: ['campaignId'],
            'metrics'     => $req->metrics ?: ['cost', 'impressions', 'clicks', 'conversions'],
        ], $accessToken);
        $reportId = $createResp['body']['data']['reportId'] ?? '';
        if (!$reportId) {
            throw new RuntimeException('Baidu report: failed to create report job');
        }

        // Step 2 – poll until completed (max 60 s)
        $maxRetries = 30;
        $status     = 'running';
        while ($maxRetries > 0 && $status !== 'completed') {
            sleep(2);
            $statusResp = $this->signedRequest('GET', 'report/status', [
                'reportId' => $reportId,
            ], $accessToken);
            $status = $statusResp['body']['data']['status'] ?? 'failed';
            if ($status === 'failed') {
                throw new RuntimeException('Baidu report: report job failed');
            }
            $maxRetries--;
        }
        if ($status !== 'completed') {
            throw new RuntimeException('Baidu report: report job timed out');
        }

        // Step 3 – pull data (paginated)
        $pageNum = 0;
        do {
            $dataResp = $this->signedRequest('POST', 'report/data', [
                'reportId' => $reportId,
                'pageNum'  => $pageNum,
                'pageSize' => min($req->pageSize, 200),
            ], $accessToken);
            $body = $dataResp['body'] ?? [];
            $data = $body['data'] ?? [];
            $list = $data['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $pageNum++;
        } while ($hasMore);
    }

    // ── Delivery operations ───────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->signedRequest('POST', 'campaign/create', [
            'campaignName' => $data->name,
            'dailyBudget'  => $data->dailyBudget / 100,   // fen → yuan
        ], $accessToken);
        return (string) ($resp['body']['data']['campaignId'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $params = [
            'campaignId'   => $platformId,
            'campaignName' => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $params['dailyBudget'] = $data->dailyBudget / 100;
        }
        $this->signedRequest('POST', 'campaign/update', $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        // Baidu status: 1 = enabled, 2 = paused
        $this->signedRequest('POST', 'campaign/toggle', [
            'campaignId' => $platformId,
            'status'     => $enabled ? 1 : 2,
        ], $accessToken);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaignId'   => 'platform_campaign_id',
            'campaignName' => 'name',
            'budget'       => 'daily_budget',
            'status'       => 'status',
        ], [
            1 => 'enabled',
            2 => 'paused',
            3 => 'deleted',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                // API returns yuan, store as fen (×100)
                $unified['daily_budget'] = (int) ($unified['daily_budget'] * 100);
            }
            return $unified;
        });
    }

    protected function adgroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'adGroupId'   => 'platform_ad_group_id',
            'adGroupName' => 'name',
            'campaignId'  => 'platform_campaign_id',
            'status'      => 'status',
        ], [
            1 => 'enabled',
            2 => 'paused',
            3 => 'deleted',
        ], function (array $unified): array {
            return $unified;
        });
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'creativeId' => 'platform_creative_id',
            'title'      => 'title',
            'status'     => 'status',
        ], [
            1 => 'enabled',
            2 => 'paused',
            3 => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaignId'  => 'platform_campaign_id',
            'cost'        => 'cost',
            'impressions' => 'impressions',
            'clicks'      => 'clicks',
            'conversions' => 'conversions',
            'ctr'         => 'ctr',
            'cpm'         => 'cpm',
            'cpc'         => 'cpc',
            'cvr'         => 'cvr',
        ], [], function (array $unified): array {
            // API returns money in yuan; store as fen (×100)
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
     * OAuth request — plain POST body, no authentication header.
     */
    protected function request(string $method, string $url, array $params = []): array
    {
        $ch = curl_init();

        if (strtoupper($method) === 'GET') {
            $url .= '?' . http_build_query($params);
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
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Baidu OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Baidu OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || (isset($decoded['error']) && $decoded['error'])) {
            $desc = $decoded['error_description'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Baidu OAuth error: ' . $desc);
        }
        return $decoded;
    }

    /**
     * API request — wraps params in Baidu's {header, body} envelope.
     */
    protected function signedRequest(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url = $this->apiBaseUrl . $path;

        $envelope = [
            'header' => [
                'accessToken' => $accessToken ?? '',
                'timestamp'   => (string) time(),
            ],
            'body' => $params,
        ];

        $ch = curl_init();

        if (strtoupper($method) === 'GET') {
            $url .= '?' . http_build_query(['body' => json_encode($params, JSON_UNESCAPED_UNICODE)]);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($envelope, JSON_UNESCAPED_UNICODE));
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Baidu API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Baidu API: invalid JSON response');
        }

        // Baidu error envelope: {header: {errorCode: ...}, body: {}}
        $header = $decoded['header'] ?? [];
        $errorCode = $header['errorCode'] ?? 0;
        if ($httpCode !== 200 || $errorCode !== 0) {
            $errorMsg = $header['errorMsg'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Baidu API error [code ' . $errorCode . ']: ' . $errorMsg);
        }
        return $decoded;
    }
}
