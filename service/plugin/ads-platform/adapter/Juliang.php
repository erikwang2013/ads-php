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

class Juliang implements PlatformAdapter
{
    protected string $appId;
    protected string $secret;
    protected string $baseUrl = 'https://ad.oceanengine.com/open_api/';

    public function __construct()
    {
        $this->appId  = env('JULIANG_APP_ID', '');
        $this->secret = env('JULIANG_SECRET', '');
    }

    public function code(): string { return 'juliang'; }

    public function name(): string { return '巨量引擎'; }

    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'app_id'       => $this->appId,
            'redirect_uri' => $redirectUri,
            'state'        => $state,
            'scope'        => implode(',', [1, 2, 4]),
        ]);
        return 'https://ad.oceanengine.com/openapi/audit/oauth.html?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request('POST', 'oauth2/access_token/', [
            'app_id'     => $this->appId,
            'secret'     => $this->secret,
            'auth_code'  => $code,
            'grant_type' => 'auth_code',
        ]);
        return [
            'access_token'   => $resp['data']['access_token'] ?? '',
            'refresh_token'  => $resp['data']['refresh_token'] ?? '',
            'expires_in'     => $resp['data']['expires_in'] ?? 86400,
            'advertiser_ids' => $resp['data']['advertiser_ids'] ?? [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->request('POST', 'oauth2/refresh_token/', [
            'app_id'        => $this->appId,
            'secret'        => $this->secret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);
        $data = $resp['data'] ?? [];
        return [
            'access_token'  => $data['access_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? '',
            'expires_in'    => $data['expires_in'] ?? 86400,
        ];
    }

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->request('GET', '2/advertiser/info/', [], $accessToken);
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
            $resp = $this->request('GET', '2/campaign/get/', [
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
        // Note: Juliang API creative/get doesn't filter by ad_group_id natively;
        // all creatives for the advertiser are fetched
        $mapping = $this->creativeFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('GET', '2/creative/get/', [
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
            $resp = $this->request('GET', '2/report/advertiser/get/', [
                'advertiser_id' => (int) $accountId,
                'start_date'    => $req->dateStart,
                'end_date'      => $req->dateEnd,
                'granularity'   => strtoupper($req->granularity),
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
        $resp = $this->request('POST', '2/campaign/create/', [
            'advertiser_id' => (int) $accountId,
            'campaign_name' => $data->name,
            'budget_mode'   => 'BUDGET_MODE_DAY',
            'budget'        => $data->dailyBudget / 100,
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
            $params['budget'] = $data->dailyBudget / 100;
        }
        $this->request('POST', '2/campaign/update/', $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('POST', '2/campaign/status/update/', [
            'advertiser_id' => (int) $accountId,
            'campaign_ids'  => [$platformId],
            'opt_status'    => $enabled ? 'ENABLE' : 'DISABLE',
        ], $accessToken);
    }

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'campaign_name' => 'name',
            'budget'        => 'daily_budget',
            'status'        => 'status',
        ], [
            'CAMPAIGN_STATUS_ENABLE'  => 'enabled',
            'CAMPAIGN_STATUS_DISABLE' => 'paused',
            'CAMPAIGN_STATUS_DELETE'  => 'deleted',
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
            'CREATIVE_STATUS_ENABLE'  => 'enabled',
            'CREATIVE_STATUS_DISABLE' => 'paused',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'  => 'platform_campaign_id',
            'stat_cost'    => 'cost',
            'show_cnt'     => 'impressions',
            'click_cnt'    => 'clicks',
            'convert_cnt'  => 'conversions',
            'ctr'          => 'ctr',
            'cpm_platform' => 'cpm',
            'cpc_platform' => 'cpc',
            'cvr'          => 'cvr',
        ], [], function (array $unified): array {
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) ($unified[$field] * 100);
                }
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
        $headers = ['Content-Type' => 'application/json'];
        if ($accessToken) {
            $headers['Access-Token'] = $accessToken;
        }

        $ch = curl_init();
        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt_array($ch, [
            CURLOPT_URL               => $url,
            CURLOPT_HTTPHEADER        => array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers),
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_TIMEOUT           => 30,
            CURLOPT_CONNECTTIMEOUT    => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Juliang API network error (code ' . curl_errno($ch) . '): ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            throw new RuntimeException(
                'Juliang API error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode)
            );
        }
        return $decoded;
    }
}
