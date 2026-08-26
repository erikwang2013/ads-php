# Phase 1: Foundation Skeleton Implementation Plan

[中文](docs/superpowers/plans/2026-05-14-phase1-foundation.md) | [English](docs/superpowers/plans/2026-05-14-phase1-foundation.en.md) | [한국어](docs/superpowers/plans/2026-05-14-phase1-foundation.ko.md) | [Русский](docs/superpowers/plans/2026-05-14-phase1-foundation.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-14-phase1-foundation.de.md) | [Français](docs/superpowers/plans/2026-05-14-phase1-foundation.fr.md) | [Español](docs/superpowers/plans/2026-05-14-phase1-foundation.es.md) | [Português](docs/superpowers/plans/2026-05-14-phase1-foundation.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-14-phase1-foundation.hi.md) | [العربية](docs/superpowers/plans/2026-05-14-phase1-foundation.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-14-phase1-foundation.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-14-phase1-foundation.id.md) | [日本語](docs/superpowers/plans/2026-05-14-phase1-foundation.ja.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the webman v2 server + webman-admin v2 admin backend skeleton, implementing multi-tenancy, authentication/permissions, account management, and the OAuth flow framework, and run through the full chain with Ocean Engine (Juliang) as the first platform.

**Architecture:** webman v2 plugin architecture; under service/plugin/, four plugins split by responsibility: ads-tenant, ads-account, ads-platform, ads-api; the admin backend uses Vue3+TS+Element Plus, manages state with Pinia, and connects to backend APIs via Axios.

**Tech Stack:** PHP 8.2+, webman v2, MySQL 8.0, Redis 7, Vue 3, TypeScript, Element Plus, ECharts 5, Pinia, Axios

---

## File Structure

```
service/
├── composer.json
├── .env
├── config/
│   ├── database.php
│   ├── redis.php
│   ├── app.php
│   └── middleware.php
├── plugin/
│   ├── ads-tenant/
│   │   ├── config/plugin.php, database.php
│   │   ├── model/Tenant.php
│   │   ├── middleware/TenantIdentify.php
│   │   └── migration/create_tenants.sql
│   ├── ads-account/
│   │   ├── config/plugin.php
│   │   ├── model/PlatformAccount.php, AuthToken.php
│   │   ├── service/OAuthService.php
│   │   └── migration/create_platform_accounts.sql
│   ├── ads-platform/
│   │   ├── config/plugin.php
│   │   ├── src/PlatformAdapter.php, AdapterRegistry.php, FieldMapping.php, CampaignData.php, ReportRequest.php
│   │   ├── adapter/Juliang.php
│   │   └── migration/create_campaign_tables.sql
│   └── ads-api/
│       ├── config/plugin.php, route.php
│       ├── controller/AuthController.php, AccountController.php, CampaignController.php, DashboardController.php, PlatformController.php
│       └── middleware/AuthMiddleware.php
└── support/Response.php

admin/
├── package.json
├── vite.config.ts
├── tsconfig.json
├── index.html
├── src/
│   ├── main.ts, App.vue
│   ├── router/index.ts
│   ├── stores/auth.ts, tenant.ts, account.ts
│   ├── api/index.ts, auth.ts, account.ts, campaign.ts, dashboard.ts, platform.ts
│   ├── views/
│   │   ├── login/LoginPage.vue
│   │   ├── dashboard/DashboardPage.vue
│   │   ├── account/AccountList.vue, AccountBind.vue
│   │   ├── campaign/CampaignList.vue
│   │   └── system/UserManage.vue
│   ├── components/
│   │   ├── layout/AppLayout.vue, SideNav.vue, TopBar.vue
│   │   ├── PlatformBadge.vue, MetricCard.vue
│   │   └── common/EmptyState.vue
│   └── utils/format.ts
```

---

### Task 1: Initialize the webman v2 server project

**Files:**
- Create: `service/composer.json`
- Create: `service/.env`
- Create: `service/config/app.php`
- Create: `service/config/database.php`
- Create: `service/config/redis.php`
- Create: `service/config/middleware.php`
- Create: `service/support/Response.php`
- Create: `service/start.php`

- [x] **Step 1: Create composer.json and install dependencies**

```json
{
    "name": "ads/service",
    "type": "project",
    "require": {
        "php": ">=8.2",
        "workerman/webman-framework": "^2.0",
        "workerman/crontab": "^2.0",
        "illuminate/database": "^11.0",
        "illuminate/redis": "^11.0",
        "webman/redis-queue": "^2.0",
        "firebase/php-jwt": "^6.0",
        "vlucas/phpdotenv": "^5.0"
    },
    "autoload": {
        "psr-4": {
            "plugin\\": "plugin/"
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

Run: `cd service && composer install`

- [x] **Step 2: Create the .env environment config**

```env
APP_DEBUG=true
APP_URL=http://0.0.0.0:8787

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ads
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

JWT_SECRET=your-secret-key-change-me
JWT_TTL=86400
```

- [x] **Step 3: Create config/app.php**

```php
<?php
return [
    'debug' => env('APP_DEBUG', false),
    'default_timezone' => 'Asia/Shanghai',
    'jwt' => [
        'secret' => env('JWT_SECRET', ''),
        'ttl' => (int) env('JWT_TTL', 86400),
    ],
];
```

- [x] **Step 4: Create config/database.php with dynamic data source support**

```php
<?php
return [
    'default' => 'shared',
    'connections' => [
        'shared' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'ads'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ],
    ],
];
```

- [x] **Step 5: Create config/redis.php**

```php
<?php
return [
    'default' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => env('REDIS_PORT', '6379'),
        'password' => env('REDIS_PASSWORD', ''),
        'database' => 0,
    ],
];
```

- [x] **Step 6: Create config/middleware.php**

```php
<?php
return [
    'global' => [
        // 全局中间件
    ],
];
```

- [x] **Step 7: Create support/Response.php for unified responses**

```php
<?php
namespace support;

class Response
{
    public static function json(int $code, string $message, mixed $data = null): \Webman\Http\Response
    {
        $body = ['code' => $code, 'message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }
        return new \Webman\Http\Response(200, ['Content-Type' => 'application/json'], json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    public static function success(mixed $data = null, string $message = 'success'): \Webman\Http\Response
    {
        return static::json(0, $message, $data);
    }

    public static function error(string $message, int $code = 1, int $httpCode = 200): \Webman\Http\Response
    {
        return new \Webman\Http\Response($httpCode, ['Content-Type' => 'application/json'], json_encode(['code' => $code, 'message' => $message], JSON_UNESCAPED_UNICODE));
    }

    public static function paginated(array $list, int $total, int $page, int $perPage, ?array $summary = null): \Webman\Http\Response
    {
        $data = [
            'list' => $list,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ];
        if ($summary !== null) {
            $data['summary'] = $summary;
        }
        return static::success($data);
    }
}
```

- [x] **Step 8: Create service/start.php**

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
```

- [x] **Step 9: Verify the project starts**

Run: `cd service && php start.php start`
Expected: webman v2 starts successfully, listening on port 8787

- [x] **Step 10: Commit**

```bash
cd /home/wwwroot/ads-php
git add service/ -f
git commit -m "feat: initialize webman v2 service project with config"
```

---

### Task 2: Create the ads-tenant multi-tenant plugin

**Files:**
- Create: `service/plugin/ads-tenant/config/plugin.php`
- Create: `service/plugin/ads-tenant/config/database.php`
- Create: `service/plugin/ads-tenant/model/Tenant.php`
- Create: `service/plugin/ads-tenant/middleware/TenantIdentify.php`
- Create: `service/plugin/ads-tenant/migration/create_tenants.sql`

- [x] **Step 1: Create plugin.php**

```php
<?php
return [
    'enable' => true,
    'name'   => 'ads-tenant',
    'version'=> '1.0.0',
];
```

- [x] **Step 2: Create database.php data source routing**

```php
<?php
namespace plugin\ads_tenant\config;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_tenant\model\Tenant;

class Database
{
    public static function connect(Tenant $tenant): void
    {
        if ($tenant->db_type === 'shared') {
            return;
        }
        $cfg = json_decode($tenant->db_config, true);
        $name = 'tenant_' . $tenant->id;
        $config = DB::getDatabaseManager()->getConfig('shared');
        $config['database'] = $cfg['database'] ?? $name;
        $config['host']     = $cfg['host']     ?? $config['host'];
        $config['username'] = $cfg['username'] ?? $config['username'];
        $config['password'] = $cfg['password'] ?? $config['password'];
        DB::getDatabaseManager()->setConfig($name, $config);
        DB::connection($name);
    }

    public static function connectionName(Tenant $tenant): string
    {
        return $tenant->db_type === 'dedicated' ? 'tenant_' . $tenant->id : 'shared';
    }
}
```

- [x] **Step 3: Create the Tenant model**

```php
<?php
namespace plugin\ads_tenant\model;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $table = 'tenants';
    protected $guarded = ['id'];
    protected $casts = [
        'db_config' => 'array',
    ];

    public function isActive(): bool
    {
        return (int) $this->status === 1;
    }

    public static function findByDomain(string $domain): ?self
    {
        return static::where('domain', $domain)->where('status', 1)->first();
    }
}
```

- [x] **Step 4: Create the TenantIdentify middleware**

```php
<?php
namespace plugin\ads_tenant\middleware;

use plugin\ads_tenant\model\Tenant;
use plugin\ads_tenant\config\Database;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class TenantIdentify implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $tenantId = $request->header('X-Tenant-Id')
            ?? $request->sessionGet('tenant_id');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant && $tenant->isActive()) {
                Database::connect($tenant);
                // 全局绑定
                app()->instance('current_tenant', $tenant);
                app()->instance('current_connection', Database::connectionName($tenant));
            }
        }

        return $handler($request);
    }
}
```

- [x] **Step 5: Create the migration SQL**

```sql
CREATE TABLE IF NOT EXISTS `tenants` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `domain` VARCHAR(255) DEFAULT NULL,
    `db_type` ENUM('shared','dedicated') DEFAULT 'shared',
    `db_config` JSON NULL,
    `plan` ENUM('free','pro','enterprise') DEFAULT 'free',
    `status` TINYINT DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tenants` (`id`, `name`, `plan`) VALUES (1, '默认租户', 'enterprise')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
```

- [x] **Step 6: Commit**

```bash
cd /home/wwwroot/ads-php
git add service/plugin/ads-tenant/ -f
git commit -m "feat: add ads-tenant plugin with multi-tenant support"
```

---

### Task 3: Create the ads-platform adapter core

**Files:**
- Create: `service/plugin/ads-platform/config/plugin.php`
- Create: `service/plugin/ads-platform/src/PlatformAdapter.php`
- Create: `service/plugin/ads-platform/src/AdapterRegistry.php`
- Create: `service/plugin/ads-platform/src/FieldMapping.php`
- Create: `service/plugin/ads-platform/src/CampaignData.php`
- Create: `service/plugin/ads-platform/src/ReportRequest.php`
- Create: `service/plugin/ads-platform/adapter/Juliang.php`

- [x] **Step 1: Create the PlatformAdapter interface**

```php
<?php
namespace plugin\ads_platform\src;

interface PlatformAdapter
{
    public function code(): string;
    public function name(): string;
    public function capabilities(): array;

    // 授权
    public function buildAuthUrl(string $redirectUri, string $state): string;
    public function exchangeToken(string $code, string $redirectUri): array;
    public function refreshToken(string $refreshToken): array;

    // 获取账户信息
    public function fetchAccountInfo(string $accessToken): array;

    // 数据同步（返回 Generator，yield 统一 Campaign/AdGroup/Creative 对象）
    public function fetchCampaigns(string $accessToken, string $accountId): \Generator;
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator;
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator;
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator;

    // 投放操作
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string;
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void;
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void;
}
```

- [x] **Step 2: Create CampaignData**

```php
<?php
namespace plugin\ads_platform\src;

class CampaignData
{
    public function __construct(
        public string $name,
        public int    $dailyBudget,    // 单位：分
        public ?int   $totalBudget = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $status = null,
        public array  $extra = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:        $data['name'],
            dailyBudget: (int) ($data['daily_budget'] ?? 0),
            totalBudget: isset($data['total_budget']) ? (int) $data['total_budget'] : null,
            startDate:   $data['start_date'] ?? null,
            endDate:     $data['end_date'] ?? null,
            status:      $data['status'] ?? null,
            extra:       $data['extra'] ?? [],
        );
    }
}
```

- [x] **Step 3: Create ReportRequest**

```php
<?php
namespace plugin\ads_platform\src;

class ReportRequest
{
    public function __construct(
        public string $dateStart,
        public string $dateEnd,
        public string $granularity = 'daily',  // daily | hourly | summary
        public array  $dimensions = [],         // campaign, adgroup, creative, platform, date
        public array  $metrics = [],            // cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
        public int    $pageSize = 100,
        public ?string $cursor = null,
    ) {}
}
```

- [x] **Step 4: Create the FieldMapping field mapping engine**

```php
<?php
namespace plugin\ads_platform\src;

class FieldMapping
{
    public function __construct(
        protected array $fieldMap,
        protected array $statusMap,
        protected ?callable $valueTransformer = null,
    ) {}

    public function map(array $raw): array
    {
        $unified = ['extra' => []];
        $reverseMap = array_flip($this->fieldMap);

        foreach ($raw as $platformField => $value) {
            if (isset($this->fieldMap[$platformField])) {
                $unifiedField = $this->fieldMap[$platformField];
                $unified[$unifiedField] = $value;
            } else {
                $unified['extra'][$platformField] = $value;
            }
        }

        if (isset($unified['status']) && isset($this->statusMap[$unified['status']])) {
            $unified['status'] = $this->statusMap[$unified['status']];
        }

        if ($this->valueTransformer) {
            $unified = ($this->valueTransformer)($unified);
        }

        return $unified;
    }
}
```

- [x] **Step 5: Create AdapterRegistry**

```php
<?php
namespace plugin\ads_platform\src;

class AdapterRegistry
{
    protected static array $adapters = [];

    public static function register(PlatformAdapter $adapter): void
    {
        static::$adapters[$adapter->code()] = $adapter;
    }

    public static function get(string $code): ?PlatformAdapter
    {
        return static::$adapters[$code] ?? null;
    }

    public static function all(): array
    {
        $list = [];
        foreach (static::$adapters as $adapter) {
            $list[] = [
                'code'         => $adapter->code(),
                'name'         => $adapter->name(),
                'capabilities' => $adapter->capabilities(),
            ];
        }
        return $list;
    }

    public static function has(string $code): bool
    {
        return isset(static::$adapters[$code]);
    }
}
```

- [x] **Step 6: Create the Juliang (Ocean Engine) adapter (field mapping + interface skeleton)**

```php
<?php
namespace plugin\ads_platform\adapter;

use plugin\ads_platform\src\{
    PlatformAdapter, CampaignData, ReportRequest, FieldMapping
};

class Juliang implements PlatformAdapter
{
    protected string $appId;
    protected string $secret;
    protected string $baseUrl = 'https://ad.oceanengine.com/open_api/';

    public function __construct()
    {
        $this->appId  = getenv('JULIANG_APP_ID') ?: '';
        $this->secret = getenv('JULIANG_SECRET') ?: '';
    }

    public function code(): string
    {
        return 'juliang';
    }

    public function name(): string
    {
        return '巨量引擎';
    }

    public function capabilities(): array
    {
        return ['report', 'campaign', 'creative', 'oauth'];
    }

    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'app_id'       => $this->appId,
            'redirect_uri' => $redirectUri,
            'state'        => $state,
            'scope'        => implode(',', [1, 2, 4]), // 账号服务、广告投放、数据报表
        ]);
        return 'https://ad.oceanengine.com/openapi/audit/oauth.html?' . $query;
    }

    public function exchangeToken(string $code, string $redirectUri): array
    {
        // 调用巨量 OAuth access_token 接口
        $resp = $this->request('POST', 'oauth2/access_token/', [
            'app_id'       => $this->appId,
            'secret'       => $this->secret,
            'auth_code'    => $code,
            'grant_type'   => 'auth_code',
        ]);
        return [
            'access_token'  => $resp['data']['access_token'] ?? '',
            'refresh_token' => $resp['data']['refresh_token'] ?? '',
            'expires_in'    => $resp['data']['expires_in'] ?? 86400,
            'advertiser_ids'=> $resp['data']['advertiser_ids'] ?? [],
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
            $pageInfo = $resp['data']['page_info'] ?? [];
            $hasMore = $page > 1 ? ($page <= ($pageInfo['total_page'] ?? 0)) : !empty($list);
            $page++;
        } while ($hasMore);
    }

    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        // 巨量暂无独立的 AdGroup 概念（1级投放），直接 yield 空
        yield from [];
    }

    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
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
            $pageInfo = $resp['data']['page_info'] ?? [];
            $hasMore = $page > 1 ? ($page <= ($pageInfo['total_page'] ?? 0)) : !empty($list);
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
            $pageInfo = $resp['data']['page_info'] ?? [];
            $hasMore = $page > 1 ? ($page <= ($pageInfo['total_page'] ?? 0)) : !empty($list);
            $page++;
        } while ($hasMore);
    }

    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
    {
        $resp = $this->request('POST', '2/campaign/create/', [
            'advertiser_id' => (int) $accountId,
            'campaign_name' => $data->name,
            'budget_mode'   => 'BUDGET_MODE_DAY',
            'budget'        => $data->dailyBudget / 100, // 分 → 元
        ], $accessToken);
        return (string) ($resp['data']['campaign_id'] ?? '');
    }

    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $this->request('POST', '2/campaign/update/', array_filter([
            'advertiser_id' => (int) $accountId,
            'campaign_id'   => $platformId,
            'campaign_name' => $data->name,
            'budget'        => $data->dailyBudget > 0 ? $data->dailyBudget / 100 : null,
        ]), $accessToken);
    }

    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->request('POST', '2/campaign/status/update/', [
            'advertiser_id' => (int) $accountId,
            'campaign_ids'  => [$platformId],
            'opt_status'    => $enabled ? 'ENABLE' : 'DISABLE',
        ], $accessToken);
    }

    // —— 私有方法 ——

    protected function campaignFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'campaign_name' => 'name',
            'budget'        => 'daily_budget',
            'status'        => 'status',
        ], [
            'CAMPAIGN_STATUS_ENABLE'             => 'enabled',
            'CAMPAIGN_STATUS_DISABLE'            => 'paused',
            'CAMPAIGN_STATUS_DELETE'             => 'deleted',
        ], function (array $unified): array {
            if (isset($unified['daily_budget'])) {
                $unified['daily_budget'] = (int) ($unified['daily_budget'] * 100); // 元 → 分
            }
            return $unified;
        });
    }

    protected function creativeFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'creative_id'   => 'platform_creative_id',
            'title'         => 'title',
            'status'        => 'status',
        ], [
            'CREATIVE_STATUS_ENABLE'  => 'enabled',
            'CREATIVE_STATUS_DISABLE' => 'paused',
        ]);
    }

    protected function reportFieldMapping(): FieldMapping
    {
        return new FieldMapping([
            'campaign_id'   => 'platform_campaign_id',
            'stat_cost'     => 'cost',
            'show_cnt'      => 'impressions',
            'click_cnt'     => 'clicks',
            'convert_cnt'   => 'conversions',
            'ctr'           => 'ctr',
            'cpm_platform'  => 'cpm',
            'cpc_platform'  => 'cpc',
            'cvr'           => 'cvr',
        ], [], function (array $unified): array {
            // 巨量引擎返回的金额单位是元，统一转分
            foreach (['cost', 'cpm', 'cpc'] as $field) {
                if (isset($unified[$field])) {
                    $unified[$field] = (int) ($unified[$field] * 100);
                }
            }
            // 百分比统一转数值
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
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
            throw new \RuntimeException(
                'Juliang API error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode)
            );
        }
        return $decoded;
    }
}
```

- [x] **Step 7: Create plugin.php**

```php
<?php
return [
    'enable' => true,
    'name'   => 'ads-platform',
    'version'=> '1.0.0',
];
```

- [x] **Step 8: Create the migration SQL (campaigns, ad_groups, creatives, report_metrics, report_extras)**

```sql
CREATE TABLE IF NOT EXISTS `campaigns` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `platform_account_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `platform_campaign_id` VARCHAR(128) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `daily_budget` BIGINT DEFAULT 0,
    `total_budget` BIGINT DEFAULT 0,
    `status` VARCHAR(32) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `extra` JSON NULL,
    `synced_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_platform_campaign` (`platform_account_id`, `platform_campaign_id`),
    INDEX `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ad_groups` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `campaign_id` BIGINT UNSIGNED NOT NULL,
    `platform_adgroup_id` VARCHAR(128) NOT NULL,
    `name` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(32) DEFAULT NULL,
    `bid_amount` BIGINT DEFAULT 0,
    `bid_type` VARCHAR(32) DEFAULT NULL,
    `targeting` JSON NULL,
    `extra` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_platform_adgroup` (`campaign_id`, `platform_adgroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `creatives` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `ad_group_id` BIGINT UNSIGNED NOT NULL,
    `platform_creative_id` VARCHAR(128) NOT NULL,
    `title` VARCHAR(500) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `media_type` VARCHAR(32) DEFAULT NULL,
    `media_urls` JSON NULL,
    `landing_url` VARCHAR(2048) DEFAULT NULL,
    `extra` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_platform_creative` (`ad_group_id`, `platform_creative_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `report_metrics` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `platform_account_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `campaign_id` BIGINT UNSIGNED DEFAULT NULL,
    `ad_group_id` BIGINT UNSIGNED DEFAULT NULL,
    `creative_id` BIGINT UNSIGNED DEFAULT NULL,
    `date` DATE NOT NULL,
    `granularity` VARCHAR(16) DEFAULT 'daily',
    `cost` BIGINT DEFAULT 0,
    `impressions` BIGINT DEFAULT 0,
    `clicks` BIGINT DEFAULT 0,
    `conversions` DECIMAL(10,2) DEFAULT 0,
    `ctr` DECIMAL(8,4) DEFAULT 0,
    `cpm` DECIMAL(10,2) DEFAULT 0,
    `cpc` DECIMAL(10,2) DEFAULT 0,
    `cvr` DECIMAL(8,4) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_report` (`tenant_id`, `platform`, `platform_account_id`, `campaign_id`, `ad_group_id`, `creative_id`, `date`, `granularity`),
    INDEX `idx_date` (`date`),
    INDEX `idx_campaign_date` (`campaign_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `report_extras` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `report_metric_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `extra` JSON NULL,
    FOREIGN KEY (`report_metric_id`) REFERENCES `report_metrics`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [x] **Step 9: Register the Juliang adapter in AdapterRegistry**

Create `service/plugin/ads-platform/config/bootstrap.php`:

```php
<?php
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\adapter\Juliang;

AdapterRegistry::register(new Juliang());
```

- [x] **Step 10: Commit**

```bash
cd /home/wwwroot/ads-php
git add service/plugin/ads-platform/ -f
git commit -m "feat: add ads-platform plugin with adapter interface and Juliang adapter"
```

---

### Task 4: Create the ads-account account management plugin

**Files:**
- Create: `service/plugin/ads-account/config/plugin.php`
- Create: `service/plugin/ads-account/model/PlatformAccount.php`
- Create: `service/plugin/ads-account/model/AuthToken.php`
- Create: `service/plugin/ads-account/service/OAuthService.php`
- Create: `service/plugin/ads-account/migration/create_platform_accounts.sql`

- [x] **Step 1: Create plugin.php**

```php
<?php
return [
    'enable' => true,
    'name'   => 'ads-account',
    'version'=> '1.0.0',
];
```

- [x] **Step 2: Create the PlatformAccount model**

```php
<?php
namespace plugin\ads_account\model;

use Illuminate\Database\Eloquent\Model;

class PlatformAccount extends Model
{
    protected $table = 'platform_accounts';
    protected $guarded = ['id'];
    protected $casts = [
        'sync_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
        'token_expires_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(\plugin\ads_tenant\model\Tenant::class, 'tenant_id');
    }

    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) return false;
        return $this->token_expires_at->subMinutes(5)->isPast();
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
```

- [x] **Step 3: Create the AuthToken model (for state storage in the OAuth flow)**

```php
<?php
namespace plugin\ads_account\model;

use Illuminate\Database\Eloquent\Model;

class AuthToken extends Model
{
    protected $table = 'auth_tokens';
    protected $guarded = ['id'];
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
```

- [x] **Step 4: Create OAuthService**

```php
<?php
namespace plugin\ads_account\service;

use plugin\ads_account\model\PlatformAccount;
use plugin\ads_account\model\AuthToken;
use plugin\ads_platform\src\AdapterRegistry;
use support\Response;

class OAuthService
{
    public function getAuthUrl(int $tenantId, string $platform, string $redirectUri): array
    {
        $adapter = AdapterRegistry::get($platform);
        if (!$adapter) {
            throw new \InvalidArgumentException("Unsupported platform: $platform");
        }

        $state = bin2hex(random_bytes(16));
        AuthToken::create([
            'tenant_id'   => $tenantId,
            'platform'    => $platform,
            'state'       => $state,
            'redirect_uri'=> $redirectUri,
            'expires_at'  => now()->addMinutes(10),
        ]);

        return [
            'auth_url' => $adapter->buildAuthUrl($redirectUri, $state),
            'state'    => $state,
        ];
    }

    public function handleCallback(int $tenantId, string $platform, string $state, string $code): PlatformAccount
    {
        $authToken = AuthToken::where('state', $state)
            ->where('tenant_id', $tenantId)
            ->where('platform', $platform)
            ->first();

        if (!$authToken || $authToken->isExpired()) {
            throw new \RuntimeException('Invalid or expired state');
        }

        $adapter = AdapterRegistry::get($platform);
        if (!$adapter) {
            throw new \InvalidArgumentException("Unsupported platform: $platform");
        }

        $tokenData = $adapter->exchangeToken($code, $authToken->redirect_uri);

        $account = PlatformAccount::create([
            'tenant_id'               => $tenantId,
            'platform'                => $platform,
            'account_id_on_platform'  => $tokenData['advertiser_ids'][0] ?? '0',
            'access_token'            => $tokenData['access_token'],
            'refresh_token'           => $tokenData['refresh_token'] ?? '',
            'token_expires_at'        => now()->addSeconds($tokenData['expires_in'] ?? 86400),
            'status'                  => 1,
        ]);

        // 尝试拉取账户名称
        if (!empty($tokenData['advertiser_ids'])) {
            try {
                $infos = $adapter->fetchAccountInfo($tokenData['access_token']);
                foreach ($infos as $info) {
                    PlatformAccount::updateOrCreate(
                        [
                            'tenant_id'              => $tenantId,
                            'platform'               => $platform,
                            'account_id_on_platform' => $info['account_id_on_platform'],
                        ],
                        [
                            'account_name'     => $info['account_name'],
                            'access_token'     => $tokenData['access_token'],
                            'refresh_token'    => $tokenData['refresh_token'] ?? '',
                            'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 86400),
                            'status'           => 1,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                // 名称获取失败不阻塞绑定流程
            }
        }

        $authToken->delete();

        return $account;
    }

    public function refreshAccessToken(PlatformAccount $account): void
    {
        $adapter = AdapterRegistry::get($account->platform);
        if (!$adapter || empty($account->refresh_token)) {
            return;
        }

        $tokenData = $adapter->refreshToken($account->refresh_token);

        $account->update([
            'access_token'     => $tokenData['access_token'],
            'refresh_token'    => $tokenData['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 86400),
        ]);
    }
}
```

- [x] **Step 5: Create the migration SQL**

```sql
CREATE TABLE IF NOT EXISTS `platform_accounts` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `account_id_on_platform` VARCHAR(128) NOT NULL,
    `account_name` VARCHAR(255) DEFAULT NULL,
    `access_token` TEXT DEFAULT NULL,
    `refresh_token` VARCHAR(512) DEFAULT NULL,
    `token_expires_at` DATETIME DEFAULT NULL,
    `status` TINYINT DEFAULT 1,
    `sync_enabled` TINYINT DEFAULT 1,
    `last_sync_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_platform_account` (`tenant_id`, `platform`, `account_id_on_platform`),
    INDEX `idx_tenant_platform` (`tenant_id`, `platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `auth_tokens` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `platform` VARCHAR(32) NOT NULL,
    `state` VARCHAR(64) NOT NULL,
    `redirect_uri` VARCHAR(512) DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [x] **Step 6: Commit**

```bash
cd /home/wwwroot/ads-php
git add service/plugin/ads-account/ -f
git commit -m "feat: add ads-account plugin with OAuth flow and platform account management"
```

---

### Task 5: Create the ads-api plugin (RESTful API + JWT auth)

**Files:**
- Create: `service/plugin/ads-api/config/plugin.php`
- Create: `service/plugin/ads-api/config/route.php`
- Create: `service/plugin/ads-api/middleware/AuthMiddleware.php`
- Create: `service/plugin/ads-api/controller/AuthController.php`
- Create: `service/plugin/ads-api/controller/PlatformController.php`
- Create: `service/plugin/ads-api/controller/AccountController.php`
- Create: `service/plugin/ads-api/controller/CampaignController.php`
- Create: `service/plugin/ads-api/controller/DashboardController.php`

- [x] **Step 1: Create plugin.php**

```php
<?php
return [
    'enable' => true,
    'name'   => 'ads-api',
    'version'=> '1.0.0',
];
```

- [x] **Step 2: Create the JWT AuthMiddleware**

```php
<?php
namespace plugin\ads_api\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use support\Response as ApiResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return ApiResponse::error('Unauthorized', 401, 401);
        }

        $token = substr($header, 7);
        try {
            $payload = JWT::decode($token, new Key(config('app.jwt.secret'), 'HS256'));
            $request->userId = $payload->uid;
            $request->tenantId = $payload->tid ?? 1;
        } catch (\Throwable $e) {
            return ApiResponse::error('Token invalid or expired', 401, 401);
        }

        return $handler($request);
    }
}
```

- [x] **Step 3: Create AuthController**

```php
<?php
namespace plugin\ads_api\controller;

use Firebase\JWT\JWT;
use support\Response;

class AuthController
{
    /**
     * POST /api/v1/auth/login
     * Body: { "username": "...", "password": "...", "tenant_id": 1 }
     */
    public function login(\Webman\Http\Request $request): Response
    {
        $username  = $request->post('username', '');
        $password  = $request->post('password', '');
        $tenantId  = (int) $request->post('tenant_id', 1);

        // Phase 1 简化：硬编码管理员账户
        if ($username !== 'admin' || $password !== 'admin123') {
            return Response::error('Invalid credentials', 1001);
        }

        $payload = [
            'uid' => 1,
            'tid' => $tenantId,
            'iat' => time(),
            'exp' => time() + (int) config('app.jwt.ttl', 86400),
        ];
        $token = JWT::encode($payload, config('app.jwt.secret'), 'HS256');

        return Response::success([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => (int) config('app.jwt.ttl', 86400),
            'user'         => [
                'id'       => 1,
                'username' => $username,
                'role'     => 'admin',
            ],
        ]);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(\Webman\Http\Request $request): Response
    {
        return Response::success([
            'id'       => $request->userId ?? 1,
            'username' => 'admin',
            'role'     => 'admin',
            'tenant_id'=> $request->tenantId ?? 1,
        ]);
    }
}
```

- [x] **Step 4: Create PlatformController**

```php
<?php
namespace plugin\ads_api\controller;

use plugin\ads_platform\src\AdapterRegistry;
use support\Response;

class PlatformController
{
    /**
     * GET /api/v1/platforms
     */
    public function index(): Response
    {
        return Response::success(AdapterRegistry::all());
    }

    /**
     * GET /api/v1/platforms/{code}/oauth-url?redirect_uri=...
     */
    public function oauthUrl(\Webman\Http\Request $request, string $code): Response
    {
        $redirectUri = $request->get('redirect_uri', '');
        if (!$redirectUri) {
            return Response::error('redirect_uri is required');
        }

        $adapter = AdapterRegistry::get($code);
        if (!$adapter) {
            return Response::error("Unsupported platform: $code");
        }

        $state = bin2hex(random_bytes(16));
        $url = $adapter->buildAuthUrl($redirectUri, $state);

        // 存储 state 用于回调验证
        \plugin\ads_account\model\AuthToken::create([
            'tenant_id'   => $request->tenantId ?? 1,
            'platform'    => $code,
            'state'       => $state,
            'redirect_uri'=> $redirectUri,
            'expires_at'  => now()->addMinutes(10),
        ]);

        return Response::success(['auth_url' => $url, 'state' => $state]);
    }

    /**
     * POST /api/v1/platforms/{code}/callback
     * Body: { "state": "...", "code": "..." }
     */
    public function callback(\Webman\Http\Request $request, string $code): Response
    {
        $state = $request->post('state', '');
        $authCode = $request->post('code', '');

        try {
            $oauth = new \plugin\ads_account\service\OAuthService();
            $account = $oauth->handleCallback(
                $request->tenantId ?? 1,
                $code,
                $state,
                $authCode
            );
            return Response::success(['account_id' => $account->id]);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
```

- [x] **Step 5: Create AccountController**

```php
<?php
namespace plugin\ads_api\controller;

use plugin\ads_account\model\PlatformAccount;
use support\Response;

class AccountController
{
    /**
     * GET /api/v1/accounts
     */
    public function index(\Webman\Http\Request $request): Response
    {
        $query = PlatformAccount::query()
            ->where('tenant_id', $request->tenantId ?? 1);

        if ($platform = $request->get('platform')) {
            $query->byPlatform($platform);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        return Response::paginated(
            $paginator->items(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage()
        );
    }

    /**
     * GET /api/v1/accounts/{id}
     */
    public function show(int $id): Response
    {
        $account = PlatformAccount::findOrFail($id);
        return Response::success($account);
    }

    /**
     * DELETE /api/v1/accounts/{id}
     */
    public function destroy(int $id): Response
    {
        $account = PlatformAccount::findOrFail($id);
        $account->update(['status' => 0]);
        return Response::success(null, 'Account disabled');
    }

    /**
     * POST /api/v1/accounts/{id}/sync
     */
    public function sync(\Webman\Http\Request $request, int $id): Response
    {
        $account = PlatformAccount::findOrFail($id);
        // Phase 1: 触发同步任务（简化实现——直接标记为已同步）
        $account->update(['last_sync_at' => now()]);
        return Response::success(null, 'Sync triggered');
    }
}
```

- [x] **Step 6: Create CampaignController**

```php
<?php
namespace plugin\ads_api\controller;

use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\CampaignData;
use plugin\ads_account\model\PlatformAccount;
use support\Response;
use Illuminate\Database\Capsule\Manager as DB;

class CampaignController
{
    /**
     * GET /api/v1/campaigns
     */
    public function index(\Webman\Http\Request $request): Response
    {
        $tenantId = $request->tenantId ?? 1;
        $query = DB::table('campaigns')->where('tenant_id', $tenantId);

        if ($platform = $request->get('platform')) {
            $query->where('platform', $platform);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($keyword = $request->get('keyword')) {
            $query->where('name', 'like', "%{$keyword}%");
        }

        $sort = $request->get('sort', 'id');
        $query->orderBy($sort, 'desc');

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        // 仪表盘汇总
        $summary = (array) DB::table('report_metrics')
            ->where('tenant_id', $tenantId)
            ->where('date', date('Y-m-d'))
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as total_impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as total_clicks')
            ->selectRaw('COALESCE(AVG(ctr), 0) as avg_ctr')
            ->selectRaw('COALESCE(AVG(cvr), 0) as avg_cvr')
            ->first();

        return Response::paginated(
            $paginator->items(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
            $summary
        );
    }

    /**
     * POST /api/v1/campaigns
     */
    public function store(\Webman\Http\Request $request): Response
    {
        $platform = $request->post('platform');
        $accountId = (int) $request->post('platform_account_id');

        $account = PlatformAccount::findOrFail($accountId);

        $adapter = AdapterRegistry::get($platform);
        if (!$adapter) {
            return Response::error("Unsupported platform: $platform");
        }

        $data = CampaignData::fromArray($request->post());
        try {
            $platformCampaignId = $adapter->createCampaign(
                $account->access_token,
                $account->account_id_on_platform,
                $data
            );

            // 写入统一数据表
            $id = DB::table('campaigns')->insertGetId([
                'tenant_id'           => $request->tenantId ?? 1,
                'platform_account_id' => $accountId,
                'platform'            => $platform,
                'platform_campaign_id'=> $platformCampaignId,
                'name'                => $data->name,
                'daily_budget'        => $data->dailyBudget,
                'total_budget'        => $data->totalBudget ?? 0,
                'status'              => 'enabled',
                'extra'               => json_encode($data->extra),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            return Response::success(['id' => $id, 'platform_campaign_id' => $platformCampaignId]);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * GET /api/v1/campaigns/{id}
     */
    public function show(int $id): Response
    {
        $campaign = DB::table('campaigns')->find($id);
        if (!$campaign) {
            return Response::error('Campaign not found');
        }

        // 附带今日数据
        $todayMetrics = DB::table('report_metrics')
            ->where('campaign_id', $id)
            ->where('date', date('Y-m-d'))
            ->first();

        return Response::success([
            'campaign' => $campaign,
            'today'    => $todayMetrics,
        ]);
    }

    /**
     * PUT /api/v1/campaigns/{id}
     */
    public function update(\Webman\Http\Request $request, int $id): Response
    {
        $campaign = DB::table('campaigns')->find($id);
        if (!$campaign) {
            return Response::error('Campaign not found');
        }

        $account = PlatformAccount::find($campaign->platform_account_id);
        $adapter = AdapterRegistry::get($campaign->platform);
        $data = CampaignData::fromArray($request->post());

        try {
            $adapter->updateCampaign(
                $account->access_token,
                $account->account_id_on_platform,
                $campaign->platform_campaign_id,
                $data
            );

            DB::table('campaigns')->where('id', $id)->update([
                'name'         => $data->name,
                'daily_budget' => $data->dailyBudget,
                'updated_at'   => now(),
            ]);

            return Response::success(null, 'Updated');
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * POST /api/v1/campaigns/{id}/toggle
     * Body: { "enabled": false }
     */
    public function toggle(\Webman\Http\Request $request, int $id): Response
    {
        $campaign = DB::table('campaigns')->find($id);
        if (!$campaign) {
            return Response::error('Campaign not found');
        }

        $enabled = (bool) $request->post('enabled', true);
        $account = PlatformAccount::find($campaign->platform_account_id);
        $adapter = AdapterRegistry::get($campaign->platform);

        try {
            $adapter->toggleCampaign(
                $account->access_token,
                $account->account_id_on_platform,
                $campaign->platform_campaign_id,
                $enabled
            );

            DB::table('campaigns')->where('id', $id)->update([
                'status'     => $enabled ? 'enabled' : 'paused',
                'updated_at' => now(),
            ]);

            return Response::success(null, $enabled ? 'Enabled' : 'Paused');
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
```

- [x] **Step 7: Create DashboardController**

```php
<?php
namespace plugin\ads_api\controller;

use support\Response;
use Illuminate\Database\Capsule\Manager as DB;

class DashboardController
{
    /**
     * GET /api/v1/reports/summary
     */
    public function summary(\Webman\Http\Request $request): Response
    {
        $tenantId = $request->tenantId ?? 1;
        $dateStart = $request->get('date_start', date('Y-m-d'));
        $dateEnd   = $request->get('date_end', date('Y-m-d'));

        // 总览指标
        $overview = (array) DB::table('report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as total_impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as total_clicks')
            ->selectRaw('COALESCE(SUM(conversions), 0) as total_conversions')
            ->selectRaw('CASE WHEN SUM(impressions) > 0 THEN ROUND(SUM(clicks)/SUM(impressions)*100, 2) ELSE 0 END as avg_ctr')
            ->selectRaw('CASE WHEN SUM(clicks) > 0 THEN ROUND(SUM(conversions)/SUM(clicks)*100, 2) ELSE 0 END as avg_cvr')
            ->selectRaw('CASE WHEN SUM(cost) > 0 THEN ROUND(SUM(cost)/SUM(conversions)/100, 2) ELSE 0 END as avg_cpa')
            ->first();

        // 按平台汇总
        $byPlatform = DB::table('report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->groupBy('platform')
            ->select('platform')
            ->selectRaw('COALESCE(SUM(cost), 0) as cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as clicks')
            ->selectRaw('COALESCE(SUM(conversions), 0) as conversions')
            ->orderByDesc('cost')
            ->get();

        // 每日趋势
        $daily = DB::table('report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->groupBy('date', 'platform')
            ->orderBy('date')
            ->select('date', 'platform')
            ->selectRaw('COALESCE(SUM(cost), 0) as cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
            ->get();

        return Response::success([
            'overview'    => $overview,
            'by_platform' => $byPlatform,
            'daily'       => $daily,
        ]);
    }
}
```

- [x] **Step 8: Create the route config route.php**

```php
<?php
use plugin\ads_api\middleware\AuthMiddleware;
use plugin\ads_api\controller\{
    AuthController, PlatformController, AccountController,
    CampaignController, DashboardController
};

// 公开路由
\Webman\Route::post('/api/v1/auth/login', [AuthController::class, 'login']);
\Webman\Route::get('/api/v1/platforms', [PlatformController::class, 'index']);

// 需要认证的路由
\Webman\Route::group('/api/v1', function () {
    \Webman\Route::get('/auth/me', [AuthController::class, 'me']);

    // 平台
    \Webman\Route::get('/platforms/{code}/oauth-url', [PlatformController::class, 'oauthUrl']);
    \Webman\Route::post('/platforms/{code}/callback', [PlatformController::class, 'callback']);

    // 账户
    \Webman\Route::get('/accounts', [AccountController::class, 'index']);
    \Webman\Route::get('/accounts/{id}', [AccountController::class, 'show']);
    \Webman\Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);
    \Webman\Route::post('/accounts/{id:\d+}/sync', [AccountController::class, 'sync']);

    // 广告计划
    \Webman\Route::get('/campaigns', [CampaignController::class, 'index']);
    \Webman\Route::post('/campaigns', [CampaignController::class, 'store']);
    \Webman\Route::get('/campaigns/{id}', [CampaignController::class, 'show']);
    \Webman\Route::put('/campaigns/{id}', [CampaignController::class, 'update']);
    \Webman\Route::post('/campaigns/{id:\d+}/toggle', [CampaignController::class, 'toggle']);

    // 仪表盘
    \Webman\Route::get('/reports/summary', [DashboardController::class, 'summary']);
})->middleware([AuthMiddleware::class]);
```

- [x] **Step 9: Commit**

```bash
cd /home/wwwroot/ads-php
git add service/plugin/ads-api/ -f
git commit -m "feat: add ads-api plugin with auth, account, campaign, and dashboard endpoints"
```

---

### Task 6: Initialize the webman-admin v2 admin backend project

**Files:**
- Create: `admin/package.json`
- Create: `admin/vite.config.ts`
- Create: `admin/tsconfig.json`
- Create: `admin/index.html`
- Create: `admin/src/main.ts`
- Create: `admin/src/App.vue`
- Create: `admin/src/router/index.ts`
- Create: `admin/src/api/index.ts`

- [x] **Step 1: Create package.json**

```json
{
  "name": "ads-admin",
  "private": true,
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vue-tsc -b && vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "vue": "^3.5",
    "vue-router": "^4.4",
    "pinia": "^2.2",
    "axios": "^1.7",
    "element-plus": "^2.8",
    "echarts": "^5.5",
    "vue-echarts": "^7.0",
    "@element-plus/icons-vue": "^2.3",
    "dayjs": "^1.11"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.1",
    "typescript": "^5.5",
    "vite": "^6.0",
    "vue-tsc": "^2.1",
    "@types/node": "^22.0"
  }
}
```

- [x] **Step 2: Create vite.config.ts**

```typescript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8787',
        changeOrigin: true,
      },
    },
  },
})
```

- [x] **Step 3: Create tsconfig.json**

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "strict": true,
    "jsx": "preserve",
    "resolveJsonModule": true,
    "isolatedModules": true,
    "esModuleInterop": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "skipLibCheck": true,
    "noEmit": true,
    "baseUrl": ".",
    "paths": {
      "@/*": ["src/*"]
    }
  },
  "include": ["src/**/*.ts", "src/**/*.d.ts", "src/**/*.vue"]
}
```

- [x] **Step 4: Create index.html**

```html
<!DOCTYPE html>
<html lang="zh-CN">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/vite.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>广告管理系统</title>
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="/src/main.ts"></script>
  </body>
</html>
```

- [x] **Step 5: Create src/main.ts**

```typescript
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import zhCn from 'element-plus/es/locale/lang/zh-cn'
import App from './App.vue'
import router from './router'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(ElementPlus, { locale: zhCn })
app.mount('#app')
```

- [x] **Step 6: Create src/App.vue**

```vue
<template>
  <router-view />
</template>
```

- [x] **Step 7: Create src/router/index.ts**

```typescript
import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/login/LoginPage.vue'),
    meta: { title: '登录' },
  },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/DashboardPage.vue'),
        meta: { title: '仪表盘' },
      },
      {
        path: 'accounts',
        name: 'Accounts',
        component: () => import('@/views/account/AccountList.vue'),
        meta: { title: '账户管理' },
      },
      {
        path: 'accounts/bind',
        name: 'AccountBind',
        component: () => import('@/views/account/AccountBind.vue'),
        meta: { title: '绑定账户' },
      },
      {
        path: 'campaigns',
        name: 'Campaigns',
        component: () => import('@/views/campaign/CampaignList.vue'),
        meta: { title: '广告计划' },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
```

- [x] **Step 8: Create src/api/index.ts (Axios wrapper)**

```typescript
import axios from 'axios'
import type { AxiosInstance, AxiosResponse } from 'axios'
import { ElMessage } from 'element-plus'

const api: AxiosInstance = axios.create({
  baseURL: '/api/v1',
  timeout: 15000,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response: AxiosResponse) => {
    const { code, message, data } = response.data
    if (code !== 0) {
      ElMessage.error(message || '请求失败')
      return Promise.reject(new Error(message))
    }
    return data
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('access_token')
      window.location.href = '/login'
    }
    ElMessage.error(error.message || '网络错误')
    return Promise.reject(error)
  }
)

export default api
export { api }
```

- [x] **Step 9: Install dependencies and verify**

Run: `cd admin && npm install && npm run dev`
Expected: Vite dev server starts on port 5173

- [x] **Step 10: Commit**

```bash
cd /home/wwwroot/ads-php
git add admin/ -f
git commit -m "feat: initialize webman-admin v2 project with Vue3+TS+Element Plus"
```

---

### Task 7: Admin backend layout & login page

**Files:**
- Create: `admin/src/components/layout/AppLayout.vue`
- Create: `admin/src/components/layout/SideNav.vue`
- Create: `admin/src/components/layout/TopBar.vue`
- Create: `admin/src/views/login/LoginPage.vue`
- Create: `admin/src/stores/auth.ts`
- Create: `admin/src/api/auth.ts`

- [x] **Step 1: Create the auth store**

```typescript
// src/stores/auth.ts
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { authApi } from '@/api/auth'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('access_token') || '')
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  async function login(username: string, password: string) {
    const data = await authApi.login(username, password)
    token.value = data.access_token
    user.value = data.user
    localStorage.setItem('access_token', data.access_token)
    localStorage.setItem('user', JSON.stringify(data.user))
    router.push('/dashboard')
  }

  function logout() {
    token.value = ''
    user.value = null
    localStorage.removeItem('access_token')
    localStorage.removeItem('user')
    router.push('/login')
  }

  return { token, user, login, logout }
})
```

- [x] **Step 2: Create the auth API**

```typescript
// src/api/auth.ts
import { api } from './index'

export const authApi = {
  login(username: string, password: string) {
    return api.post('/auth/login', { username, password })
  },
  me() {
    return api.get('/auth/me')
  },
}
```

- [x] **Step 3: Create LoginPage.vue**

```vue
<template>
  <div class="login-container">
    <div class="login-card">
      <h2>广告管理系统</h2>
      <el-form ref="formRef" :model="form" :rules="rules" size="large">
        <el-form-item prop="username">
          <el-input v-model="form.username" placeholder="用户名" prefix-icon="User" />
        </el-form-item>
        <el-form-item prop="password">
          <el-input v-model="form.password" type="password" placeholder="密码" show-password prefix-icon="Lock" @keyup.enter="handleLogin" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" style="width:100%" @click="handleLogin">登 录</el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import type { FormInstance } from 'element-plus'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const formRef = ref<FormInstance>()
const loading = ref(false)
const form = reactive({ username: 'admin', password: 'admin123' })
const rules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
}

async function handleLogin() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  loading.value = true
  try {
    await authStore.login(form.username, form.password)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f2f5;
}
.login-card {
  width: 400px;
  padding: 40px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.1);
}
.login-card h2 {
  text-align: center;
  margin-bottom: 30px;
  color: #303133;
}
</style>
```

- [x] **Step 4: Create SideNav.vue**

```vue
<template>
  <el-menu
    :default-active="route.path"
    router
    :collapse="collapsed"
    background-color="#304156"
    text-color="#bfcbd9"
    active-text-color="#409EFF"
  >
    <el-menu-item index="/dashboard">
      <el-icon><DataAnalysis /></el-icon>
      <span>仪表盘</span>
    </el-menu-item>

    <el-sub-menu index="ads">
      <template #title>
        <el-icon><Promotion /></el-icon>
        <span>广告管理</span>
      </template>
      <el-menu-item index="/campaigns">广告计划</el-menu-item>
      <el-menu-item index="/creatives">广告创意</el-menu-item>
    </el-sub-menu>

    <el-menu-item index="/accounts">
      <el-icon><User /></el-icon>
      <span>账户管理</span>
    </el-menu-item>

    <el-menu-item index="/reports">
      <el-icon><Document /></el-icon>
      <span>数据报表</span>
    </el-menu-item>
  </el-menu>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'
import { DataAnalysis, Promotion, User, Document } from '@element-plus/icons-vue'

defineProps<{ collapsed: boolean }>()
const route = useRoute()
</script>
```

- [x] **Step 5: Create TopBar.vue**

```vue
<template>
  <div class="top-bar">
    <div class="left">
      <el-icon class="collapse-btn" @click="$emit('toggle')">
        <Fold v-if="!collapsed" /><Expand v-else />
      </el-icon>
      <span class="title">{{ title }}</span>
    </div>
    <div class="right">
      <span class="username">{{ authStore.user?.username }}</span>
      <el-button text @click="authStore.logout()">退出</el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Fold, Expand } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'

defineProps<{ collapsed: boolean }>()
defineEmits(['toggle'])
const authStore = useAuthStore()
const route = useRoute()
const title = computed(() => route.meta.title as string || '')
</script>

<style scoped>
.top-bar {
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  background: #fff;
  border-bottom: 1px solid #e6e6e6;
}
.left { display: flex; align-items: center; gap: 12px; }
.collapse-btn { cursor: pointer; font-size: 18px; }
.title { font-size: 16px; font-weight: 500; }
.right { display: flex; align-items: center; gap: 12px; }
</style>
```

- [x] **Step 6: Create AppLayout.vue**

```vue
<template>
  <div class="app-layout">
    <div class="sidebar" :style="{ width: collapsed ? '64px' : '200px' }">
      <SideNav :collapsed="collapsed" />
    </div>
    <div class="main">
      <TopBar :collapsed="collapsed" @toggle="collapsed = !collapsed" />
      <div class="content">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import SideNav from './SideNav.vue'
import TopBar from './TopBar.vue'

const collapsed = ref(false)
</script>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
}
.sidebar {
  background: #304156;
  transition: width 0.3s;
  overflow: hidden;
}
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f0f2f5;
}
.content {
  flex: 1;
  padding: 16px;
}
</style>
```

- [x] **Step 7: Commit**

```bash
cd /home/wwwroot/ads-php
git add admin/src/stores/ admin/src/api/auth.ts admin/src/views/login/ admin/src/components/layout/ -f
git commit -m "feat: add login page and admin layout with sidebar navigation"
```

---

