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

class Linkedin implements PlatformAdapter
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl = 'https://api.linkedin.com/v2/';

    public function __construct()
    {
        $this->clientId     = env('LINKEDIN_CLIENT_ID', '');
        $this->clientSecret = env('LINKEDIN_CLIENT_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string { return 'linkedin'; }

    public function name(): string { return 'LinkedIn Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    // ── OAuth ─────────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'r_ads_reporting r_ads rw_ads',
        ]);
        return 'https://www.linkedin.com/oauth/v2/authorization?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->oauthRequest('POST', 'https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        return [
            'access_token'   => $resp['access_token'] ?? '',
            'refresh_token'  => $resp['refresh_token'] ?? '',
            'expires_in'     => (int) ($resp['expires_in'] ?? 86400),
            'advertiser_ids' => [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->oauthRequest('POST', 'https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
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
        $resp = $this->request('GET', 'adAccountsV2', [
            'q'                      => 'search',
            'search.type.values[0]' => 'ENTERPRISE',
            'sort.field'             => 'ID',
            'sort.order'             => 'ASCENDING',
            'count'                  => 100,
        ], $accessToken);

        $elements = $resp['elements'] ?? [];
        $accounts = [];
        foreach ($elements as $item) {
            $urn = $item['id'] ?? '';
            // Extract numeric ID from URN (urn:li:sponsoredAccount:123456)
            $numericId = $this->extractUrnId($urn);
            $accounts[] = [
                'account_id_on_platform' => (string) $numericId,
                'account_name'           => $item['name'] ?? '',
            ];
        }
        return $accounts;
    }

    // ── Campaigns ─────────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $accountUrn = $this->toAccountUrn($accountId);
        $start = 0;
        $count  = 100;

        do {
            $resp = $this->request('GET', 'adCampaignsV2', [
                'q'                           => 'search',
                'search.account.values[0]'    => $accountUrn,
                'start'                       => $start,
                'count'                       => $count,
                'sort.field'                  => 'ID',
                'sort.order'                  => 'ASCENDING',
            ], $accessToken);

            $elements = $resp['elements'] ?? [];
            foreach ($elements as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($elements);
            $start += $count;
        } while ($hasMore);
    }

    // ── AdGroups ──────────────────────────────────────────────

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adgroupFieldMapping();
        $accountUrn = $this->toAccountUrn($accountId);
        $start = 0;
        $count  = 100;

        do {
            $resp = $this->request('GET', 'adCampaignGroupsV2', [
                'q'                           => 'search',
                'search.account.values[0]'    => $accountUrn,
                'start'                       => $start,
                'count'                       => $count,
            ], $accessToken);

            $elements = $resp['elements'] ?? [];
            foreach ($elements as $row) {
                if ($campaignId && ($this->extractUrnId($row['campaign'] ?? '') !== $campaignId)) {
                    continue;
                }
                yield $mapping->map($row);
            }
            $hasMore = !empty($elements);
            $start += $count;
        } while ($hasMore);
    }

    // ── Creatives ─────────────────────────────────────────────

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $accountUrn = $this->toAccountUrn($accountId);
        $start = 0;
        $count  = 100;

        do {
            $resp = $this->request('GET', 'adCreativesV2', [
                'q'                           => 'search',
                'search.account.values[0]'    => $accountUrn,
                'start'                       => $start,
                'count'                       => $count,
            ], $accessToken);

            $elements = $resp['elements'] ?? [];
            foreach ($elements as $row) {
                if ($adGroupId && ($this->extractUrnId($row['campaignGroup'] ?? '') !== $adGroupId)) {
                    continue;
                }
                yield $mapping->map($row);
            }
            $hasMore = !empty($elements);
            $start += $count;
        } while ($hasMore);
    }

    // ── Reports (Analytics) ───────────────────────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $accountUrn = $this->toAccountUrn($accountId);

        // Build date range: LinkedIn requires explicit year/month/day breakdown
        $dateStart = explode('-', $req->dateStart);
        $dateEnd   = explode('-', $req->dateEnd);

        $params = [
            'q'                        => 'analytics',
            'pivot'                    => 'CAMPAIGN',
            'dateRange.start.year'     => (int) ($dateStart[0] ?? date('Y')),
            'dateRange.start.month'    => (int) ($dateStart[1] ?? 1),
            'dateRange.start.day'      => (int) ($dateStart[2] ?? 1),
            'dateRange.end.year'       => (int) ($dateEnd[0] ?? date('Y')),
            'dateRange.end.month'      => (int) ($dateEnd[1] ?? 12),
            'dateRange.end.day'        => (int) ($dateEnd[2] ?? 31),
            'accounts[0]'              => $accountUrn,
            'fields'                   => 'campaignGroupId,campaignGroupName,costInLocalCurrency,impressions,clicks,conversions,ctr,cpm,cpc,cvr',
            'timeGranularity'          => $req->granularity === 'daily' ? 'DAILY' : 'ALL',
            'count'                    => min($req->pageSize, 100),
        ];

        $start = 0;
        do {
            $params['start'] = $start;
            $resp = $this->request('GET', 'adAnalyticsV2', $params, $accessToken);

            $elements = $resp['elements'] ?? [];
            foreach ($elements as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($elements);
            $start += $params['count'];
        } while ($hasMore);
    }

    // ── Delivery operations ───────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $accountUrn = $this->toAccountUrn($accountId);
        $body = [
            'account' => $accountUrn,
            'name'    => $data->name,
            'status'  => 'PAUSED',
        ];

        if ($data->dailyBudget > 0) {
            // fen × 10000 = micro-dollars
            $body['dailyBudget'] = [
                'amount'       => (string) ($data->dailyBudget * 10000),
                'currencyCode' => $data->extra['currency'] ?? 'USD',
            ];
        }
        if ($data->totalBudget) {
            $body['totalBudget'] = [
                'amount'       => (string) ($data->totalBudget * 10000),
                'currencyCode' => $data->extra['currency'] ?? 'USD',
            ];
        }

        $resp = $this->request('POST', 'adCampaignsV2', $body, $accessToken);
        return $this->extractUrnId($resp['id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $body = ['name' => $data->name];
        if ($data->dailyBudget > 0) {
            $body['dailyBudget'] = [
                'amount'       => (string) ($data->dailyBudget * 10000),
                'currencyCode' => $data->extra['currency'] ?? 'USD',
            ];
        }
        $this->request('POST', 'adCampaignsV2/' . $this->toCampaignUrn($platformId), $body, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('POST', 'adCampaignsV2/' . $this->toCampaignUrn($platformId), [
            'status' => $enabled ? 'ACTIVE' : 'PAUSED',
        ], $accessToken);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'   => 'platform_campaign_id',
            'name' => 'name',
            'status' => 'status',
        ], [
            'ACTIVE'    => 'enabled',
            'PAUSED'    => 'paused',
            'CANCELED'  => 'deleted',
            'ARCHIVED'  => 'deleted',
            'DRAFT'     => 'paused',
        ], function (array $unified): array {
            // Extract numeric ID from URN
            if (isset($unified['platform_campaign_id'])) {
                $unified['platform_campaign_id'] = $this->extractUrnId($unified['platform_campaign_id']);
            }
            // Extract daily_budget.amount from nested structure; convert micro$ → fen (÷10000)
            if (isset($unified['extra']['dailyBudget']['amount'])) {
                $unified['daily_budget'] = (int) (((int) $unified['extra']['dailyBudget']['amount']) / 10000);
                unset($unified['extra']['dailyBudget']);
            }
            return $unified;
        });
    }

    protected function adgroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'     => 'platform_ad_group_id',
            'name'   => 'name',
            'status' => 'status',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'   => 'paused',
            'ARCHIVED' => 'deleted',
        ], function (array $unified): array {
            if (isset($unified['platform_ad_group_id'])) {
                $unified['platform_ad_group_id'] = $this->extractUrnId($unified['platform_ad_group_id']);
            }
            return $unified;
        });
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'     => 'platform_creative_id',
            'status' => 'status',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'   => 'paused',
            'ARCHIVED' => 'deleted',
            'DRAFT'    => 'paused',
        ], function (array $unified): array {
            if (isset($unified['platform_creative_id'])) {
                $unified['platform_creative_id'] = $this->extractUrnId($unified['platform_creative_id']);
            }
            // Extract title from nested creative content
            if (isset($unified['extra']['content']['title'])) {
                $unified['title'] = $unified['extra']['content']['title'];
            }
            return $unified;
        });
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaignGroupId'   => 'platform_campaign_id',
            'campaignGroupName' => 'campaign_name',
            'costInLocalCurrency' => 'cost',
            'impressions'       => 'impressions',
            'clicks'            => 'clicks',
            'conversions'       => 'conversions',
            'ctr'               => 'ctr',
            'cpm'               => 'cpm',
            'cpc'               => 'cpc',
            'cvr'               => 'cvr',
        ], [], function (array $unified): array {
            // LinkedIn returns money in micro-dollars; convert to fen (÷10000)
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) round((float) $unified[$field] / 10000);
                }
            }
            // LinkedIn CTR/CVR are typically decimals; ensure consistent precision
            foreach (['ctr', 'cvr'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = round((float) $unified[$field], 6);
                }
            }
            // Extract numeric ID from URN
            if (isset($unified['platform_campaign_id'])) {
                $unified['platform_campaign_id'] = $this->extractUrnId($unified['platform_campaign_id']);
            }
            return $unified;
        });
    }

    // ── HTTP layer ────────────────────────────────────────────

    /**
     * OAuth request to token endpoint — form-encoded, no auth header.
     */
    protected function oauthRequest(string $method, string $url, array $params = []): array
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
            throw new RuntimeException("LinkedIn OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('LinkedIn OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $desc = $decoded['error_description'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('LinkedIn OAuth error: ' . $desc);
        }
        return $decoded;
    }

    /**
     * API request — Bearer auth + X-Restli-Protocol-Version header.
     */
    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url = $this->baseUrl . $path;

        $headers = [
            'Content-Type: application/json',
            'X-Restli-Protocol-Version: 2.0.0',
            'LinkedIn-Version: 202405',
        ];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $ch = curl_init();
        if (strtoupper($method) === 'GET') {
            $url .= '?' . http_build_query($params);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
            // LinkedIn partial update uses custom HTTP method via X-HTTP-Method-Override
            // but standard POST to entity endpoint works for partial updates
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
            throw new RuntimeException("LinkedIn API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('LinkedIn API: invalid JSON response');
        }
        if ($httpCode >= 400 || isset($decoded['errorDetail']) || isset($decoded['message'])) {
            $msg = $decoded['errorDetail']['message'] ?? $decoded['message'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('LinkedIn API error: ' . $msg);
        }
        return $decoded;
    }

    // ── URN helpers ───────────────────────────────────────────

    protected function toAccountUrn(string $accountId): string
    {
        if (str_starts_with($accountId, 'urn:li:sponsoredAccount:')) {
            return $accountId;
        }
        return 'urn:li:sponsoredAccount:' . $accountId;
    }

    protected function toCampaignUrn(string $campaignId): string
    {
        if (str_starts_with($campaignId, 'urn:li:sponsoredCampaign:')) {
            return $campaignId;
        }
        return 'urn:li:sponsoredCampaign:' . $campaignId;
    }

    protected function extractUrnId(string $urn): string
    {
        $parts = explode(':', $urn);
        return end($parts);
    }
}
