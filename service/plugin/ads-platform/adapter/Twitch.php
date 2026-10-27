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

class Twitch implements PlatformAdapter
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl = 'https://api.twitch.tv/helix/ads/';

    public function __construct()
    {
        $this->clientId     = env('TWITCH_ADS_CLIENT_ID', '');
        $this->clientSecret = env('TWITCH_ADS_CLIENT_SECRET', '');
    }

    public function code(): string { return 'twitch'; }

    public function name(): string { return 'Twitch Ads'; }

    public function capabilities(): array { return ['report', 'campaign', 'oauth']; }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'ads:read ads:edit',
            'response_type' => 'code',
        ]);
        return 'https://id.twitch.tv/oauth2/authorize?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request('POST', 'https://id.twitch.tv/oauth2/token', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ], null, false);
        return [
            'access_token'   => $resp['access_token'] ?? '',
            'refresh_token'  => $resp['refresh_token'] ?? '',
            'expires_in'     => $resp['expires_in'] ?? 14400,
            'advertiser_ids' => [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->request('POST', 'https://id.twitch.tv/oauth2/token', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ], null, false);
        return [
            'access_token'  => $resp['access_token'] ?? '',
            'refresh_token' => $resp['refresh_token'] ?? '',
            'expires_in'    => $resp['expires_in'] ?? 14400,
        ];
    }

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'https://api.twitch.tv/helix/users', [], $accessToken, false);
        $list = $resp['data'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['id'] ?? ''),
            'account_name'           => $item['display_name'] ?? ($item['login'] ?? ''),
        ], $list);
    }

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $cursor = null;
        do {
            $query = ['broadcaster_id' => $accountId];
            if ($cursor) {
                $query['after'] = $cursor;
            }
            $resp = $this->request('GET', 'campaigns', $query, $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $cursor = $resp['pagination']['cursor'] ?? null;
        } while ($cursor);
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        // Twitch uses ad_schedule segments within campaigns — no separate ad group entity
        yield from [];
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        // Twitch ads are video-based with limited creative API surface
        yield from [];
    }

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $cursor = null;
        do {
            $query = [
                'broadcaster_id' => $accountId,
                'started_at'     => $req->dateStart . 'T00:00:00Z',
                'ended_at'       => $req->dateEnd . 'T23:59:59Z',
                'first'          => min($req->pageSize, 100),
            ];
            if ($cursor) {
                $query['after'] = $cursor;
            }
            $resp = $this->request('GET', 'analytics', $query, $accessToken);
            $list = $resp['data'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $cursor = $resp['pagination']['cursor'] ?? null;
        } while ($cursor);
    }

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->request('POST', 'campaigns', [
            'broadcaster_id' => $accountId,
            'name'           => $data->name,
            'budget'         => $data->dailyBudget,
            'start_at'       => ($data->startDate ?? date('Y-m-d')) . 'T00:00:00Z',
            'status'         => 'ACTIVE',
        ], $accessToken);
        return (string) ($resp['data'][0]['id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $payload = [
            'broadcaster_id' => $accountId,
            'id'             => $platformId,
            'name'           => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $payload['budget'] = $data->dailyBudget;
        }
        $this->request('PATCH', 'campaigns', $payload, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('PATCH', 'campaigns', [
            'broadcaster_id' => $accountId,
            'id'             => $platformId,
            'status'         => $enabled ? 'ACTIVE' : 'PAUSED',
        ], $accessToken);
    }

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'id'        => 'platform_campaign_id',
            'name'      => 'name',
            'budget'    => 'daily_budget',
            'status'    => 'status',
            'start_at'  => 'start_date',
            'end_at'    => 'end_date',
        ], [
            'ACTIVE' => 'enabled',
            'PAUSED' => 'paused',
            'ENDED'  => 'deleted',
        ], function (array $unified): array {
            // Twitch budget in cents, no conversion
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) $unified['daily_budget'];
            }
            return $unified;
        });
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'cost'          => 'cost',
            'impressions'   => 'impressions',
            'clicks'        => 'clicks',
            'conversions'   => 'conversions',
            'ctr'           => 'ctr',
            'cpm'           => 'cpm',
            'cpc'           => 'cpc',
            'video_views'   => 'video_views',
            'date'          => 'date',
        ], [], function (array $unified): array {
            // Twitch returns cost in cents, no conversion needed
            if (isset($unified['cost'])) {
                $unified['cost'] = (int) $unified['cost'];
            }
            return $unified;
        });
    }

    protected function request(string $method, string $url, array $params = [], ?string $accessToken = null, bool $isAdsApi = true): array
    {
        // Allow full URL override for auth endpoints
        if ($isAdsApi && !str_starts_with($url, 'https://')) {
            $url = $this->baseUrl . $url;
        }

        $headers = ['Content-Type: application/json'];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }
        // Twitch always requires Client-Id header
        $headers[] = 'Client-Id: ' . $this->clientId;

        $ch = curl_init();
        if ($method === 'GET') {
            if (!empty($params)) {
                $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
            }
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false || curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Twitch Ads API network error: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode >= 400 || !is_array($decoded)) {
            throw new RuntimeException(
                'Twitch Ads API error: HTTP ' . $httpCode . ' - ' . ($decoded['message'] ?? $body)
            );
        }
        return $decoded;
    }
}
