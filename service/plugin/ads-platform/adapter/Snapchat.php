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

class Snapchat implements PlatformAdapter
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl = 'https://adsapi.snapchat.com/v1/';

    public function __construct()
    {
        $this->clientId     = env('SNAPCHAT_CLIENT_ID', '');
        $this->clientSecret = env('SNAPCHAT_CLIENT_SECRET', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string { return 'snapchat'; }

    public function name(): string { return 'Snapchat Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    // ── OAuth ─────────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'snapchat-marketing-api',
        ]);
        return 'https://accounts.snapchat.com/login/oauth2/authorize?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->oauthRequest('POST', 'https://accounts.snapchat.com/login/oauth2/access_token', [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        return [
            'access_token'   => $resp['access_token'] ?? '',
            'refresh_token'  => $resp['refresh_token'] ?? '',
            'expires_in'     => (int) ($resp['expires_in'] ?? 3600),
            'advertiser_ids' => [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->oauthRequest('POST', 'https://accounts.snapchat.com/login/oauth2/access_token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        return [
            'access_token'  => $resp['access_token'] ?? '',
            'refresh_token' => $resp['refresh_token'] ?? '',
            'expires_in'    => (int) ($resp['expires_in'] ?? 3600),
        ];
    }

    // ── Account ───────────────────────────────────────────────

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'me/organizations', [], $accessToken);
        $orgs = $resp['organizations'] ?? [];
        $accounts = [];
        foreach ($orgs as $org) {
            $orgId = $org['organization']['id'] ?? '';
            // Fetch ad accounts per organization
            try {
                $adResp = $this->request('GET', "organizations/{$orgId}/adaccounts", [], $accessToken);
                $adList = $adResp['adaccounts'] ?? [];
                foreach ($adList as $adAcct) {
                    $accounts[] = [
                        'account_id_on_platform' => (string) ($adAcct['adaccount']['id'] ?? ''),
                        'account_name'           => $adAcct['adaccount']['name'] ?? '',
                    ];
                }
            } catch (\RuntimeException $e) {
                // Skip unreachable orgs
                continue;
            }
        }
        return $accounts;
    }

    // ── Campaigns ─────────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $urlPath = "adaccounts/{$accountId}/campaigns";

        $resp = $this->request('GET', $urlPath, [], $accessToken);
        $list = $resp['campaigns'] ?? [];

        foreach ($list as $row) {
            $campaign = $row['campaign'] ?? $row;
            yield $mapping->map($campaign);
        }
    }

    // ── AdGroups (Ad Squads in Snapchat) ──────────────────────

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adgroupFieldMapping();
        $urlPath = "adaccounts/{$accountId}/adsquads";

        $resp = $this->request('GET', $urlPath, [], $accessToken);
        $list = $resp['adsquads'] ?? [];

        foreach ($list as $row) {
            $adsquad = $row['adsquad'] ?? $row;
            if ($campaignId && ($adsquad['campaign_id'] ?? '') !== $campaignId) {
                continue;
            }
            yield $mapping->map($adsquad);
        }
    }

    // ── Creatives (Ads in Snapchat) ───────────────────────────

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $urlPath = "adaccounts/{$accountId}/ads";

        $resp = $this->request('GET', $urlPath, [], $accessToken);
        $list = $resp['ads'] ?? [];

        foreach ($list as $row) {
            $ad = $row['ad'] ?? $row;
            if ($adGroupId && ($ad['ad_squad_id'] ?? '') !== $adGroupId) {
                continue;
            }
            yield $mapping->map($ad);
        }
    }

    // ── Reports ───────────────────────────────────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();

        $body = [
            'granularity' => strtoupper($req->granularity),
            'dimensions'  => $req->dimensions ?: ['campaign'],
            'metrics'     => $req->metrics ?: ['impressions', 'clicks', 'spend', 'conversions', 'ctr', 'cpm', 'cpc'],
            'start_time'  => $req->dateStart . 'T00:00:00.000Z',
            'end_time'    => $req->dateEnd . 'T23:59:59.999Z',
        ];

        $resp = $this->request('POST', "adaccounts/{$accountId}/stats", $body, $accessToken);
        $items = $resp['items'] ?? [];

        // Snapchat stats may be paginated or returned as one response
        foreach ($items as $row) {
            $flatRow = array_merge(
                $row['dimension_stats'][0] ?? [],
                $row['total_stats'] ?? [],
                $row['dimension_stats'][0] ?? []
            );
            yield $mapping->map($flatRow);
        }
    }

    // ── Delivery operations ───────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $body = [
            'campaigns' => [[
                'name'           => $data->name,
                'status'         => 'PAUSED',
                'start_time'     => $data->startDate ? ($data->startDate . 'T00:00:00.000Z') : null,
                'end_time'       => $data->endDate ? ($data->endDate . 'T23:59:59.999Z') : null,
            ]],
        ];

        if ($data->dailyBudget > 0) {
            // fen × 10000 = micro-dollars
            $body['campaigns'][0]['daily_budget_micro'] = $data->dailyBudget * 10000;
        }

        $resp = $this->request('POST', "adaccounts/{$accountId}/campaigns", $body, $accessToken);
        $created = $resp['campaigns'][0]['campaign'] ?? $resp['campaigns'][0] ?? [];
        return (string) ($created['id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $body = [
            'campaigns' => [[
                'id'   => $platformId,
                'name' => $data->name,
            ]],
        ];
        if ($data->dailyBudget > 0) {
            $body['campaigns'][0]['daily_budget_micro'] = $data->dailyBudget * 10000;
        }
        $this->request('PUT', "adaccounts/{$accountId}/campaigns", $body, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('PUT', "adaccounts/{$accountId}/campaigns", [
            'campaigns' => [[
                'id'     => $platformId,
                'status' => $enabled ? 'ACTIVE' : 'PAUSED',
            ]],
        ], $accessToken);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'                  => 'platform_campaign_id',
            'name'                => 'name',
            'daily_budget_micro'  => 'daily_budget',
            'status'              => 'status',
        ], [
            'ACTIVE'  => 'enabled',
            'PAUSED'  => 'paused',
            'DELETED' => 'deleted',
        ], function (array $unified): array {
            // Convert micro-dollars to fen (÷10000)
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) round((float) $unified['daily_budget'] / 10000);
            }
            return $unified;
        });
    }

    protected function adgroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'            => 'platform_ad_group_id',
            'name'          => 'name',
            'campaign_id'   => 'platform_campaign_id',
            'status'        => 'status',
        ], [
            'ACTIVE'  => 'enabled',
            'PAUSED'  => 'paused',
            'DELETED' => 'deleted',
        ]);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'           => 'platform_creative_id',
            'name'         => 'title',
            'ad_squad_id'  => 'platform_ad_group_id',
            'status'       => 'status',
        ], [
            'ACTIVE'   => 'enabled',
            'PAUSED'   => 'paused',
            'DELETED'  => 'deleted',
            'ARCHIVED' => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'spend'         => 'cost',
            'impressions'   => 'impressions',
            'clicks'        => 'clicks',
            'conversions'   => 'conversions',
            'ctr'           => 'ctr',
            'cpm'           => 'cpm',
            'cpc'           => 'cpc',
        ], [], function (array $unified): array {
            // Snapchat returns money in micro-dollars; convert to fen (÷10000)
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) round((float) $unified[$field] / 10000);
                }
            }
            // CTR/CVR are typically decimals; ensure consistent precision
            foreach (['ctr', 'cvr'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = round((float) $unified[$field], 6);
                }
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
            throw new RuntimeException("Snapchat OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Snapchat OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $desc = $decoded['error_description'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Snapchat OAuth error: ' . $desc);
        }
        return $decoded;
    }

    /**
     * API request — Bearer auth.
     */
    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url = $this->baseUrl . $path;

        $headers = ['Content-Type: application/json'];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $ch = curl_init();
        if (strtoupper($method) === 'GET') {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        } elseif (strtoupper($method) === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
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
            throw new RuntimeException("Snapchat API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Snapchat API: invalid JSON response');
        }
        if ($httpCode >= 400 || (isset($decoded['request_status']) && $decoded['request_status'] === 'ERROR')) {
            $msg = $decoded['request_status'] ?? "HTTP {$httpCode}";
            if (isset($decoded['errors'])) {
                $msgs = [];
                foreach ($decoded['errors'] as $err) {
                    $msgs[] = $err['error_code'] . ': ' . ($err['error_message'] ?? 'unknown');
                }
                $msg = implode('; ', $msgs);
            }
            throw new RuntimeException('Snapchat API error: ' . $msg);
        }
        return $decoded;
    }
}
