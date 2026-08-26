# Multi-Platform Ad Management System Design

[中文](docs/superpowers/specs/design.md) | [English](docs/superpowers/specs/design.en.md) | [한국어](docs/superpowers/specs/design.ko.md) | [Русский](docs/superpowers/specs/design.ru.md) | [Deutsch](docs/superpowers/specs/design.de.md) | [Français](docs/superpowers/specs/design.fr.md) | [Español](docs/superpowers/specs/design.es.md) | [Português](docs/superpowers/specs/design.pt.md) | [हिन्दी](docs/superpowers/specs/design.hi.md) | [العربية](docs/superpowers/specs/design.ar.md) | [বাংলা](docs/superpowers/specs/design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/design.id.md) | [日本語](docs/superpowers/specs/design.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## Overview

A unified ad management platform integrating **29 advertising platforms**, covering major domestic and international ad vendors, with campaign management, cross-platform reporting, and real-time alert monitoring.

- **service** — user-facing business service, webman v2 (PHP 8.2+), listening on :8788
- **admin** — standalone admin panel, webman-admin v2 (PHP backend :8789 + Vue 3 SPA)
- **apps** — client apps, Flutter (iOS/Android/Web PC) + HarmonyOS (ArkTS)
- **Infrastructure**: Docker + Nginx + MySQL 8.0 + Redis 7 + Elasticsearch

Business scenarios cover self-serve delivery, SaaS multi-tenancy, and agency operations.

### Communication Architecture

```
admin:8789 (管理后台)          service:8788 (业务API)
┌─────────────────┐    HTTP    ┌──────────────────┐
│ webman-admin v2 │ ────────→  │ webman v2 API    │
│ PHP后端+Vue SPA │ ServiceProxy│ 7插件・29适配器   │
└────────┬────────┘            └────────┬─────────┘
         │                              │
    管理操作                        业务数据
  (用户/RBAC/审计)           (广告/报表/告警/同步)
```

---

## Overall Architecture

### System Architecture Diagram

```text
                          ┌──────────────────────────────┐
                          │          Nginx :80           │
                          │   / -> admin    /api -> svc  │
                          └──────┬──────────────┬────────┘
                                 │              │
              ┌──────────────────┼──────┐       │
              │      Admin :8789 │      │       │
              │  ┌───────────────────┐  │       │
              │  │  webman-admin v2  │  │       │
              │  │  PHP + Vue3 SPA   │  │       │
              │  │  RBAC / Audit     │──┼───────┘
              │  └───────────────────┘  │   ServiceProxy
              │         ServiceProxy ───┼── (HTTP call)
              └─────────────────────────┘        │
                                                 │
         ┌───────────────────────────────────────┘
         │             Service :8788
         │  ┌─────────────────────────────────────┐
         │  │ 7 Middleware: CORS, RateLimit,      │
         │  │ SQLGuard, Valid, Encrypt, JWT,      │
         │  │ TenantIdentify                      │
         │  └─────────────────────────────────────┘
         │  ┌──────────┐ ┌──────────┐ ┌─────────┐
         │  │ Campaign │ │  Report  │ │  Alert  │
         │  │ Manager  │ │  Engine  │ │  Engine │
         │  └────┬─────┘ └────┬─────┘ └────┬────┘
         │       └────────────┼────────────┘
         │            ┌───────┴────────┐
         │            │ 29 Platform    │
         │            │ Adapters       │
         │            └───────┬────────┘
         └────────────────────┼──────────────┘
                              │
              ┌───────────────┼───────────────┐
              │               │               │
         ┌────┴────┐   ┌─────┴─────┐   ┌─────┴─────┐
         │  MySQL  │   │  Redis 7  │   │  ES 搜索  │
         │ 14 tables│   │ 缓存/队列 │   │  scout    │
         └─────────┘   └───────────┘   └───────────┘
                              │
                    ┌─────────┴─────────┐
                    │   29 广告平台 API  │
                    │ 巨量/百度/淘宝/... │
                    └───────────────────┘
```

```
┌──────────────────────────────────────────────────────────┐
│                     Client Layer                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐       │
│  │ Flutter  │  │HarmonyOS │  │ webman-admin v2  │       │
│  │ App (PC) │  │   App    │  │ (Vue3+TS+Element)│       │
│  └────┬─────┘  └────┬─────┘  └────────┬─────────┘       │
└───────┼──────────────┼────────────────┼─────────────────┘
        │              │                │
        └──────────────┼────────────────┘
                       │ HTTP (hashids ID + JWT + encryption)
           ┌───────────┴───────────┐
           │  Middleware Pipeline   │
           │  CORS → RateLimit →   │
           │  SQLGuard → Valid →   │
           │  Encryption → Tenant  │
           └───────────┬───────────┘
                       │
    ┌──────────────────┼──────────────────┐
    │            Service Layer             │
    │  ┌──────────┐ ┌──────┐ ┌──────────┐ │
    │  │ Campaign │ │Report│ │  Alert   │ │
    │  │ Manager  │ │Engine│ │  Engine  │ │
    │  └────┬─────┘ └──┬───┘ └────┬─────┘ │
    │       └──────────┼──────────┘        │
    │          ┌───────┴────────┐          │
    │          │ Platform       │          │
    │          │ Adapter Layer  │          │
    │          │ (29 adapters)  │          │
    │          └───────┬────────┘          │
    └──────────────────┼───────────────────┘
                       │
    ┌──────────────────┼───────────────────┐
    │       Platform Adapters (29)         │
    │  国内16: Juliang/Baidu/Taobao/...    │
    │  国际13: Google/Meta/TikTok/...       │
    └──────────────────┼───────────────────┘
                       │
              外部广告平台 APIs
```

---

## 1. Service Module Breakdown

webman v2 plugin architecture, 7 plugins under `service/plugin/`:

```
service/
├── config/                     # 配置（带注释）
│   ├── app.php, database.php, redis.php
│   ├── middleware.php, server.php
│   ├── log.php, container.php, scout.php
├── support/                    # Erik Stack 工具类
│   ├── ApiResponse.php         # 统一 JSON 响应（含 hashids ID 编码）
│   ├── SnowflakeTrait.php      # 分布式 ID 生成
│   ├── HashidsService.php      # API ID 加解密
│   ├── CacheService.php        # Redis 缓存层
│   └── QueryOptimizer.php      # SQL 优化器
├── plugin/
│   ├── ads-tenant/             # 多租户管理
│   │   ├── model/Tenant.php
│   │   ├── middleware/TenantIdentify.php
│   │   └── migration/create_tenants.sql
│   │
│   ├── ads-account/            # 广告账户 & OAuth 授权（含 encryptable 加密）
│   │   ├── model/PlatformAccount.php, AuthToken.php
│   │   ├── service/OAuthService.php
│   │   └── migration/create_platform_accounts.sql
│   │
│   ├── ads-platform/           # 平台适配器核心
│   │   ├── src/PlatformAdapter.php, AdapterRegistry.php, FieldMapping.php
│   │   ├── src/CampaignData.php, ReportRequest.php
│   │   ├── adapter/            # 29 个适配器（国内16 + 国际13）
│   │   └── migration/create_campaign_tables.sql
│   │
│   ├── ads-api/                # RESTful API（25+ 端点）
│   │   ├── controller/         # 7 个控制器
│   │   ├── middleware/          # 7 个中间件
│   │   └── config/route.php
│   │
│   ├── ads-task/               # 定时任务调度
│   │   ├── task/DataSyncTask.php, TokenRefreshTask.php, AlertCheckTask.php
│   │   └── config/cron.php
│   │
│   ├── ads-report/             # 报表引擎 & 导出
│   │   ├── service/ReportBuilder.php, ReportExporter.php, PdfExporter.php
│   │   └── config/plugin.php
│   │
│   └── ads-alert/              # 告警监控
│       ├── model/AlertRule.php, AlertLog.php
│       ├── service/AlertEngine.php, NotificationService.php
│       └── migration/create_alerts.sql
```

---

## 2. Platform Adapters

### Adapted Platforms (29)

| Region | # | Platform | Adapter Class | Auth | Money | Reports |
|--------|---|----------|---------------|------|-------|---------|
| China | 1 | Juliang (Ocean Engine) | Juliang | OAuth2 Access-Token | yuan→fen | Sync pagination |
| China | 2 | Baidu Marketing | Baidu | OAuth2 + Envelope Sign | yuan→fen | Async polling |
| China | 3 | Taobao/Alimama | Taobao | OAuth2 + MD5 Sign | yuan→fen | Sync pagination |
| China | 4 | Tencent Ads | Tencent | OAuth2 + nonce | fen native | Sync pagination |
| China | 5 | Kuaishou (Kwai) | Kuaishou | OAuth2 URL Params | yuan→fen | Sync pagination |
| China | 6 | Xiaohongshu (RED) | Xiaohongshu | OAuth2 Bearer | fen native | Sync pagination |
| China | 7 | Weibo Fans Connect | Weibo | OAuth2 Bearer | fen native | Sync pagination |
| China | 8 | Bilibili Huahuo | Bilibili | OAuth2 Bearer | fen native | Sync pagination |
| China | 9 | Youku Ads | Youku | OAuth2 + MD5 Sign | yuan→fen | Sync pagination |
| China | 10 | Meituan Ads | Meituan | OAuth2 Bearer | fen native | Sync pagination |
| China | 11 | Zhihu Ads | Zhihu | OAuth2 Bearer | yuan→fen | Sync pagination |
| China | 12 | Qihoo 360 | Qihoo360 | API Key + Sign | yuan→fen | Sync pagination |
| China | 13 | Sogou | Sogou | API Key + Sign | yuan→fen | Sync pagination |
| China | 14 | Umeng | Umeng | API Key + MD5 | yuan→fen | Sync pagination |
| China | 15 | JD Jingzhuntong | Jingdong | OAuth2 + MD5 | yuan→fen | Sync pagination |
| China | 16 | Pinduoduo Ads | Pinduoduo | OAuth2 + Custom Sign | fen native | Sync pagination |
| Intl | 17 | Google Ads | Google | OAuth2 + GAQL | micro-dollars→fen | pageToken |
| Intl | 18 | YouTube Ads | Youtube | OAuth2 + GAQL | micro-dollars→fen | pageToken |
| Intl | 19 | Meta Ads | Meta | OAuth2 URL Params | fen native | Async |
| Intl | 20 | TikTok Ads | Tiktok | OAuth2 Access-Token | micro-dollars→fen | Sync pagination |
| Intl | 21 | LinkedIn Ads | Linkedin | OAuth2 Bearer | micro-dollars→fen | Sync pagination |
| Intl | 22 | Snapchat Ads | Snapchat | OAuth2 Bearer | micro-dollars→fen | Sync pagination |
| Intl | 23 | Pinterest Ads | Pinterest | OAuth2 Bearer | micro-dollars→fen | Sync pagination |
| Intl | 24 | Twitter/X Ads | Twitter | OAuth2 Bearer | micro-dollars→fen | Sync pagination |
| Intl | 25 | Amazon Ads | Amazon | OAuth2 + Profile | fen native | Async |
| Intl | 26 | The Trade Desk | TheTradeDesk | HMAC-SHA256 | fen native | Async |
| Intl | 27 | Spotify Ads | Spotify | OAuth2 Bearer | fen native | Async |
| Intl | 28 | Twitch Ads | Twitch | OAuth2 Bearer+ClientId | fen native | Sync |
| Intl | 29 | Netflix Ads | Netflix | OAuth2 client_credentials | fen native | Sync |

### Interface Definition

```php
interface PlatformAdapter
{
    public function code(): string;
    public function name(): string;
    public function capabilities(): array;

    // 授权
    public function buildAuthUrl(string $redirectUri, string $state): string;
    public function exchangeToken(string $code, string $redirectUri): array;
    public function refreshToken(string $refreshToken): array;
    public function fetchAccountInfo(string $accessToken): array;

    // 数据同步（Generator 流式）
    public function fetchCampaigns(string $accessToken, string $accountId): Generator;
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): Generator;
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): Generator;
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): Generator;

    // 投放操作
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string;
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void;
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void;
}
```

### Field Mapping

Each adapter converts platform raw fields to the unified model via `FieldMapping`; platform-specific fields automatically fall into the `extra` JSON. Money is unified to **fen** (CNY) / **fen-cents** (USD).

```php
// 巨量引擎：元→分，百分比→小数
protected array $fieldMap = [
    'campaign_id' => 'platform_campaign_id',
    'stat_cost'   => 'cost',         // 元 → ×100 → 分
    'show_cnt'    => 'impressions',
    'click_cnt'   => 'clicks',
    'ctr'         => 'ctr',          // 百分比 → ÷100 → 小数
];

// Google Ads：微元→分
protected array $fieldMap = [
    'campaign.id'                => 'platform_campaign_id',
    'metrics.cost_micros'        => 'cost',         // 微元 → ÷10000 → 分
    'metrics.impressions'        => 'impressions',
    'metrics.clicks'             => 'clicks',
];
```

---

## 3. Database Design

### Naming Conventions
- Table prefix: `erik_`
- Primary key: `BIGINT UNSIGNED PRIMARY KEY` (no auto-increment, Snowflake ID generation)
- Engine: InnoDB, charset: utf8mb4

### Core Tables (13)

```sql
-- 租户
CREATE TABLE erik_tenants (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    domain VARCHAR(255) DEFAULT NULL,
    db_type ENUM('shared','dedicated') DEFAULT 'shared',
    db_config JSON NULL,
    plan ENUM('free','pro','enterprise') DEFAULT 'free',
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain_status (domain, status)
);

-- 平台账户 (access_token/refresh_token 由 encryptable 自动加解密)
CREATE TABLE erik_platform_accounts (
    id BIGINT UNSIGNED PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(32) NOT NULL,
    account_id_on_platform VARCHAR(128) NOT NULL,
    account_name VARCHAR(255),
    access_token TEXT,
    refresh_token VARCHAR(512),
    token_expires_at DATETIME,
    status TINYINT DEFAULT 1,
    sync_enabled TINYINT DEFAULT 1,
    last_sync_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_platform_account (tenant_id, platform, account_id_on_platform),
    INDEX idx_tenant_platform (tenant_id, platform)
);

-- OAuth 状态 Token
CREATE TABLE erik_auth_tokens (
    id BIGINT UNSIGNED PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(32) NOT NULL,
    state VARCHAR(64) NOT NULL,
    redirect_uri VARCHAR(512),
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_state (state)
);

-- 统一广告计划
CREATE TABLE erik_campaigns (
    id BIGINT UNSIGNED PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    platform_account_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(32) NOT NULL,
    platform_campaign_id VARCHAR(128) NOT NULL,
    name VARCHAR(255) NOT NULL,
    daily_budget BIGINT DEFAULT 0,       -- 单位：分
    total_budget BIGINT DEFAULT 0,
    status VARCHAR(32),
    start_date DATE,
    end_date DATE,
    extra JSON,
    synced_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_platform_campaign (platform_account_id, platform_campaign_id),
    INDEX idx_tenant (tenant_id)
);

-- 统一广告组
CREATE TABLE erik_ad_groups (
    id BIGINT UNSIGNED PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    platform_adgroup_id VARCHAR(128) NOT NULL,
    name VARCHAR(255),
    status VARCHAR(32),
    bid_amount BIGINT DEFAULT 0,
    bid_type VARCHAR(32),
    targeting JSON,
    extra JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_platform_adgroup (campaign_id, platform_adgroup_id)
);

-- 统一创意
CREATE TABLE erik_creatives (
    id BIGINT UNSIGNED PRIMARY KEY,
    ad_group_id BIGINT UNSIGNED NOT NULL,
    platform_creative_id VARCHAR(128) NOT NULL,
    title VARCHAR(500),
    description TEXT,
    media_type VARCHAR(32),
    media_urls JSON,
    landing_url VARCHAR(2048),
    extra JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_platform_creative (ad_group_id, platform_creative_id)
);

-- 报表核心指标
CREATE TABLE erik_report_metrics (
    id BIGINT UNSIGNED PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    platform_account_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(32) NOT NULL,
    campaign_id BIGINT UNSIGNED,
    ad_group_id BIGINT UNSIGNED,
    creative_id BIGINT UNSIGNED,
    date DATE NOT NULL,
    granularity VARCHAR(16) DEFAULT 'daily',
    cost BIGINT DEFAULT 0,              -- 消耗，单位：分
    impressions BIGINT DEFAULT 0,
    clicks BIGINT DEFAULT 0,
    conversions DECIMAL(10,2) DEFAULT 0,
    ctr DECIMAL(10,6) DEFAULT 0,
    cpm DECIMAL(10,2) DEFAULT 0,
    cpc DECIMAL(10,2) DEFAULT 0,
    cvr DECIMAL(10,6) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_report (tenant_id, platform, platform_account_id, campaign_id, ad_group_id, creative_id, date, granularity),
    INDEX idx_date (date),
    INDEX idx_campaign_date (campaign_id, date),
    INDEX idx_platform_account (platform_account_id)
);

-- 报表扩展数据
CREATE TABLE erik_report_extras (
    id BIGINT UNSIGNED PRIMARY KEY,
    report_metric_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(32) NOT NULL,
    extra JSON,
    FOREIGN KEY (report_metric_id) REFERENCES erik_report_metrics(id) ON DELETE CASCADE
);

-- 告警规则
CREATE TABLE erik_alert_rules (
    id BIGINT UNSIGNED PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    metric VARCHAR(32) NOT NULL,
    condition VARCHAR(16) NOT NULL,
    threshold DECIMAL(12,2) NOT NULL,
    scope VARCHAR(32) DEFAULT 'tenant',
    platform VARCHAR(32),
    campaign_id BIGINT UNSIGNED,
    check_interval INT DEFAULT 5,
    channels JSON,
    enabled TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_enabled (tenant_id, enabled)
);

-- 告警记录
CREATE TABLE erik_alert_logs (
    id BIGINT UNSIGNED PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    rule_id BIGINT UNSIGNED NOT NULL,
    rule_name VARCHAR(100) NOT NULL,
    metric VARCHAR(32) NOT NULL,
    current_value DECIMAL(12,2) NOT NULL,
    threshold DECIMAL(12,2) NOT NULL,
    condition VARCHAR(16) NOT NULL,
    status ENUM('triggered','acknowledged','resolved') DEFAULT 'triggered',
    extra JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_rule (rule_id)
);
```

---

## 4. Erik Stack Integration

| Package | Purpose | Integration Point |
|---------|---------|-------------------|
| `erikwang2013/snowflake-php` | Distributed primary key ID | SnowflakeTrait → creating event on all Models |
| `erikwang2013/hashids` | API request/response ID encryption | ApiResponse auto-encodes id/*_id fields |
| `erikwang2013/jwt-webman` | JWT auth tokens | AuthMiddleware + AuthController |
| `erikwang2013/encryption` | API-layer sensitive data encryption | EncryptionMiddleware (X-Encrypted header) |
| `erikwang2013/encryptable` | DB field auto encryption | PlatformAccount/AuthToken $encryptable |
| `erikwang2013/webman-scout` | Elasticsearch data sync | config/scout.php |
| `erikwang2013/season` | Country flags | PlatformBadge.vue (Unicode flags) |
| `erikwang2013/poster-php` | Slider CAPTCHA | CaptchaService + CaptchaWidget |

---

## 5. Internationalization (i18n)

All interfaces support **Chinese (zh-CN)** and **English (en)**:

| End | Technology | Translation Volume |
|-----|------------|--------------------|
| Admin | vue-i18n v9 | 158 keys (app/nav/login/dashboard/campaign/account/report/alert/system/common) |
| Service API | `erik\support\I18n` | 12 message keys (Accept-Language header / ?lang= param) |
| Flutter | AppLocalizations + Delegate | 20+ UI keys |
| HarmonyOS | StringResources | 15+ UI keys |

---

## 6. CAPTCHA

Sensitive operations such as login require completing the slider CAPTCHA (erikwang2013/poster-php):

```
GET  /api/v1/captcha/generate  → 返回背景图 + 拼图块 + AES 加密 token
POST /api/v1/captcha/verify    → 验证偏移量（5px 容差，5 分钟有效）
```

The frontend `CaptchaWidget` component supports drag/touch, auto-refreshing on failure. The backend AuthController validates `captcha_token` + `captcha_offset` at login.

### Type-to-Confirm

Sensitive operations such as delete, unbind, and batch actions use the "type-to-confirm" pattern:

| Operation | Confirmation Method | Confirm Phrase |
|-----------|---------------------|----------------|
| Unbind account | Type account name | Account name |
| Batch toggle campaigns | Type fixed phrase | `ENABLE` / `PAUSE` |
| Delete alert rule | Type rule name | Rule name |
| Disable/enable user | Type username | Username |

Driven by the generic `GlobalConfirm` component + `useConfirmStore` Pinia store; adding a new sensitive operation only requires calling `confirmStore.show({...})`.

---

## 7. Security Middleware Stack (8 layers total)

### Request Flow

```text
                          HTTP Request
                               │
                               v
                    ┌──────────────────┐
                    │   CorsMiddleware │  CORS header
                    └────────┬─────────┘
                             v
                    ┌──────────────────┐
                    │ RateLimitMiddle  │  60 req / 60s
                    └────────┬─────────┘
                             v
                    ┌──────────────────┐
                    │ SQLGuardMiddle   │  Injection detect
                    └────────┬─────────┘
                             v
                    ┌──────────────────┐
                    │ ValidationMiddle │  Trim + strip tags
                    └────────┬─────────┘
                             v
                    ┌──────────────────┐
                    │ EncryptMiddle    │  X-Encrypted header
                    └────────┬─────────┘
                             v
                    ┌──────────────────┐
                    │ AuthMiddle (JWT) │  Bearer Token
                    └────────┬─────────┘
                             v
                    ┌──────────────────┐
                    │ TenantIdentify   │  X-Tenant-Id
                    └────────┬─────────┘
                             v
                    ┌──────────────────┐
                    │   Controller     │  Business Logic
                    └──────────────────┘
```

| Middleware | Function |
|------------|----------|
| CorsMiddleware | Cross-origin handling, supports X-Tenant-Id/X-Encrypted |
| RateLimitMiddleware | Redis sliding-window rate limit, default 60 req/60s |
| SqlGuardMiddleware | SQL injection pattern detection (UNION/DROP/ALTER/comments) |
| ValidationMiddleware | Input trimming + HTML tag filtering |
| EncryptionMiddleware | Request decryption + response encryption (X-Encrypted header) |
| AuthMiddleware | JWT Bearer Token validation (erikwang2013/jwt-webman) |
| TenantIdentify | Multi-tenant resolution (X-Tenant-Id header / Session) |

---

## 8. Web Admin Panel

Tech stack: Vue 3 + TypeScript + Element Plus + ECharts 5 + Pinia + Axios

### Implemented Pages

```
admin/src/views/
├── login/LoginPage.vue              # 登录
├── dashboard/DashboardPage.vue      # 仪表盘（KPI趋势/平台对比/TOP10/日期筛选/PDF导出）
├── account/
│   ├── AccountList.vue              # 平台账户列表（同步/解绑）
│   └── AccountBind.vue              # OAuth 绑定引导（3步向导）
├── campaign/CampaignList.vue        # 广告计划（CRUD/批量操作/筛选/分页）
├── alert/
│   ├── AlertRuleList.vue            # 告警规则 CRUD
│   └── AlertLogList.vue             # 告警记录（状态筛选/确认）
├── report/ReportExport.vue          # 报表导出（CSV/Excel/PDF）
└── components/
    ├── layout/AppLayout.vue, SideNav.vue, TopBar.vue
    ├── MetricCard.vue               # KPI 指标卡片（含趋势箭头）
    └── PlatformBadge.vue            # 平台标签（含国旗）
```

### TypeScript

- Axios generic type `UnwrappedInstance` auto-unwraps the `ApiResponse<T>` envelope
- `vue-tsc --noEmit` **zero errors**

---

## 9. Flutter App

PC Web-first responsive design, adaptive across 3 breakpoints.

| Breakpoint | Width | Layout | Navigation |
|------------|-------|--------|------------|
| Mobile | < 600px | Single-column cards | Bottom NavigationBar |
| Tablet | 600-1200px | Two-column grid | Drawer |
| Desktop | > 1200px | Multi-column grid + DataTable | Fixed SideNav (250px) |

**Division of labor between PC and admin panel**:

- **webman-admin**: heavy management (deep reports/system config/tenant management/batch operations)
- **Flutter Web/PC**: lightweight operations panel (real-time monitoring/alert handling/lightweight delivery, no VPN required)

---

## 10. HarmonyOS App

Tech stack: ArkTS + ArkUI. Features aligned with the Flutter App.

```
entry/src/main/ets/
├── entryability/EntryAbility.ets
├── pages/LoginPage, DashboardPage, CampaignListPage, AccountPage, ReportPage, AlertPage
├── model/Campaign, ReportMetric, PlatformAccount, AlertRule
├── api/ApiClient (GET/POST/PUT/DELETE + Bearer Token)
├── widgets/MetricCard, PlatformBadge, StatusChip, EmptyState
└── utils/FormatUtil
```

---

## 11. API Design

Prefix `/api/v1`, unified response format:

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [...],
    "pagination": { "page": 1, "per_page": 20, "total": 156, "total_pages": 8 },
    "summary": { "total_cost": 1258000, "avg_ctr": 6.39, "avg_roi": 3.2 }
  }
}
```

### All Endpoints

```
# 认证
POST   /api/v1/auth/login
GET    /api/v1/auth/me

# 平台 & 账户
GET    /api/v1/platforms
GET    /api/v1/accounts
GET    /api/v1/accounts/:id
DELETE /api/v1/accounts/:id
POST   /api/v1/accounts/:id/sync
GET    /api/v1/platforms/:code/oauth-url
POST   /api/v1/platforms/:code/callback

# 广告计划
GET    /api/v1/campaigns
POST   /api/v1/campaigns
GET    /api/v1/campaigns/:id
PUT    /api/v1/campaigns/:id
POST   /api/v1/campaigns/:id/toggle

# 报表
GET    /api/v1/reports/summary
GET    /api/v1/reports/custom
GET    /api/v1/reports/export
GET    /api/v1/reports/export-dashboard

# 告警
GET    /api/v1/alerts/rules
POST   /api/v1/alerts/rules
PUT    /api/v1/alerts/rules/:id
DELETE /api/v1/alerts/rules/:id
GET    /api/v1/alerts/logs
POST   /api/v1/alerts/logs/:id/acknowledge
GET    /api/v1/alerts/unread-count
```

---

## 12. Business Logic Diagrams

### Admin ↔ Service Communication

```text
 Browser                    Admin :8789               Service :8788
    │                            │                         │
    │  POST /api/admin/login     │                         │
    │ ─────────────────────────> │                         │
    │                            │  Verify password+captcha│
    │ <─────── JWT Token ─────── │                         │
    │                            │                         │
    │  GET / (Vue SPA)           │                         │
    │ ─────────────────────────> │                         │
    │ <─────── index.html ────── │                         │
    │                            │                         │
    │  GET /api/v1/campaigns     │                         │
    │ ──────────────────────────────────────────────────> │
    │                            │     Bearer: {token}     │
    │                            │     X-Tenant-Id: 1      │
    │                            │                         │
    │ <────────── JSON ───────────────────────────────── │
    │                            │                         │
    │  POST /api/v1/campaigns    │                         │
    │ ──────────────────────────────────────────────────> │
    │                            │                         │──> Platform API
    │                            │                         │<── {campaign_id}
    │                            │                         │
    │ <────── {id: hashids} ──────────────────────────── │
```

### OAuth Platform Authorization Flow

```text
 Admin            Service :8788           Ad Platform       User
    │                    │                     │              │
    │ GET oauth-url      │                     │              │
    │──────────────────> │                     │              │
    │                    │ gen state -> DB     │              │
    │ <── {auth_url} ─── │                     │              │
    │                    │                     │              │
    │────────────────────────────────────────────────────>   │
    │                    │               Redirect to login   │
    │                    │                     │<─────────────│
    │                    │                     │   授权确认    │
    │                    │                     │─────────────>│
    │                    │                     │              │
    │ <── ?code=xxx ──────────────────────    │              │
    │                    │                     │              │
    │ POST callback      │                     │              │
    │──────────────────> │                     │              │
    │                    │ verify state        │              │
    │                    │──────────────────>  │              │
    │                    │  POST /oauth/token  │              │
    │                    │<── {access_token} ──│              │
    │                    │                     │              │
    │                    │ encrypt + store     │              │
    │                    │ fetch account info  │              │
    │ <── {account_id} ─ │                     │              │
```

### Data Sync & Alert Flow

```text
              ┌─────── Crontab ───────┐
              │        │              │
              v        v              v
    ┌─────────────┐ ┌───────────┐ ┌──────────────┐
    │ DataSync    │ │ AlertCheck│ │ RetrySync    │
    │ (10 min)    │ │ (5 min)   │ │ (3 min)      │
    └──────┬──────┘ └─────┬─────┘ └──────┬───────┘
           │              │              │
           v              v              v
    ┌──────────────┐ ┌───────────┐ ┌──────────────┐
    │ Rate Limiter │ │遍历启用规则│ │扫描错误队列  │
    │ per platform │ └─────┬─────┘ │ retry < 3    │
    └──────┬───────┘       │       └──────┬───────┘
           │               v              │
           v        ┌───────────┐         v
    ┌──────────┐    │查询今日数据│  ┌──────────────┐
    │Generator │    │report_     │  │单账户重试同步│
    │fetch API │    │metrics表   │  └──────┬───────┘
    └────┬─────┘    └─────┬─────┘         │
         │                │               v
         v                v        ┌─── 成功? ───┐
    ┌──────────┐    ┌───────────┐  │yes        no│
    │FieldMap  │    │阈值触发?   │  v           v
    │金额统一  │    └──┬───┬───┘  ┌──┐    ┌──────────┐
    │extra JSON│    yes│   │no   │清除│    │retry+1   │
    └────┬─────┘      │   └──→  │记录│    │指数退避  │
         │            v          └──┘    │5^n 分钟  │
         v      ┌───────────┐           └──────────┘
    ┌──────────┐│AlertLog   │
    │upsert    ││写入DB     │
    │campaigns ││Redis Pub  │
    │metrics   ││Sub 推送   │
    └────┬─────┘└───────────┘
         │
         v
    ┌──────────┐
    │清仪表盘  │
    │Redis缓存 │
    └──────────┘
```

### Adapter Pattern

```text
                 ┌──────────────────────────────┐
                 │      << interface >>          │
                 │      PlatformAdapter          │
                 ├──────────────────────────────┤
                 │ code() / name() / capabilities│
                 │ buildAuthUrl / exchangeToken  │
                 │ fetchCampaigns (Generator)    │
                 │ fetchAdGroups  (Generator)    │
                 │ fetchCreatives (Generator)    │
                 │ fetchReports   (Generator)    │
                 │ createCampaign / update       │
                 │ toggleCampaign                │
                 └──────────┬───────────────────┘
                            │ implements
            ┌───────────────┼───────────────┐
            │               │               │
    ┌───────┴──────┐ ┌──────┴──────┐ ┌──────┴──────┐
    │  Juliang     │ │  Baidu      │ │  Google     │
    │  巨量引擎    │ │  百度营销   │ │  Google Ads │
    │  yuan->fen   │ │  yuan->fen  │ │  u$->fen    │
    │  AccessToken │ │  信封签名   │ │  GAQL       │
    └──────┬───────┘ └──────┬──────┘ └──────┬──────┘
           │                │               │
           └────────────────┼───────────────┘
                            │
                   ┌────────┴────────┐
                   │   FieldMapping  │
                   ├─────────────────┤
                   │ fieldMap: array │
                   │ statusMap:array │
                   │ transformer:fn  │
                   │ map(raw):array  │
                   └─────────────────┘

         AdapterRegistry (static)
         ┌─────────────────────────┐
         │ register(adapter):void  │
         │ get(code): ?Adapter     │
         │ all(): array            │
         │ has(code): bool         │
         └─────────────────────────┘
```

---

## 13. Data Sync & Task Scheduling

Uses webman/crontab, accelerated with Redis caching.

| Task | Frequency | Description |
|------|-----------|-------------|
| TokenRefreshTask | Every 55 min | Scan expired tokens, auto-refresh |
| DataSyncTask | Every 10 min | Pull platform campaigns + last 2 days of reports, clear dashboard cache after sync |
| AlertCheckTask | Every 5 min | Iterate enabled rules, evaluate thresholds, trigger push |
| RetrySyncTask | Every 3 min | Retry failed syncs (erik_sync_errors table, max 3 retries, exponential backoff) |

Sync strategy: adapter Generator streaming, cursor/pagination to prevent gaps, automatic retry on failure, curl_errno checks, platform-level QPS rate limiting.

---

## 14. Deployment Architecture

### Containerized Deployment

```text
                    ┌──────────────────────┐
                    │   Browser / App      │
                    └──────────┬───────────┘
                               │ :80
                               v
                    ┌──────────────────────┐
                    │   Nginx (reverse)    │
                    │   / -> admin:8789    │
                    │   /api -> svc:8788   │
                    └──────┬───────┬───────┘
                           │       │
              ┌────────────┘       └────────────┐
              v                                 v
    ┌─────────────────┐               ┌─────────────────┐
    │ admin-php :8789 │               │   php :8788     │
    │ webman-admin v2 │─── Service ──>│  webman v2      │
    │ Vue SPA + RBAC  │    Proxy HTTP │  Business API   │
    └────────┬────────┘               └────────┬────────┘
             │                                 │
             └─────────────┬───────────────────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
              v            v            v
        ┌─────────┐ ┌──────────┐ ┌──────────┐
        │MySQL 8.0│ │ Redis 7  │ │ ES 9200  │
        │erik_*   │ │ cache,   │ │ search   │
        │admin_*  │ │ queue    │ │          │
        └─────────┘ └──────────┘ └──────────┘
```

### Production Deployment Flow

```text
   ┌─────────┐     ┌──────────────────────────────────────────────┐
   │ GitHub  │     │            GitHub Actions CI                 │
   │  Push   │────>│                                              │
   └─────────┘     │  ┌──────────┐ ┌──────────┐ ┌──────────┐    │
                   │  │PHP Syntax│ │ PHPUnit  │ │ vue-tsc  │    │
                   │  │  0 err   │ │ 20 tests │ │  0 err   │    │
                   │  └────┬─────┘ └────┬─────┘ └────┬─────┘    │
                   │       └───────────┼─────────────┘          │
                   │                   v                         │
                   │         ┌─────────────────┐                 │
                   │         │  Docker Build   │                 │
                   │         │  PHP + Admin    │                 │
                   │         └────────┬────────┘                 │
                   └──────────────────┼──────────────────────────┘
                                      │ merge to main
                                      v
                             ┌─────────────────┐
                             │  Manual Deploy  │
                             │  staging / prod │
                             └─────────────────┘
```

### Docker One-Click Deployment

```bash
docker-compose up -d          # MySQL + Redis + PHP + Nginx
make db-init                  # 初始化数据库
make admin-dev                # 前端开发模式
```

---

## 15. Implementation History

| Phase | Content | Status |
|-------|---------|--------|
| Phase 1 | webman v2 + admin skeleton + multi-tenancy + OAuth + Juliang | ✅ |
| Phase 2 | Baidu adapter + Taobao adapter + data sync + report engine | ✅ |
| Phase 3 | Tencent + Umeng + Kuaishou + Xiaohongshu (4 new) | ✅ |
| Phase 4 | Weibo + Bilibili + Youku + Meituan + Zhihu + 360 + Sogou + Jingdong + Pinduoduo (9 new domestic) | ✅ |
| Phase 4 | Meta + LinkedIn + Snapchat + Pinterest + Twitter + Amazon + TTD + Spotify + Twitch + Netflix + Google + YouTube + TikTok (13 new international) | ✅ |
| Phase 5 | Alert system + report export + Flutter App + HarmonyOS App + dashboard enhancements | ✅ |
| Phase 6 | Erik Stack integration (snowflake/hashids/jwt-webman/encryption/encryptable/scout/season) | ✅ |
| Phase 7 | Docker deployment + security hardening (RateLimit/CORS/SQLGuard) + cache layer + README | ✅ |
| Phase 8 | Directory reorganization (apps/) + standalone Admin webman-admin v2 (PHP backend + ServiceProxy) + RBAC + audit logs | ✅ |
| Phase 9 | API docs + platform rate limits + sync retry queue + PHPUnit 20 tests + GitHub Actions CI/CD | ✅ |
| Phase 10 | Chinese comments in config files + .env comments + platform credentials docs + `erik_` table prefix rewrite + BIGINT PK | ✅ |
| Phase 11 | Internationalization (vue-i18n + I18n.php + Flutter + HarmonyOS) + slider CAPTCHA (poster-php) | ✅ |
| Phase 12 | Type-to-confirm — unbind/delete/batch operations require typing the target name | ✅ |

---

## 16. Admin Panel Architecture

### PHP Backend (port 8789)

```
admin/
├── public/web/              # Vue SPA 源码（开发模式 Vite :5173）
├── app/
│   ├── controller/
│   │   ├── AuthController.php       # 管理员登录（JWT）
│   │   ├── AdminUserController.php  # 用户 CRUD（bcrypt 密码）
│   │   ├── AuditLogController.php   # 审计日志查询
│   │   └── ServiceProxy.php         # HTTP 代理 → service:8788
│   ├── middleware/AuthCheck.php     # JWT/Session 双重认证
│   └── service/AuditService.php     # 操作审计写入
├── config/route.php                # Admin API 路由
└── migration/create_admin_tables.sql # admin_users/roles/audit_logs
```

### Role Permissions (RBAC)

| Role | slug | Permissions |
|------|------|-------------|
| Super Admin | super_admin | `*` all permissions |
| Operations Manager | ops_manager | dashboard, campaigns, reports, alerts, accounts |
| Data Analyst | analyst | dashboard, reports |

### Admin ↔ Service Communication

Admin calls the service API through `ServiceProxy` (cURL), forwarding the JWT token. Admin handles authentication/authorization and user management itself; all business data is served by the service.

---

## 17. Testing & CI/CD

### PHPUnit Test Suite

```bash
cd service && ./vendor/bin/phpunit
# 20 tests / 41 assertions
# FieldMappingTest (5) / HashidsServiceTest (5)
# ReportBuilderTest (3) / CampaignDataTest (3)
# AdapterRegistryTest (4)
```

### GitHub Actions CI

```yaml
Push/PR → PHP Syntax → PHPUnit (MySQL 8.0) → TypeScript (vue-tsc) → Docker Build
```

### Dependabot

Auto-updates Composer + npm + Docker dependencies weekly.
