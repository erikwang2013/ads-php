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

class Kuaishou implements PlatformAdapter
{
    protected string $appId;
    protected string $secret;
    protected string $baseUrl    = 'https://api.e.kuaishou.com/v2/';
    protected string $authUrl    = 'https://developers.e.kuaishou.com/oauth/authorize';
    protected string $tokenUrl   = 'https://api.e.kuaishou.com/oauth/token';

    public function __construct()
    {
        $this->appId  = env('KUAISHOU_APP_ID', '');
        $this->secret = env('KUAISHOU_SECRET', '');
    }

    public function code(): string { return 'kuaishou'; }

    public function name(): string { return '快手磁力引擎'; }

    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'app_id'       => $this->appId,
            'redirect_uri' => $redirectUri,
            'state'        => $state,
            'scope'        => 'ad_api',
            'response_type' => 'code',
        ]);
        return $this->authUrl . '?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->requestToken([
            'app_id'        => $this->appId,
            'secret'        => $this->secret,
            'auth_code'     => $code,
            'grant_type'    => 'authorization_code',
        ]);
        return $this->normalizeTokenResponse($resp);
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->requestToken([
            'app_id'        => $this->appId,
            'secret'        => $this->secret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);
        return $this->normalizeTokenResponse($resp);
    }

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', 'advertiser/info', [], $accessToken);
        $list = $resp['data']['list'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['advertiser_id'] ?? ''),
            'account_name'           => $item['advertiser_name'] ?? '',
        ], $list);
    }

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'campaign/list', [
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

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        yield from [];
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'creative/list', [
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

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', 'report/campaign/report', [
                'advertiser_id' => (int) $accountId,
                'start_date'    => $req->dateStart,
                'end_date'      => $req->dateEnd,
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

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->request('POST', 'campaign/create', [
            'advertiser_id' => (int) $accountId,
            'campaign_name' => $data->name,
            'day_budget'    => $data->dailyBudget / 100,
        ], $accessToken);
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
            $params['day_budget'] = $data->dailyBudget / 100;
        }
        $this->request('POST', 'campaign/update', $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('POST', 'campaign/status/update', [
            'advertiser_id' => (int) $accountId,
            'campaign_id'   => $platformId,
            'put_status'    => $enabled ? 1 : 2,
        ], $accessToken);
    }

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'campaign_name' => 'name',
            'day_budget'    => 'daily_budget',
            'put_status'    => 'status',
        ], [
            1 => 'enabled',
            2 => 'paused',
            3 => 'deleted',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget'] * 100);
            }
            return $unified;
        });
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'creative_id' => 'platform_creative_id',
            'title'       => 'title',
            'status'      => 'status',
        ], [
            1 => 'enabled',
            2 => 'paused',
            3 => 'deleted',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'  => 'platform_campaign_id',
            'charge'       => 'cost',
            'impression'   => 'impressions',
            'click'        => 'clicks',
            'action_count' => 'conversions',
            'ctr'          => 'ctr',
            'cvr'          => 'cvr',
        ], [], function (array $unified): array {
            if (isset($unified['cost'])) {
                $unified['cost'] = (int) ($unified['cost'] * 100);
            }
            foreach (['ctr', 'cvr'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = round((float) $unified[$field] / 100, 6);
                }
            }
            return $unified;
        });
    }

    protected function request(string $method, string $path, array $params = [], ?string $accessToken = null): array
    {
        $url = $this->baseUrl . $path;

        // Kuaishou auth: access_token as URL query parameter
        $queryParams = [];
        if ($accessToken) {
            $queryParams['access_token'] = $accessToken;
        }

        $headers = [];
        $ch = curl_init();

        if ($method === 'GET') {
            $queryParams = array_merge($queryParams, $params);
            $url .= '?' . http_build_query($queryParams);
        } else {
            // POST: access_token in URL query string, body as JSON
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }
            $headers[] = 'Content-Type: application/json';
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
            curl_close($ch);
            throw new RuntimeException('Kuaishou API network error: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            throw new RuntimeException(
                'Kuaishou API error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode)
            );
        }
        return $decoded;
    }

    protected function requestToken(array $params): array
    {
        $url = $this->tokenUrl;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Kuaishou token endpoint network error: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            throw new RuntimeException(
                'Kuaishou token endpoint error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode)
            );
        }
        return $decoded;
    }

    protected function normalizeTokenResponse(array $resp): array
    {
        $data = $resp['data'] ?? [];
        return [
            'access_token'   => $data['access_token'] ?? '',
            'refresh_token'  => $data['refresh_token'] ?? '',
            'expires_in'     => $data['expires_in'] ?? 86400,
            'advertiser_ids' => $data['advertiser_ids'] ?? [],
        ];
    }
}
