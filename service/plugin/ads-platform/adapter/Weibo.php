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

class Weibo implements PlatformAdapter
{
    protected string $appId;
    protected string $secret;
    protected string $baseUrl = 'https://api.weibo.com/v2/';
    protected string $authBaseUrl = 'https://api.weibo.com/oauth2/';

    public function __construct()
    {
        $this->appId  = env('WEIBO_APP_ID', '');
        $this->secret = env('WEIBO_SECRET', '');
    }

    public function code(): string { return 'weibo'; }

    public function name(): string { return '微博粉丝通'; }

    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    // ── OAuth ──────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id'     => $this->appId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'response_type' => 'code',
            'scope'         => 'all',
        ]);
        return $this->authBaseUrl . 'authorize?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request('POST', 'access_token', [
            'client_id'     => $this->appId,
            'client_secret' => $this->secret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ], null, $this->authBaseUrl);
        $data = $resp['data'] ?? $resp;
        return [
            'access_token'   => $data['access_token'] ?? '',
            'refresh_token'  => $data['refresh_token'] ?? '',
            'expires_in'     => $data['expires_in'] ?? 86400,
            'advertiser_ids' => $data['advertiser_ids'] ?? [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->request('POST', 'access_token', [
            'client_id'     => $this->appId,
            'client_secret' => $this->secret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ], null, $this->authBaseUrl);
        $data = $resp['data'] ?? $resp;
        return [
            'access_token'  => $data['access_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? '',
            'expires_in'    => $data['expires_in'] ?? 86400,
        ];
    }

    // ── Account ────────────────────────────────────────────

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'account/info', [], $accessToken);
        $data = $resp['data'] ?? [];
        return [
            [
                'account_id_on_platform' => (string) ($data['account_id'] ?? ''),
                'account_name'           => $data['account_name'] ?? '',
            ],
        ];
    }

    // ── Campaign ───────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'campaigns/list', [
                'page'      => $page,
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

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        yield from [];
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'creatives/list', [
                'page'      => $page,
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

    // ── Report ─────────────────────────────────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'reports/campaign', [
                'start_date'  => $req->dateStart,
                'end_date'    => $req->dateEnd,
                'granularity' => strtoupper($req->granularity),
                'page'        => $page,
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

    // ── Mutations ──────────────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->request('POST', 'campaigns/create', [
            'name'         => $data->name,
            'daily_budget' => $data->dailyBudget,
        ], $accessToken);
        return (string) ($resp['data']['campaign_id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $params = [
            'campaign_id' => $platformId,
            'name'        => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $params['daily_budget'] = $data->dailyBudget;
        }
        $this->request('POST', 'campaigns/update', $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('POST', 'campaigns/status/update', [
            'campaign_id' => $platformId,
            'status'      => $enabled ? 1 : 0,
        ], $accessToken);
    }

    // ── Field Mappings ─────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'  => 'platform_campaign_id',
            'name'         => 'name',
            'daily_budget' => 'daily_budget',
            'status'       => 'status',
        ], [
            1 => 'enabled',
            0 => 'paused',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'creative_id' => 'platform_creative_id',
            'title'       => 'title',
            'status'      => 'status',
        ], [
            1 => 'enabled',
            0 => 'paused',
        ]);
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
        ], []);
    }

    // ── HTTP Client ────────────────────────────────────────

    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null, ?string $overrideBase = null): array
    {
        $base = $overrideBase ?? $this->baseUrl;
        $url = $base . $path;

        $headers = ['Content-Type: application/json'];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $ch = curl_init();
        if ($method === 'GET') {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
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
        $errno = curl_errno($ch);
        if ($errno !== 0) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Weibo API network error: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            throw new RuntimeException(
                'Weibo API error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode)
            );
        }
        return $decoded;
    }
}
