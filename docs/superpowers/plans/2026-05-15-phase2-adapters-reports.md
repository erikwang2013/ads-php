# Phase 2: 广告平台扩展 + 报表引擎 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** 新增百度营销和淘宝广告适配器，实现真正数据同步调度，增强报表引擎支持自定义多维度查询。

**Architecture:** 每个广告平台实现 PlatformAdapter 接口，通过 AdapterRegistry 统一注册。数据同步使用 webman/crontab 定时调度，同步过程中将平台原生数据经 FieldMapping 转成统一格式写入 report_metrics 表。报表引擎在现有 DashboardController 基础上增加 CampaignController 中的自定义查询能力。

**Tech Stack:** PHP 8.2+, webman v2, webman/crontab, curl (API calls), Vue 3, ECharts 5

**Phase 1 已有基础:**
- PlatformAdapter 接口、AdapterRegistry、FieldMapping 全部就绪
- 巨量引擎适配器已实现完整链路（OAuth → 同步 → 投放操作）
- report_metrics 表已建好，DashboardController::summary() 已有按日期/平台聚合能力
- campaigns 表已建好，CampaignController 已有完整 CRUD
- OAuthService 通用授权流程已就绪

---

## 文件结构（Phase 2 新增/修改）

```
service/
├── plugin/
│   ├── ads-platform/
│   │   └── adapter/
│   │       ├── Baidu.php              # 新增：百度营销适配器
│   │       ├── Taobao.php             # 新增：淘宝广告适配器
│   │       └── Juliang.php            # 修改：补充 API 响应示例注释
│   ├── ads-task/
│   │   ├── config/plugin.php          # 新增
│   │   ├── task/
│   │   │   ├── DataSyncTask.php       # 新增：通用数据同步任务
│   │   │   └── TokenRefreshTask.php   # 新增：Token 自动刷新任务
│   │   └── config/cron.php            # 新增：定时任务配置
│   ├── ads-api/
│   │   ├── controller/
│   │   │   ├── ReportController.php   # 新增：自定义报表接口
│   │   │   └── CampaignController.php # 修改：增加批量操作接口
│   │   └── config/route.php           # 修改：增加报表路由
│   └── ads-report/
│       ├── config/plugin.php          # 新增
│       └── service/
│           └── ReportBuilder.php      # 新增：报表预计算服务
```

---

### Task 10: 创建百度营销适配器

**Files:**
- Create: `service/plugin/ads-platform/adapter/Baidu.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

百度营销 API 核心对接：
- OAuth 授权地址：`https://u.baidu.com/oauth/authorize`
- Token 交换：`https://u.baidu.com/oauth/token`
- 广告计划查询：`/json/sms/service/CampaignService/getCampaign`
- 报表查询：`/json/sms/service/ReportService/getProfessionalReportId` + `getReportData`

#### Step 1: 创建 Baidu.php 适配器

```php
<?php
namespace plugin\ads_platform\adapter;

use plugin\ads_platform\src\{
    PlatformAdapter, CampaignData, ReportRequest, FieldMapping
};

class Baidu implements PlatformAdapter
{
    protected string $appId;
    protected string $secret;
    protected string $baseUrl = 'https://api.baidu.com/';
    protected string $authUrl  = 'https://u.baidu.com/oauth/authorize';
    protected string $tokenUrl = 'https://u.baidu.com/oauth/token';

    public function __construct()
    {
        $this->appId  = getenv('BAIDU_APP_ID') ?: '';
        $this->secret = getenv('BAIDU_SECRET') ?: '';
    }

    public function code(): string { return 'baidu'; }
    public function name(): string { return '百度营销'; }
    public function capabilities(): array { return ['report', 'campaign', 'creative', 'oauth']; }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'app_id'       => $this->appId,
            'redirect_uri' => $redirectUri,
            'state'        => $state,
            'scope'        => 'basic',
        ]);
        return $this->authUrl . '?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request($this->tokenUrl, 'POST', [
            'app_id'       => $this->appId,
            'secret'       => $this->secret,
            'auth_code'    => $code,
            'grant_type'   => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);
        return [
            'access_token'   => $resp['data']['accessToken'] ?? '',
            'refresh_token'  => $resp['data']['refreshToken'] ?? '',
            'expires_in'     => $resp['data']['expiresIn'] ?? 86400,
            'advertiser_ids' => $resp['data']['userIds'] ?? [],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->request($this->tokenUrl, 'POST', [
            'app_id'        => $this->appId,
            'secret'        => $this->secret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);
        $data = $resp['data'] ?? [];
        return [
            'access_token'  => $data['accessToken'] ?? '',
            'refresh_token' => $data['refreshToken'] ?? '',
            'expires_in'    => $data['expiresIn'] ?? 86400,
        ];
    }

    public function fetchAccountInfo(string $accessToken): array
    {
        $resp = $this->signedRequest('/json/sms/service/UserService/getUserInfo', [], $accessToken);
        $list = $resp['data'] ?? [];
        return array_map(fn($item) => [
            'account_id_on_platform' => (string) ($item['userId'] ?? ''),
            'account_name'           => $item['userName'] ?? '',
        ], $list);
    }

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $resp = $this->signedRequest('/json/sms/service/CampaignService/getCampaign', [
            'campaignFields' => ['campaignId', 'campaignName', 'budget', 'status', 'startDate', 'endDate'],
        ], $accessToken);
        foreach (($resp['data'] ?? []) as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adGroupFieldMapping();
        $resp = $this->signedRequest('/json/sms/service/AdgroupService/getAdgroup', [
            'adgroupFields' => ['adgroupId', 'adgroupName', 'campaignId', 'status', 'bid', 'bidType'],
        ], $accessToken);
        $filtered = array_values(array_filter($resp['data'] ?? [], fn($r) => ($r['campaignId'] ?? '') === $campaignId));
        foreach ($filtered as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $resp = $this->signedRequest('/json/sms/service/CreativeService/getCreative', [
            'creativeFields' => ['creativeId', 'title', 'status'],
        ], $accessToken);
        foreach (($resp['data'] ?? []) as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();

        // 百度报表两步：先创建报表任务，再获取数据
        $reportReq = [
            'reportType' => $req->granularity === 'hourly' ? 1 : 2,
            'performanceData' => ['impressions', 'clicks', 'cost', 'ctr', 'cpc', 'cpm', 'conversions'],
            'startDate'  => $req->dateStart,
            'endDate'    => $req->dateEnd,
        ];

        $createResp = $this->signedRequest('/json/sms/service/ReportService/getProfessionalReportId', $reportReq, $accessToken);
        $reportId = $createResp['data']['reportId'] ?? '';

        if (!$reportId) {
            return;
        }

        // 轮询等待报表生成
        $maxRetries = 10;
        for ($i = 0; $i < $maxRetries; $i++) {
            sleep(2);
            $statusResp = $this->signedRequest('/json/sms/service/ReportService/getReportState', [
                'reportId' => $reportId,
            ], $accessToken);
            if (($statusResp['data']['isGenerated'] ?? 0) === 3) {
                break;
            }
        }

        $dataResp = $this->signedRequest('/json/sms/service/ReportService/getReportData', [
            'reportId' => $reportId,
        ], $accessToken);

        foreach (($dataResp['data'] ?? []) as $row) {
            yield $mapping->map($row);
        }
    }

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->signedRequest('/json/sms/service/CampaignService/addCampaign', [
            'campaignTypes' => [[
                'campaignName' => $data->name,
                'budget'       => $data->dailyBudget / 100,  // 分 → 元
            ]],
        ], $accessToken);
        return (string) ($resp['data'][0]['campaignId'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $this->signedRequest('/json/sms/service/CampaignService/updateCampaign', [
            'campaignTypes' => [[
                'campaignId'   => $platformId,
                'campaignName' => $data->name,
                'budget'       => $data->dailyBudget > 0 ? $data->dailyBudget / 100 : null,
            ]],
        ], $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->signedRequest('/json/sms/service/CampaignService/updateCampaign', [
            'campaignTypes' => [[
                'campaignId' => $platformId,
                'status'     => $enabled ? 1 : 2,
            ]],
        ], $accessToken);
    }

    // —— 字段映射 ——

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaignId'   => 'platform_campaign_id',
            'campaignName' => 'name',
            'budget'       => 'daily_budget',
            'status'       => 'status',
        ], [
            1 => 'enabled', 2 => 'paused', 3 => 'deleted',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget'] * 100);
            }
            return $unified;
        });
    }

    protected function adGroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'adgroupId'   => 'platform_adgroup_id',
            'adgroupName' => 'name',
            'bid'         => 'bid_amount',
            'status'      => 'status',
        ], [1 => 'enabled', 2 => 'paused', 3 => 'deleted']);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'creativeId' => 'platform_creative_id',
            'title'      => 'title',
            'status'     => 'status',
        ], [1 => 'enabled', 2 => 'paused']);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'impressions' => 'impressions',
            'clicks'      => 'clicks',
            'cost'        => 'cost',
            'ctr'         => 'ctr',
            'cpc'         => 'cpc',
            'cpm'         => 'cpm',
            'conversions' => 'conversions',
        ], [], function (array $unified): array {
            if (isset($unified['cost'])) {
                $unified['cost'] = (int) ($unified['cost'] * 100); // 元 → 分
            }
            return $unified;
        });
    }

    // —— 私有方法 ——

    protected function signedRequest(string $path, array $params, string $accessToken): array
    {
        $url = $this->baseUrl . ltrim($path, '/');
        $payload = [
            'header' => [
                'username'   => $this->appId,
                'password'   => $this->secret,
                'token'      => $accessToken,
                'target'     => 'baidu',
                'accessMode' => 1,
            ],
            'body' => $params,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Baidu API network error: ' . $err);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        $errno = $decoded['header']['failures'][0]['code'] ?? 0;
        if ($httpCode !== 200 || $errno !== 0) {
            $msg = $decoded['header']['failures'][0]['message'] ?? ('HTTP ' . $httpCode);
            throw new \RuntimeException('Baidu API error: ' . $msg);
        }
        return $decoded;
    }

    protected function request(string $url, string $method, array $params = []): array
    {
        $ch = curl_init();
        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
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
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Baidu API network error: ' . $err);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode($body, true);
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            throw new \RuntimeException('Baidu OAuth error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode));
        }
        return $decoded;
    }
}
```

#### Step 2: 注册百度适配器

```php
// 在 service/plugin/ads-platform/config/bootstrap.php 中添加
AdapterRegistry::register(new \plugin\ads_platform\adapter\Baidu());
```

#### Step 3: 提交

```bash
git add service/plugin/ads-platform/adapter/Baidu.php service/plugin/ads-platform/config/bootstrap.php
git commit -m "feat: add Baidu marketing adapter"
```

---

### Task 11: 创建淘宝广告适配器

**Files:**
- Create: `service/plugin/ads-platform/adapter/Taobao.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

淘宝广告（阿里妈妈）API 核心对接：
- OAuth 授权：淘宝开放平台 OAuth 2.0
- API 端点：`https://api.taobao.com/router/rest`
- 广告计划：`alimama.campaign.get`
- 报表：`alimama.report.get`

#### Step 1: 创建 Taobao.php

```php
<?php
namespace plugin\ads_platform\adapter;

use plugin\ads_platform\src\{
    PlatformAdapter, CampaignData, ReportRequest, FieldMapping
};

class Taobao implements PlatformAdapter
{
    protected string $appKey;
    protected string $secret;
    protected string $baseUrl = 'https://api.taobao.com/router/rest';
    protected string $authUrl = 'https://oauth.taobao.com/authorize';

    public function __construct()
    {
        $this->appKey = getenv('TAOBAO_APP_KEY') ?: '';
        $this->secret = getenv('TAOBAO_SECRET') ?: '';
    }

    public function code(): string { return 'taobao'; }
    public function name(): string { return '淘宝广告'; }
    public function capabilities(): array { return ['report', 'campaign', 'oauth']; }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->appKey,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
        ]);
        return $this->authUrl . '?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $resp = $this->request('taobao.oauth.token', [
            'client_id'     => $this->appKey,
            'client_secret' => $this->secret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]);
        $data = $resp['data'] ?? [];
        return [
            'access_token'   => $data['access_token'] ?? '',
            'refresh_token'  => $data['refresh_token'] ?? '',
            'expires_in'     => $data['expires_in'] ?? 86400,
            'advertiser_ids' => [$data['taobao_user_id'] ?? '0'],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $resp = $this->request('taobao.oauth.token', [
            'client_id'     => $this->appKey,
            'client_secret' => $this->secret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
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
        $resp = $this->request('taobao.user.get', [
            'fields' => 'user_id,nick',
        ], $accessToken);
        $data = $resp['data'] ?? [];
        $user = $data['user'] ?? [];
        return [[
            'account_id_on_platform' => (string) ($user['user_id'] ?? ''),
            'account_name'           => $user['nick'] ?? '',
        ]];
    }

    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        $mapping = $this->campaignFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('alimama.campaign.get', [
                'page_no'   => $page,
                'page_size' => 100,
            ], $accessToken);
            $list = $resp['data']['campaign_list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $page++;
        } while ($hasMore);
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        $mapping = $this->adGroupFieldMapping();
        $resp = $this->request('alimama.adgroup.get', [
            'campaign_id' => (int) $campaignId,
        ], $accessToken);
        foreach (($resp['data']['adgroup_list'] ?? []) as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        $mapping = $this->creativeFieldMapping();
        $resp = $this->request('alimama.creative.get', [
            'adgroup_id' => (int) $adGroupId,
        ], $accessToken);
        foreach (($resp['data']['creative_list'] ?? []) as $row) {
            yield $mapping->map($row);
        }
    }

    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        $mapping = $this->reportFieldMapping();
        $page = 1;
        do {
            $resp = $this->request('alimama.report.get', [
                'start_date'    => $req->dateStart,
                'end_date'      => $req->dateEnd,
                'granularity'   => $req->granularity === 'hourly' ? 'hourly' : 'daily',
                'page_no'       => $page,
                'page_size'     => min($req->pageSize, 200),
            ], $accessToken);
            $list = $resp['data']['report_list'] ?? [];
            foreach ($list as $row) {
                yield $mapping->map($row);
            }
            $hasMore = !empty($list);
            $page++;
        } while ($hasMore);
    }

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->request('alimama.campaign.create', [
            'campaign_name' => $data->name,
            'day_budget'    => $data->dailyBudget / 100,
        ], $accessToken);
        return (string) ($resp['data']['campaign_id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $params = [
            'campaign_id'   => $platformId,
            'campaign_name' => $data->name,
        ];
        if ($data->dailyBudget > 0) {
            $params['day_budget'] = $data->dailyBudget / 100;
        }
        $this->request('alimama.campaign.update', $params, $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('alimama.campaign.status.update', [
            'campaign_id'    => $platformId,
            'online_status'  => $enabled ? 1 : 0,
        ], $accessToken);
    }

    // —— 字段映射 ——

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'campaign_name' => 'name',
            'day_budget'    => 'daily_budget',
            'online_status' => 'status',
        ], [
            1 => 'enabled', 0 => 'paused',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget'] * 100);
            }
            return $unified;
        });
    }

    protected function adGroupFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'adgroup_id'   => 'platform_adgroup_id',
            'adgroup_name' => 'name',
            'bid_price'    => 'bid_amount',
            'online_status'=> 'status',
        ], [1 => 'enabled', 0 => 'paused']);
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'creative_id'   => 'platform_creative_id',
            'creative_title'=> 'title',
            'online_status' => 'status',
        ], [1 => 'enabled', 0 => 'paused']);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'impressions' => 'impressions',
            'clicks'      => 'clicks',
            'cost'        => 'cost',
            'ctr'         => 'ctr',
            'cpc'         => 'cpc',
            'cpm'         => 'cpm',
            'conversions' => 'conversions',
        ], [], function (array $unified): array {
            if (isset($unified['cost'])) {
                $unified['cost'] = (int) ($unified['cost'] * 100);
            }
            return $unified;
        });
    }

    // —— 淘宝 API 签名请求 ——

    protected function request(string $method, array $params, ?string $accessToken = null): array
    {
        $params['method']       = $method;
        $params['app_key']      = $this->appKey;
        $params['timestamp']    = date('Y-m-d H:i:s');
        $params['v']            = '2.0';
        $params['format']       = 'json';
        $params['sign_method']  = 'md5';
        if ($accessToken) {
            $params['session'] = $accessToken;
        }
        $params['sign'] = $this->sign($params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Taobao API network error: ' . $err);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        $errorResp = $decoded['error_response'] ?? null;
        if ($httpCode !== 200 || $errorResp) {
            $msg = $errorResp['sub_msg'] ?? $errorResp['msg'] ?? ('HTTP ' . $httpCode);
            throw new \RuntimeException('Taobao API error: ' . $msg);
        }
        $responseKey = str_replace('.', '_', $method) . '_response';
        return $decoded[$responseKey] ?? $decoded;
    }

    protected function sign(array $params): string
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            if ($k !== 'sign' && $v !== '' && $v !== null) {
                $str .= $k . $v;
            }
        }
        return strtoupper(md5($this->secret . $str . $this->secret));
    }
}
```

#### Step 2: 注册淘宝适配器

```php
// 在 bootstrap.php 中添加
AdapterRegistry::register(new \plugin\ads_platform\adapter\Taobao());
```

#### Step 3: 提交

```bash
git add service/plugin/ads-platform/adapter/Taobao.php service/plugin/ads-platform/config/bootstrap.php
git commit -m "feat: add Taobao (Alimama) advertising adapter"
```

---

### Task 12: 创建数据同步任务 + 报表引擎增强

**Files:**
- Create: `service/plugin/ads-task/config/plugin.php`
- Create: `service/plugin/ads-task/task/DataSyncTask.php`
- Create: `service/plugin/ads-task/task/TokenRefreshTask.php`
- Create: `service/plugin/ads-task/config/cron.php`
- Create: `service/plugin/ads-report/config/plugin.php`
- Create: `service/plugin/ads-report/service/ReportBuilder.php`
- Create: `service/plugin/ads-api/controller/ReportController.php`
- Modify: `service/plugin/ads-api/config/route.php`

#### Step 1: 创建 ads-task 插件

`service/plugin/ads-task/config/plugin.php`:
```php
<?php
return ['enable' => true, 'name' => 'ads-task', 'version' => '1.0.0'];
```

#### Step 2: 创建 TokenRefreshTask

```php
<?php
namespace plugin\ads_task\task;

use plugin\ads_account\model\PlatformAccount;
use plugin\ads_platform\src\AdapterRegistry;

class TokenRefreshTask
{
    public function execute(): void
    {
        $accounts = PlatformAccount::query()
            ->where('status', 1)
            ->whereNotNull('refresh_token')
            ->where('refresh_token', '!=', '')
            ->get();

        $refreshed = 0;
        foreach ($accounts as $account) {
            if (!$account->isTokenExpired()) continue;

            try {
                $adapter = AdapterRegistry::get($account->platform);
                if (!$adapter) continue;

                $tokenData = $adapter->refreshToken($account->refresh_token);
                $account->update([
                    'access_token'     => $tokenData['access_token'],
                    'refresh_token'    => $tokenData['refresh_token'] ?? $account->refresh_token,
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 86400),
                ]);
                $refreshed++;
            } catch (\Throwable $e) {
                echo "Token refresh failed for account {$account->id}: {$e->getMessage()}\n";
            }
        }

        echo "Refreshed {$refreshed} tokens.\n";
    }
}
```

#### Step 3: 创建 DataSyncTask

```php
<?php
namespace plugin\ads_task\task;

use plugin\ads_account\model\PlatformAccount;
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\ReportRequest;
use Illuminate\Database\Capsule\Manager as DB;

class DataSyncTask
{
    public function execute(): void
    {
        $accounts = PlatformAccount::query()
            ->where('status', 1)
            ->where('sync_enabled', 1)
            ->get();

        foreach ($accounts as $account) {
            echo "Syncing account {$account->id} ({$account->platform})...\n";

            try {
                $adapter = AdapterRegistry::get($account->platform);
                if (!$adapter) continue;

                // Sync campaigns
                foreach ($adapter->fetchCampaigns($account->access_token, $account->account_id_on_platform) as $row) {
                    DB::table('campaigns')->updateOrInsert(
                        [
                            'platform_account_id'  => $account->id,
                            'platform_campaign_id' => $row['platform_campaign_id'],
                        ],
                        [
                            'tenant_id'   => $account->tenant_id,
                            'platform'    => $account->platform,
                            'name'        => $row['name'] ?? '',
                            'daily_budget'=> $row['daily_budget'] ?? 0,
                            'status'      => $row['status'] ?? null,
                            'extra'       => json_encode($row['extra'] ?? [], JSON_UNESCAPED_UNICODE),
                            'synced_at'   => now(),
                            'updated_at'  => now(),
                        ]
                    );
                }

                // Sync reports (last 2 days)
                $req = new ReportRequest(
                    dateStart: date('Y-m-d', strtotime('-2 days')),
                    dateEnd:   date('Y-m-d'),
                    granularity: 'daily',
                    metrics: ['cost', 'impressions', 'clicks', 'conversions', 'ctr', 'cvr', 'cpc', 'cpm', 'roi'],
                );

                foreach ($adapter->fetchReports($account->access_token, $account->account_id_on_platform, $req) as $row) {
                    $campaignId = null;
                    if (!empty($row['platform_campaign_id'])) {
                        $campaign = DB::table('campaigns')
                            ->where('platform_campaign_id', $row['platform_campaign_id'])
                            ->where('platform_account_id', $account->id)
                            ->first();
                        $campaignId = $campaign->id ?? null;
                    }

                    DB::table('report_metrics')->updateOrInsert(
                        [
                            'tenant_id'           => $account->tenant_id,
                            'platform'            => $account->platform,
                            'platform_account_id' => $account->id,
                            'campaign_id'         => $campaignId,
                            'date'                => $row['date'] ?? date('Y-m-d'),
                            'granularity'         => 'daily',
                        ],
                        [
                            'cost'         => $row['cost'] ?? 0,
                            'impressions'  => $row['impressions'] ?? 0,
                            'clicks'       => $row['clicks'] ?? 0,
                            'conversions'  => $row['conversions'] ?? 0,
                            'ctr'          => $row['ctr'] ?? 0,
                            'cpm'          => $row['cpm'] ?? 0,
                            'cpc'          => $row['cpc'] ?? 0,
                            'cvr'          => $row['cvr'] ?? 0,
                        ]
                    );
                }

                $account->update(['last_sync_at' => now()]);
                echo "  Done.\n";

            } catch (\Throwable $e) {
                echo "  Failed: {$e->getMessage()}\n";
            }
        }
    }
}
```

#### Step 4: 创建 cron.php 定时任务配置

```php
<?php
return [
    // Token 每 55 分钟刷新一次（避免整点高峰）
    [
        'name'     => 'TokenRefresh',
        'handler'  => [plugin\ads_task\task\TokenRefreshTask::class, 'execute'],
        'rule'     => '55 */1 * * *',
    ],
    // 数据同步每 10 分钟执行
    [
        'name'     => 'DataSync',
        'handler'  => [plugin\ads_task\task\DataSyncTask::class, 'execute'],
        'rule'     => '*/10 * * * *',
    ],
];
```

#### Step 5: 创建 ads-report 插件 + ReportBuilder

`service/plugin/ads-report/config/plugin.php`:
```php
<?php
return ['enable' => true, 'name' => 'ads-report', 'version' => '1.0.0'];
```

`service/plugin/ads-report/service/ReportBuilder.php`:
```php
<?php
namespace plugin\ads_report\service;

use Illuminate\Database\Capsule\Manager as DB;

class ReportBuilder
{
    public function buildCustom(int $tenantId, array $params): array
    {
        $dateStart = $params['date_start'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateEnd   = $params['date_end']   ?? date('Y-m-d');
        $dimensions = $params['dimensions']  ?? ['platform'];
        $metrics    = $params['metrics']     ?? ['cost', 'impressions', 'clicks'];

        $metricColumns = $this->metricColumns($metrics);
        $groupCols     = $this->dimensionColumns($dimensions);

        $query = DB::table('report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd]);

        foreach ($groupCols as $col) {
            $query->groupBy($col)->select($col);
        }
        foreach ($metricColumns as $alias => $raw) {
            $query->selectRaw("{$raw} as {$alias}");
        }
        if (in_array('date', $dimensions)) {
            $query->orderBy('date');
        }
        $query->orderByDesc(array_values($metricColumns)[0] ?? 'cost');

        $perPage = min((int) ($params['per_page'] ?? 20), 100);
        $paginator = $query->paginate($perPage);

        return [
            'list'       => $paginator->items(),
            'pagination' => [
                'page'        => $paginator->currentPage(),
                'per_page'    => $paginator->perPage(),
                'total'       => $paginator->total(),
                'total_pages' => (int) ceil($paginator->total() / $paginator->perPage()),
            ],
        ];
    }

    protected function metricColumns(array $metrics): array
    {
        $map = [
            'cost'         => 'COALESCE(SUM(cost), 0)',
            'impressions'  => 'COALESCE(SUM(impressions), 0)',
            'clicks'       => 'COALESCE(SUM(clicks), 0)',
            'conversions'  => 'COALESCE(SUM(conversions), 0)',
            'ctr'          => 'CASE WHEN SUM(impressions) > 0 THEN ROUND(SUM(clicks)/SUM(impressions), 6) ELSE 0 END',
            'cvr'          => 'CASE WHEN SUM(clicks) > 0 THEN ROUND(SUM(conversions)/SUM(clicks), 6) ELSE 0 END',
            'cpc'          => 'CASE WHEN SUM(clicks) > 0 THEN ROUND(SUM(cost)/SUM(clicks), 2) ELSE 0 END',
            'cpm'          => 'CASE WHEN SUM(impressions) > 0 THEN ROUND(SUM(cost)/SUM(impressions)*1000, 2) ELSE 0 END',
            'roi'          => 'CASE WHEN SUM(cost) > 0 THEN ROUND(SUM(conversions)/SUM(cost)*100, 2) ELSE 0 END',
        ];
        $result = [];
        foreach ($metrics as $m) {
            if (isset($map[$m])) $result[$m] = $map[$m];
        }
        return $result;
    }

    protected function dimensionColumns(array $dimensions): array
    {
        return array_intersect($dimensions, ['platform', 'date', 'campaign_id', 'granularity']);
    }
}
```

#### Step 6: 创建 ReportController

```php
<?php
namespace plugin\ads_api\controller;

use plugin\ads_report\service\ReportBuilder;
use Webman\Http\Request;
use app\support\ApiResponse;

class ReportController
{
    public function custom(Request $request): \Webman\Http\Response
    {
        $builder = new ReportBuilder();
        $result = $builder->buildCustom(
            $request->tenantId ?? 1,
            $request->all()
        );
        return ApiResponse::success($result);
    }
}
```

#### Step 7: 增加报表路由

在 `route.php` 认证组中添加:
```php
\Webman\Route::get('/reports/custom', [ReportController::class, 'custom']);
```

#### Step 8: 提交

```bash
git add service/plugin/ads-task/ service/plugin/ads-report/ service/plugin/ads-api/
git commit -m "feat: add data sync tasks, report builder, and custom report API"
```

---

## 验收标准

1. ✅ 百度营销适配器实现全部 PlatformAdapter 方法
2. ✅ 淘宝广告适配器实现全部 PlatformAdapter 方法（含淘宝签名算法）
3. ✅ OAuth 回调处理通过 AdapterRegistry 统一分流
4. ✅ 百度/淘宝适配器在 bootstrap.php 中注册
5. ✅ TokenRefreshTask 每 55 分钟扫描过期 Token 并刷新
6. ✅ DataSyncTask 每 10 分钟拉取各平台投放数据和报表
7. ✅ 报表自定义查询 API 支持多维度（platform/date/campaign）和多指标组合
8. ✅ 同步数据正确写入 report_metrics 表
