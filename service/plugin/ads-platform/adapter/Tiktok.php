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

class Tiktok implements PlatformAdapter
{
    protected string $appId;
    protected string $secret;
    protected string $baseUrl = 'https://business-api.tiktok.com/open_api/v1.3/';
    protected string $authUrl = 'https://business-api.tiktok.com/portal/auth';

    public function __construct()
    {
        $this->appId  = env('TIKTOK_APP_ID', '');
        $this->secret = env('TIKTOK_SECRET', '');
    }

    // -------------------------------------------------------------------
    //  Identity
    // -------------------------------------------------------------------

    public function code(): string { return 'tiktok'; }

    public function name(): string { return 'TikTok Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    // -------------------------------------------------------------------
    //  OAuth2 — TikTok for Business
    //  Auth URL: https://business-api.tiktok.com/portal/auth
    // -------------------------------------------------------------------

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'app_id'       => $this->appId,
            'redirect_uri' => $redirectUri,
            'state'        => $state,
        ]);
        return $this->authUrl . '?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->oauthRequest('oauth2/access_token/', [
            'app_id'     => $this->appId,
            'secret'     => $this->secret,
            'auth_code'  => $code,
            'grant_type' => 'auth_code',
        ]);

        $data = $resp['data'] ?? [];
        return [
            'access_token'   => $data['access_token'] ?? '',
            'refresh_token'  => $data['refresh_token'] ?? '',
            'expires_in'     => (int) ($data['expires_in'] ?? 86400),
            'advertiser_ids' => $data['advertiser_ids'] ?? [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->oauthRequest('oauth2/refresh_token/', [
            'app_id'        => $this->appId,
            'secret'        => $this->secret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        $data = $resp['data'] ?? [];
        return [
            'access_token'  => $data['access_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? $refreshToken,
            'expires_in'    => (int) ($data['expires_in'] ?? 86400),
        ];
    }

    // -------------------------------------------------------------------
    //  Account
    // -------------------------------------------------------------------

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'advertiser/info/', [], $accessToken);
        $list = $resp['data']['list'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['advertiser_id'] ?? ''),
            'account_name'           => $item['advertiser_name'] ?? '',
        ], $list);
    }

    // -------------------------------------------------------------------
    //  Campaigns
    // -------------------------------------------------------------------

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'campaign/get/', [
                'advertiser_id' => (int) $accountId,
                'page'          => $page,
                'page_size'     => 100,
            ], $accessToken);
            $list = $resp['data']['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $page++;
        } while ($hasMore);
    }

    // -------------------------------------------------------------------
    //  AdGroups
    // -------------------------------------------------------------------

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adgroupFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'adgroup/get/', [
                'advertiser_id' => (int) $accountId,
                'campaign_id'   => $campaignId,
                'page'          => $page,
                'page_size'     => 100,
            ], $accessToken);
            $list = $resp['data']['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $page++;
        } while ($hasMore);
    }

    // -------------------------------------------------------------------
    //  Creatives
    // -------------------------------------------------------------------

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'creative/get/', [
                'advertiser_id' => (int) $accountId,
                'adgroup_id'    => $adGroupId,
                'page'          => $page,
                'page_size'     => 100,
            ], $accessToken);
            $list = $resp['data']['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $page++;
        } while ($hasMore);
    }

    // -------------------------------------------------------------------
    //  Reports — /report/integrated/get (sync paginated)
    // -------------------------------------------------------------------

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'report/integrated/get/', [
                'advertiser_id' => (int) $accountId,
                'start_date'    => $req->dateStart,
                'end_date'      => $req->dateEnd,
                'dimensions'    => json_encode($req->dimensions ?: ['campaign_id']),
                'metrics'       => json_encode(
                    $req->metrics ?: ['spend', 'impressions', 'clicks', 'conversion', 'ctr', 'cpm', 'cpc']
                ),
                'data_level'    => 'AUCTION_CAMPAIGN',
                'page'          => $page,
                'page_size'     => min($req->pageSize, 200),
            ], $accessToken);
            $list = $resp['data']['list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $page++;
        } while ($hasMore);
    }

    // -------------------------------------------------------------------
    //  Delivery operations
    // -------------------------------------------------------------------

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $params = [
            'advertiser_id' => (int) $accountId,
            'campaign_name' => $data->name,
            'budget_mode'   => 'BUDGET_MODE_DAY',
        ];
        if ($data->dailyBudget > 0) {
            // fen × 10000 = micro-dollars (same as Snapchat pattern)
            $params['budget'] = $data->dailyBudget * 10000;
        }
        $resp = $this->request('POST', 'campaign/create/', $params, $accessToken);
        return (string) ($resp['data']['campaign_id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $params = [
            'advertiser_id' => (int) $accountId,
            'campaign_id'   => $platformId,
            'campaign_name' => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            // fen × 10000 = micro-dollars
            $params['budget'] = $data->dailyBudget * 10000;
        }
        $this->request('POST', 'campaign/update/', $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('POST', 'campaign/status/update/', [
            'advertiser_id' => (int) $accountId,
            'campaign_ids'  => [$platformId],
            'opt_status'    => $enabled ? 'ENABLE' : 'DISABLE',
        ], $accessToken);
    }

    // -------------------------------------------------------------------
    //  Field mappings
    // -------------------------------------------------------------------

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'campaign_name' => 'name',
            'budget'        => 'daily_budget',
            'status'        => 'status',
        ], [
            'ENABLE' => 'enabled',
            'DISABLE' => 'paused',
            'DELETE' => 'deleted',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                // TikTok returns budget in micro-dollars; convert to fen (+10000)
                $unified['daily_budget'] = (int) round((float) $unified['daily_budget'] / 10000);
            }
            return $unified;
        });
    }

    protected function adgroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'adgroup_id'   => 'platform_ad_group_id',
            'adgroup_name' => 'name',
            'campaign_id'  => 'platform_campaign_id',
            'status'       => 'status',
        ], [
            'ENABLE' => 'enabled',
            'DISABLE' => 'paused',
            'DELETE' => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'creative_id' => 'platform_creative_id',
            'title'       => 'title',
            'status'      => 'status',
        ], [
            'ENABLE' => 'enabled',
            'DISABLE' => 'paused',
            'DELETE' => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id' => 'platform_campaign_id',
            'spend'       => 'cost',
            'impressions' => 'impressions',
            'clicks'      => 'clicks',
            'conversion'  => 'conversions',
            'ctr'         => 'ctr',
            'cpm'         => 'cpm',
            'cpc'         => 'cpc',
        ], [], function (array $unified): array {
            // TikTok returns money in micro-dollars; divide by 10000 for fen
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) round((float) $unified[$field] / 10000);
                }
            }
            // TikTok returns CTR/CVR as percentages (e.g. 5.0 = 5%);
            // divide by 100 to get decimal (same as Juliang)
            foreach (['ctr', 'cvr'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = round((float) $unified[$field] / 100, 6);
                }
            }
            return $unified;
        });
    }

    // -------------------------------------------------------------------
    //  HTTP layer
    //  TikTok uses Access-Token header (same pattern as Juliang / Ocean Engine)
    // -------------------------------------------------------------------

    /**
     * OAuth token request — form-encoded, no auth header.
     */
    protected function oauthRequest(string $path, array $params = []): array
    {
        $url = $this->baseUrl . $path;
        $headers = ['Content-Type: application/json'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
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
            throw new RuntimeException("TikTok Ads OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true) ?: [];
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            $msg = $decoded['message'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('TikTok Ads OAuth error: ' . $msg);
        }
        return $decoded;
    }

    /**
     * API request — Access-Token auth header (ByteDance-style, same as Juliang).
     */
    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url = $this->baseUrl . $path;
        $headers = ['Content-Type: application/json'];
        if ($accessToken) {
            $headers[] = 'Access-Token: ' . $accessToken;
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
            throw new RuntimeException("TikTok Ads API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            throw new RuntimeException(
                'TikTok Ads API error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode)
            );
        }
        return $decoded;
    }
}
