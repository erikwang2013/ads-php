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

class Meta implements PlatformAdapter
{
    protected string $appId;
    protected string $secret;
    protected string $baseUrl = 'https://graph.facebook.com/v19.0/';

    public function __construct()
    {
        $this->appId  = env('META_APP_ID', '');
        $this->secret = env('META_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string { return 'meta'; }

    public function name(): string { return 'Meta Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    // ── OAuth ─────────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id'     => $this->appId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'ads_management,ads_read,business_management',
            'response_type' => 'code',
        ]);
        return 'https://www.facebook.com/v19.0/dialog/oauth?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        // Step 1: exchange authorization code for short-lived token
        $url = 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
            'client_id'     => $this->appId,
            'client_secret' => $this->secret,
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Meta OAuth network error: ' . $error);
        }
        curl_close($ch);

        $data = json_decode($body, true);
        if (!isset($data['access_token'])) {
            throw new RuntimeException(
                'Meta OAuth error: ' . ($data['error']['message'] ?? 'unknown')
            );
        }

        // Step 2: exchange for long-lived token (60 day)
        $longUrl = 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $this->appId,
            'client_secret'     => $this->secret,
            'fb_exchange_token' => $data['access_token'],
        ]);

        $ch2 = curl_init($longUrl);
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $longBody = curl_exec($ch2);
        $longData = json_decode($longBody, true) ?: [];
        curl_close($ch2);

        $accessToken  = $longData['access_token'] ?? $data['access_token'];
        $expiresIn    = (int) ($longData['expires_in'] ?? 5184000);

        return [
            'access_token'   => $accessToken,
            'refresh_token'  => $accessToken,  // Meta: long-lived token is both access & refresh
            'expires_in'     => $expiresIn,
            'advertiser_ids' => [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $url = 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $this->appId,
            'client_secret'     => $this->secret,
            'fb_exchange_token' => $refreshToken,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Meta token refresh network error: ' . $error);
        }
        curl_close($ch);

        $data = json_decode($body, true) ?: [];
        $accessToken = $data['access_token'] ?? '';

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $accessToken,
            'expires_in'    => (int) ($data['expires_in'] ?? 5184000),
        ];
    }

    // ── Account ───────────────────────────────────────────────

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'me/adaccounts', [
            'fields' => 'id,name,account_status',
            'limit'  => 100,
        ], $accessToken);

        $list = $resp['data'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['id'] ?? ''),
            'account_name'           => $item['name'] ?? '',
        ], $list);
    }

    // ── Campaigns ─────────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $urlPath = $accountId . '/campaigns';
        $after    = null;

        do {
            $params = [
                'fields' => 'id,name,status,daily_budget,lifetime_budget,objective,created_time',
                'limit'  => 100,
            ];
            if ($after) {
                $params['after'] = $after;
            }
            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $after = $resp['paging']['cursors']['after'] ?? null;
        } while ($after);
    }

    // ── AdGroups (AdSets in Meta) ─────────────────────────────

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adgroupFieldMapping();
        $urlPath = $accountId . '/adsets';
        $after    = null;

        do {
            $params = [
                'fields' => 'id,name,status,campaign_id,daily_budget,optimization_goal',
                'limit'  => 100,
            ];
            if ($after) {
                $params['after'] = $after;
            }
            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $list = $resp['data'] ?? [];
            // Filter by campaign_id if provided (Meta doesn't natively filter adsets by campaign)
            foreach ($list as $row) {
                if ($campaignId && ($row['campaign_id'] ?? '') !== $campaignId) {
                    continue;
                }
                yield $mapping->map($row);
            }
            $after = $resp['paging']['cursors']['after'] ?? null;
        } while ($after);
    }

    // ── Creatives (Ads in Meta) ───────────────────────────────

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $urlPath = $accountId . '/ads';
        $after    = null;

        do {
            $params = [
                'fields' => 'id,name,status,adset_id,creative{title,body,image_url,video_id}',
                'limit'  => 100,
            ];
            if ($after) {
                $params['after'] = $after;
            }
            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                if ($adGroupId && ($row['adset_id'] ?? '') !== $adGroupId) {
                    continue;
                }
                yield $mapping->map($row);
            }
            $after = $resp['paging']['cursors']['after'] ?? null;
        } while ($after);
    }

    // ── Reports (Insights) ────────────────────────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $urlPath = $accountId . '/insights';
        $after    = null;

        do {
            $params = [
                'fields'          => 'campaign_id,campaign_name,impressions,clicks,spend,ctr,cpm,cpc,actions,conversions',
                'time_range'      => json_encode([
                    'since' => $req->dateStart,
                    'until' => $req->dateEnd,
                ]),
                'level'           => 'campaign',
                'time_increment'  => $req->granularity === 'daily' ? 1 : 'all_days',
                'limit'           => min($req->pageSize, 500),
            ];
            if ($after) {
                $params['after'] = $after;
            }
            $resp = $this->request('GET', $urlPath, $params, $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $after = $resp['paging']['cursors']['after'] ?? null;
        } while ($after);
    }

    // ── Delivery operations ───────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $params = [
            'name'                => $data->name,
            'objective'           => $data->extra['objective'] ?? 'OUTCOME_TRAFFIC',
            'status'              => 'PAUSED',
            'special_ad_categories' => $data->extra['special_ad_categories'] ?? [],
        ];

        if ($data->dailyBudget > 0) {
            // Meta budget in cents; fen == cents, no conversion needed
            $params['daily_budget'] = $data->dailyBudget;
        }

        $resp = $this->request('POST', $accountId . '/campaigns', $params, $accessToken);
        return (string) ($resp['id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $params = ['name' => $data->name];
        if ($data->dailyBudget > 0) {
            $params['daily_budget'] = $data->dailyBudget;
        }
        $this->request('POST', $platformId, $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('POST', $platformId, [
            'status' => $enabled ? 'ACTIVE' : 'PAUSED',
        ], $accessToken);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'            => 'platform_campaign_id',
            'name'          => 'name',
            'daily_budget'  => 'daily_budget',
            'status'        => 'status',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'   => 'paused',
            'DELETED'  => 'deleted',
            'ARCHIVED' => 'deleted',
        ], function (array $unified): array {
            // Meta returns budget in cents; fen == cents, no conversion
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) $unified['daily_budget'];
            }
            return $unified;
        });
    }

    protected function adgroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'          => 'platform_ad_group_id',
            'name'        => 'name',
            'campaign_id' => 'platform_campaign_id',
            'status'      => 'status',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'   => 'paused',
            'DELETED'  => 'deleted',
            'ARCHIVED' => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'          => 'platform_creative_id',
            'name'        => 'title',
            'status'      => 'status',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'   => 'paused',
            'DELETED'  => 'deleted',
            'ARCHIVED' => 'deleted',
        ], function (array $unified): array {
            // Flatten nested creative data
            if (isset($unified['extra']['creative']['title'])) {
                $unified['title'] = $unified['extra']['creative']['title'];
            }
            return $unified;
        });
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'campaign_name' => 'campaign_name',
            'spend'         => 'cost',
            'impressions'   => 'impressions',
            'clicks'        => 'clicks',
            'ctr'           => 'ctr',
            'cpm'           => 'cpm',
            'cpc'           => 'cpc',
        ], [], function (array $unified): array {
            // Meta returns spend/cpm/cpc in cents; fen == cents, no conversion
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) $unified[$field];
                }
            }
            // Meta CTR is already a decimal ratio (e.g. 0.05 = 5%)
            foreach (['ctr', 'cvr'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = round((float) $unified[$field], 6);
                }
            }
            // Extract conversions from actions array
            if (isset($unified['extra']['actions'])) {
                foreach ($unified['extra']['actions'] as $action) {
                    if (($action['action_type'] ?? '') === 'offsite_conversion') {
                        $unified['conversions'] = (int) ($action['value'] ?? 0);
                        break;
                    }
                }
            }
            return $unified;
        });
    }

    // ── HTTP layer ────────────────────────────────────────────

    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url = $this->baseUrl . $path;

        // Meta auth: access_token as URL query parameter on every request
        if ($accessToken) {
            $params['access_token'] = $accessToken;
        }

        $headers = ['Content-Type: application/json'];
        $ch = curl_init();

        if (strtoupper($method) === 'GET') {
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
            throw new RuntimeException("Meta API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Meta API: invalid JSON response');
        }
        if ($httpCode >= 400 || isset($decoded['error'])) {
            $msg = $decoded['error']['message'] ?? $decoded['error']['error_user_msg'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Meta API error: ' . $msg);
        }
        return $decoded;
    }
}
