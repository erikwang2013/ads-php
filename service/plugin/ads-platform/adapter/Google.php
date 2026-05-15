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

/**
 * Google Ads API adapter.
 *
 * Uses the REST interface via googleads.googleapis.com/v17/. Auth is
 * Bearer-token based with additional developer-token and login-customer-id
 * headers. Queries use GAQL (Google Ads Query Language) via the search
 * endpoint. Mutations use the :mutate endpoint with operations arrays.
 *
 * Money in Google Ads is in micros (millionths of the base currency unit).
 * We divide by 10,000 to convert to cents/fen for unified storage.
 */
class Google implements PlatformAdapter
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $developerToken;
    protected string $loginCustomerId;
    protected string $authBaseUrl = 'https://accounts.google.com/o/oauth2/auth';
    protected string $tokenUrl    = 'https://oauth2.googleapis.com/token';
    protected string $apiBaseUrl  = 'https://googleads.googleapis.com/v17/';

    public function __construct()
    {
        $this->clientId        = env('GOOGLE_CLIENT_ID', '');
        $this->clientSecret    = env('GOOGLE_CLIENT_SECRET', '');
        $this->developerToken  = env('GOOGLE_ADS_DEVELOPER_TOKEN', '');
        $this->loginCustomerId = env('GOOGLE_ADS_LOGIN_CUSTOMER_ID', '');
    }

    // ── Identity ──────────────────────────────────────────────

    public function code(): string { return 'google'; }

    public function name(): string { return 'Google Ads'; }

    public function capabilities(): array
    {
        return ['report', 'campaign', 'creative', 'oauth'];
    }

    // ── OAuth ─────────────────────────────────────────────────

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'https://www.googleapis.com/auth/adwords',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
        return $this->authBaseUrl . '?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->tokenRequest([
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
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
        $resp = $this->tokenRequest([
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
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
        // For MCC accounts: list client accounts under the manager
        if ($this->loginCustomerId) {
            $query = 'SELECT customer_client.id, customer_client.descriptive_name FROM customer_client';
            $resp  = $this->searchRequest($accessToken, $this->loginCustomerId, $query);
            $results = [];
            foreach (($resp['results'] ?? []) as $r) {
                $c = $r['customerClient'] ?? [];
                $results[] = [
                    'account_id_on_platform' => (string) ($c['id'] ?? ''),
                    'account_name'           => $c['descriptiveName'] ?? '',
                ];
            }
            return $results;
        }

        // Without an MCC, we cannot enumerate accounts; return empty
        return [];
    }

    // ── Campaigns ─────────────────────────────────────────────

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $query   = 'SELECT campaign.id, campaign.name, campaign.status, campaign_budget.amount_micros FROM campaign';
        $pageToken = null;

        do {
            $resp = $this->searchRequest($accessToken, $accountId, $query, $pageToken);
            foreach (($resp['results'] ?? []) as $r) {
                yield $mapping->map($this->flattenCampaignResult($r));
            }
            $pageToken = $resp['nextPageToken'] ?? null;
        } while ($pageToken);
    }

    // ── AdGroups ──────────────────────────────────────────────

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        yield from [];
    }

    // ── Creatives ─────────────────────────────────────────────

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping   = $this->creativeFieldMapping();
        $query     = 'SELECT ad_group_ad.ad.id, ad_group_ad.ad.name, ad_group_ad.status FROM ad_group_ad';
        $pageToken = null;

        do {
            $resp = $this->searchRequest($accessToken, $accountId, $query, $pageToken);
            foreach (($resp['results'] ?? []) as $r) {
                yield $mapping->map($this->flattenCreativeResult($r));
            }
            $pageToken = $resp['nextPageToken'] ?? null;
        } while ($pageToken);
    }

    // ── Reports ───────────────────────────────────────────────

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping   = $this->reportFieldMapping();
        $query     = $this->buildReportQuery($req);
        $pageToken = null;

        do {
            $resp = $this->searchRequest($accessToken, $accountId, $query, $pageToken);
            foreach (($resp['results'] ?? []) as $r) {
                yield $mapping->map($this->flattenReportResult($r));
            }
            $pageToken = $resp['nextPageToken'] ?? null;
        } while ($pageToken);
    }

    // ── Delivery operations ───────────────────────────────────

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $budgetMicros = $data->dailyBudget * 10000;  // fen -> micros

        // First create a campaign budget
        $budgetResp = $this->mutateRequest($accessToken, $accountId, 'campaignBudgets', [
            [
                'create' => [
                    'name'           => $data->name . ' Budget',
                    'amountMicros'    => $budgetMicros,
                    'deliveryMethod'  => 'STANDARD',
                ],
            ],
        ]);
        $budgetResourceName = $budgetResp['results'][0]['resourceName'] ?? '';
        $budgetId = $this->extractResourceId($budgetResourceName);

        // Then create the campaign referencing the budget
        $resp = $this->mutateRequest($accessToken, $accountId, 'campaigns', [
            [
                'create' => [
                    'name'                   => $data->name,
                    'status'                 => 'ENABLED',
                    'campaignBudget'         => "customers/{$accountId}/campaignBudgets/{$budgetId}",
                    'advertisingChannelType' => 'SEARCH',
                ],
            ],
        ]);
        return $this->extractResourceId($resp['results'][0]['resourceName'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $update = [
            'resourceName' => "customers/{$accountId}/campaigns/{$platformId}",
        ];
        if ($data->name !== '') {
            $update['name'] = $data->name;
        }

        $updateMask = $this->buildUpdateMask($data);
        if ($updateMask === '') {
            return;  // nothing to update
        }

        $this->mutateRequest($accessToken, $accountId, 'campaigns', [
            [
                'update'     => $update,
                'updateMask' => $updateMask,
            ],
        ]);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->mutateRequest($accessToken, $accountId, 'campaigns', [
            [
                'update'     => [
                    'resourceName' => "customers/{$accountId}/campaigns/{$platformId}",
                    'status'       => $enabled ? 'ENABLED' : 'PAUSED',
                ],
                'updateMask' => 'status',
            ],
        ]);
    }

    // ── Field mappings ────────────────────────────────────────

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'         => 'platform_campaign_id',
            'campaign_name'       => 'name',
            'daily_budget_micros' => 'daily_budget',
            'status'              => 'status',
        ], [
            'ENABLED' => 'enabled',
            'PAUSED'  => 'paused',
            'REMOVED' => 'deleted',
        ], function (array $unified): array {
            // Google returns micros; convert to fen (divide by 10,000)
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) round((float) $unified['daily_budget'] / 10000);
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
            'ENABLED' => 'enabled',
            'PAUSED'  => 'paused',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id' => 'platform_campaign_id',
            'cost_micros' => 'cost',
            'impressions' => 'impressions',
            'clicks'      => 'clicks',
            'conversions' => 'conversions',
            'ctr'         => 'ctr',
            'cpm_micros'  => 'cpm',
            'cpc_micros'  => 'cpc',
            'cvr'         => 'cvr',
        ], [], function (array $unified): array {
            // Google returns money in micros; convert to fen (divide by 10,000)
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) round((float) $unified[$field] / 10000);
                }
            }
            // Google returns ctr/cvr as decimal already; ensure float
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
     * OAuth token request -- plain POST to Google's OAuth2 token endpoint.
     */
    protected function tokenRequest(array $params): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->tokenUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Google OAuth network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Google OAuth: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $desc = $decoded['error_description'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Google OAuth error: ' . $desc);
        }
        return $decoded;
    }

    /**
     * Build common HTTP headers for Google Ads API requests.
     */
    protected function buildHeaders(string $accessToken): array
    {
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'developer-token: ' . $this->developerToken,
            'Content-Type: application/json',
        ];
        if ($this->loginCustomerId) {
            $headers[] = 'login-customer-id: ' . $this->loginCustomerId;
        }
        return $headers;
    }

    /**
     * Execute a GAQL search request against googleAds:search.
     *
     * Handles pageToken-based pagination. Returns the raw decoded JSON
     * response array which includes 'results' and optionally 'nextPageToken'.
     */
    protected function searchRequest(string $accessToken, string $customerId, string $query, ?string $pageToken = null): array
    {
        $url  = $this->apiBaseUrl . "customers/{$customerId}/googleAds:search";
        $body = ['query' => $query, 'pageSize' => 100];
        if ($pageToken) {
            $body['pageToken'] = $pageToken;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => $this->buildHeaders($accessToken),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $respBody = curl_exec($ch);
        if ($respBody === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Google Ads API network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($respBody, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Google Ads API: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $err  = $decoded['error'] ?? [];
            $desc = $err['message'] ?? "HTTP {$httpCode}";
            $code = $err['code'] ?? 0;
            throw new RuntimeException("Google Ads API error [code {$code}]: {$desc}");
        }
        return $decoded;
    }

    /**
     * Execute a mutate request against a resource's :mutate endpoint.
     *
     * Google Ads uses operations:create/update/remove pattern instead of
     * traditional REST CRUD. Each operation in the array describes a single
     * mutation (create, update, or remove).
     */
    protected function mutateRequest(string $accessToken, string $customerId, string $resource, array $operations): array
    {
        $url  = $this->apiBaseUrl . "customers/{$customerId}/{$resource}:mutate";
        $body = json_encode(['operations' => $operations]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $this->buildHeaders($accessToken),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $respBody = curl_exec($ch);
        if ($respBody === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Google Ads mutate network error [{$errno}]: {$error}");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($respBody, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Google Ads mutate: invalid JSON response');
        }
        if ($httpCode !== 200 || isset($decoded['error'])) {
            $err  = $decoded['error'] ?? [];
            $desc = $err['message'] ?? "HTTP {$httpCode}";
            $code = $err['code'] ?? 0;
            throw new RuntimeException("Google Ads mutate error [code {$code}]: {$desc}");
        }
        return $decoded;
    }

    // ── GAQL & response helpers ───────────────────────────────

    /**
     * Build a GAQL report query string from a ReportRequest.
     *
     * Uses segments.date for time filtering. Metrics and campaign ID are
     * selected. The query is used with the googleAds:search endpoint.
     */
    protected function buildReportQuery(ReportRequest $req): string
    {
        $dateClause = "segments.date BETWEEN '{$req->dateStart}' AND '{$req->dateEnd}'";
        return 'SELECT campaign.id, metrics.cost_micros, metrics.impressions, metrics.clicks, '
            . 'metrics.conversions, metrics.ctr, metrics.average_cpm, metrics.average_cpc, '
            . 'metrics.conversions_from_interactions_rate '
            . "FROM campaign WHERE {$dateClause}";
    }

    /**
     * Flatten a Google Ads search result row into a flat key-value array
     * suitable for FieldMapping::map().
     */
    protected function flattenCampaignResult(array $result): array
    {
        $c      = $result['campaign'] ?? [];
        $budget = $result['campaignBudget'] ?? [];
        return [
            'campaign_id'         => $this->extractResourceId($c['resourceName'] ?? ''),
            'campaign_name'       => $c['name'] ?? '',
            'status'              => $c['status'] ?? '',
            'daily_budget_micros' => $budget['amountMicros'] ?? '0',
        ];
    }

    /**
     * Flatten a creative (ad_group_ad) search result row.
     */
    protected function flattenCreativeResult(array $result): array
    {
        $adGroupAd = $result['adGroupAd'] ?? [];
        $ad        = $adGroupAd['ad'] ?? [];
        return [
            'creative_id' => (string) ($ad['id'] ?? ''),
            'title'       => $ad['name'] ?? '',
            'status'      => $adGroupAd['status'] ?? '',
        ];
    }

    /**
     * Flatten a report search result row (campaign + metrics).
     */
    protected function flattenReportResult(array $result): array
    {
        $c = $result['campaign'] ?? [];
        $m = $result['metrics'] ?? [];
        return [
            'campaign_id' => $this->extractResourceId($c['resourceName'] ?? ''),
            'cost_micros' => $m['costMicros'] ?? '0',
            'impressions' => $m['impressions'] ?? '0',
            'clicks'      => $m['clicks'] ?? '0',
            'conversions' => $m['conversions'] ?? '0',
            'ctr'         => $m['ctr'] ?? 0,
            'cpm_micros'  => $m['averageCpm'] ?? '0',
            'cpc_micros'  => $m['averageCpc'] ?? '0',
            'cvr'         => $m['conversionsFromInteractionsRate'] ?? 0,
        ];
    }

    /**
     * Extract the trailing numeric ID from a Google Ads resource name.
     *
     * Resource name format: "customers/{customer_id}/campaigns/{campaign_id}"
     * Returns the last path segment.
     */
    protected function extractResourceId(string $resourceName): string
    {
        $parts = explode('/', $resourceName);
        return end($parts) ?: '';
    }

    /**
     * Build an updateMask string from a CampaignData object.
     *
     * The updateMask is a comma-separated list of field paths to update.
     */
    protected function buildUpdateMask(CampaignData $data): string
    {
        $paths = [];
        if ($data->name !== '') {
            $paths[] = 'name';
        }
        return implode(',', $paths);
    }
}
