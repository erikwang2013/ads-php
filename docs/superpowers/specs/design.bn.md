# 多平台广告管理系统设计

[中文](docs/superpowers/specs/design.md) | [English](docs/superpowers/specs/design.en.md) | [한국어](docs/superpowers/specs/design.ko.md) | [Русский](docs/superpowers/specs/design.ru.md) | [Deutsch](docs/superpowers/specs/design.de.md) | [Français](docs/superpowers/specs/design.fr.md) | [Español](docs/superpowers/specs/design.es.md) | [Português](docs/superpowers/specs/design.pt.md) | [हिन्दी](docs/superpowers/specs/design.hi.md) | [العربية](docs/superpowers/specs/design.ar.md) | [বাংলা](docs/superpowers/specs/design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/design.id.md) | [日本語](docs/superpowers/specs/design.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## ওভারভিউ

**২৯টি অ্যাড প্ল্যাটফর্ম**-এর সাথে সংযুক্ত ইউনিফাইড অ্যাড ম্যানেজমেন্ট প্ল্যাটফর্ম, দেশি-বিদেশি প্রধান অ্যাড ভেন্ডর কভার করে, অ্যাড ডেলিভারি ম্যানেজমেন্ট, ক্রস-প্ল্যাটফর্ম ডেটা রিপোর্ট, রিয়েল-টাইম অ্যালার্ট মনিটরিং সাপোর্ট করে।

- **service** — ইউজার-সাইড বিজনেস সার্ভিস, webman v2 (PHP 8.2+)，লিসেন :8788
- **admin** — স্বাধীন ম্যানেজমেন্ট ব্যাকএন্ড, webman-admin v2 (PHP ব্যাকএন্ড :8789 + Vue 3 SPA)
- **apps** — ক্লায়েন্ট অ্যাপ, Flutter (iOS/Android/Web PC) + HarmonyOS (ArkTS)
- **ইনফ্রাস্ট্রাকচার**: Docker + Nginx + MySQL 8.0 + Redis 7 + Elasticsearch

বিজনেস দৃশ্যপট কভার করে: নিজস্ব ব্যবহার ডেলিভারি, SaaS মাল্টি-টেন্যান্ট, অ্যাজেন্সি অপারেশন — তিনটি মোড।

### কমিউনিকেশন আর্কিটেকচার

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

## সামগ্রিক আর্কিটেকচার

### সিস্টেম আর্কিটেকচার ডায়াগ্রাম

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

## 一、সার্ভার-সাইড মডিউল ব্রেকডাউন

webman v2 প্লাগইন-আর্কিটেকচার, `service/plugin/`-এর অধীনে ৭টি প্লাগইন:

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

## 二、প্ল্যাটফর্ম অ্যাডাপ্টার

### অ্যাডাপ্ট করা প্ল্যাটফর্ম (29)

| অঞ্চল | # | প্ল্যাটফর্ম | অ্যাডাপ্টার ক্লাস | অথেনটিকেশন | অর্থ | রিপোর্ট |
|------|---|------|---------|------|------|------|
| দেশীয় | 1 | 巨量引擎 | Juliang | OAuth2 Access-Token | 元→ফেন (分) | সিঙ্ক পেজিনেশন |
| দেশীয় | 2 | 百度营销 | Baidu | OAuth2 + এনভেলপ সাইনিং | 元→ফেন (分) | অ্যাসিঙ্ক পোলিং |
| দেশীয় | 3 | 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5 সাইনিং | 元→ফেন (分) | সিঙ্ক পেজিনেশন |
| দেশীয় | 4 | 腾讯广告 | Tencent | OAuth2 + nonce | ফেন (分) নেটিভ | সিঙ্ক পেজিনেশন |
| দেশীয় | 5 | 快手磁力引擎 | Kuaishou | OAuth2 URL প্যারামিটার | 元→ফেন (分) | সিঙ্ক পেজিনেশন |
| দেশীয় | 6 | 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer | ফেন (分) নেটিভ | সিঙ্ক পেজিনেশন |
| দেশীয় | 7 | 微博粉丝通 | Weibo | OAuth2 Bearer | ফেন (分) নেটিভ | সিঙ্ক পেজিনেশন |
| দেশীয় | 8 | B站花火 | Bilibili | OAuth2 Bearer | ফেন (分) নেটিভ | সিঙ্ক পেজিনেশন |
| দেশীয় | 9 | 优酷广告 | Youku | OAuth2 + MD5 সাইনিং | 元→ফেন (分) | সিঙ্ক পেজিনেশন |
| দেশীয় | 10 | 美团广告 | Meituan | OAuth2 Bearer | ফেন (分) নেটিভ | সিঙ্ক পেজিনেশন |
| দেশীয় | 11 | 知乎广告 | Zhihu | OAuth2 Bearer | 元→ফেন (分) | সিঙ্ক পেজিনেশন |
| দেশীয় | 12 | 360推广 | Qihoo360 | API Key + Sign | 元→ফেন (分) | সিঙ্ক পেজিনেশন |
| দেশীয় | 13 | 搜狗推广 | Sogou | API Key + Sign | 元→ফেন (分) | সিঙ্ক পেজিনেশন |
| দেশীয় | 14 | 友盟 | Umeng | API Key + MD5 | 元→ফেন (分) | সিঙ্ক পেজিনেশন |
| দেশীয় | 15 | 京东京准通 | Jingdong | OAuth2 + MD5 | 元→ফেন (分) | সিঙ্ক পেজিনেশন |
| দেশীয় | 16 | 拼多多广告 | Pinduoduo | OAuth2 + কাস্টম Sign | ফেন (分) নেটিভ | সিঙ্ক পেজিনেশন |
| আন্তর্জাতিক | 17 | Google Ads | Google | OAuth2 + GAQL | মাইক্রো-元→ফেন (分) | pageToken |
| আন্তর্জাতিক | 18 | YouTube Ads | Youtube | OAuth2 + GAQL | মাইক্রো-元→ফেন (分) | pageToken |
| আন্তর্জাতিক | 19 | Meta Ads | Meta | OAuth2 URL প্যারামিটার | ফেন (分) নেটিভ | অ্যাসিঙ্ক |
| আন্তর্জাতিক | 20 | TikTok Ads | Tiktok | OAuth2 Access-Token | মাইক্রো-元→ফেন (分) | সিঙ্ক পেজিনেশন |
| আন্তর্জাতিক | 21 | LinkedIn Ads | Linkedin | OAuth2 Bearer | মাইক্রো-元→ফেন (分) | সিঙ্ক পেজিনেশন |
| আন্তর্জাতিক | 22 | Snapchat Ads | Snapchat | OAuth2 Bearer | মাইক্রো-元→ফেন (分) | সিঙ্ক পেজিনেশন |
| আন্তর্জাতিক | 23 | Pinterest Ads | Pinterest | OAuth2 Bearer | মাইক্রো-元→ফেন (分) | সিঙ্ক পেজিনেশন |
| আন্তর্জাতিক | 24 | Twitter/X Ads | Twitter | OAuth2 Bearer | মাইক্রো-元→ফেন (分) | সিঙ্ক পেজিনেশন |
| আন্তর্জাতিক | 25 | Amazon Ads | Amazon | OAuth2 + Profile | ফেন (分) নেটিভ | অ্যাসিঙ্ক |
| আন্তর্জাতিক | 26 | The Trade Desk | TheTradeDesk | HMAC-SHA256 | ফেন (分) নেটিভ | অ্যাসিঙ্ক |
| আন্তর্জাতিক | 27 | Spotify Ads | Spotify | OAuth2 Bearer | ফেন (分) নেটিভ | অ্যাসিঙ্ক |
| আন্তর্জাতিক | 28 | Twitch Ads | Twitch | OAuth2 Bearer+ClientId | ফেন (分) নেটিভ | সিঙ্ক |
| আন্তর্জাতিক | 29 | Netflix Ads | Netflix | OAuth2 client_credentials | ফেন (分) নেটিভ | সিঙ্ক |

### ইন্টারফেস সংজ্ঞা

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

### ফিল্ড ম্যাপিং

প্রতিটি অ্যাডাপ্টার `FieldMapping`-এর মাধ্যমে প্ল্যাটফর্মের র ক ফিল্ডকে ইউনিফাইড মডেলে রূপান্তর করে, প্ল্যাটফর্ম-নির্দিষ্ট ফিল্ড স্বয়ংক্রিয়ভাবে `extra` JSON-এ পড়ে। অর্থ ইউনিফাইডভাবে **ফেন (分)** (RMB) / **ফেন-cent (分-cent)** (USD)。

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

## 三、ডাটাবেস ডিজাইন

### নামকরণ কনভেনশন
- টেবিল প্রিফিক্স: `erik_`
- প্রাইমারি কী: `BIGINT UNSIGNED PRIMARY KEY` (কোনো অটো-ইনক্রিমেন্ট নেই, Snowflake ID জেনারেট)
- ইঞ্জিন: InnoDB，ক্যারেক্টার সেট: utf8mb4

### কোর টেবিল (13টি)

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

## 四、Erik Stack ইন্টিগ্রেশন

| প্যাকেজ | ব্যবহার | ইন্টিগ্রেশন অবস্থান |
|----|------|---------|
| `erikwang2013/snowflake-php` | ডিস্ট্রিবিউটেড প্রাইমারি কী ID | SnowflakeTrait → সব Model creating ইভেন্ট |
| `erikwang2013/hashids` | API রিকোয়েস্ট/রেসপন্স ID এনক্রিপশন/ডিক্রিপশন | ApiResponse অটো এনকোড id/*_id ফিল্ড |
| `erikwang2013/jwt-webman` | JWT অথেনটিকেশন টোকেন | AuthMiddleware + AuthController |
| `erikwang2013/encryption` | API লেয়ার সংবেদনশীল ডেটা এনক্রিপশন/ডিক্রিপশন | EncryptionMiddleware (X-Encrypted হেডার) |
| `erikwang2013/encryptable` | DB ফিল্ড অটো এনক্রিপশন/ডিক্রিপশন | PlatformAccount/AuthToken $encryptable |
| `erikwang2013/webman-scout` | Elasticsearch ডেটা সিঙ্ক | config/scout.php |
| `erikwang2013/season` | দেশের পতাকা | PlatformBadge.vue (Unicode পতাকা) |
| `erikwang2013/poster-php` | স্লাইডার ক্যাপচা | CaptchaService + CaptchaWidget |

---

## 五、আন্তর্জাতিকীকরণ (i18n)

সব ইন্টারফেস সাপোর্ট করে **中文 (zh-CN)** এবং **English (en)**：

| এন্ড | টেকনোলজি | অনুবাদের পরিমাণ |
|----|------|--------|
| Admin | vue-i18n v9 | 158 keys (app/nav/login/dashboard/campaign/account/report/alert/system/common) |
| Service API | `erik\support\I18n` | 12 মেসেজ keys (Accept-Language header / ?lang= param) |
| Flutter | AppLocalizations + Delegate | 20+ UI keys |
| HarmonyOS | StringResources | 15+ UI keys |

---

## 六、ক্যাপচা

লগইন ইত্যাদি সংবেদনশীল অপারেশনের আগে স্লাইডার ক্যাপচা সম্পন্ন করতে হবে (erikwang2013/poster-php)：

```
GET  /api/v1/captcha/generate  → 返回背景图 + 拼图块 + AES 加密 token
POST /api/v1/captcha/verify    → 验证偏移量（5px 容差，5 分钟有效）
```

ফ্রন্টএন্ড `CaptchaWidget` কম্পোনেন্ট ড্র্যাগ/টাচ-স্ক্রিন সাপোর্ট করে, ব্যর্থ হলে অটো রিফ্রেশ। ব্যাকএন্ড AuthController লগইনের সময় captcha_token + captcha_offset ভ্যালিডেট করে।

### সেকেন্ডারি কনফার্মেশন

ডিলিট, আনবাইন্ড, ব্যাচ অপারেশন ইত্যাদি সংবেদনশীল অপারেশনে "ইনপুট টু কনফার্ম" মোড ব্যবহৃত হয়：

| অপারেশন | কনফার্মেশন পদ্ধতি | কনফার্মেশন শব্দ |
|------|---------|--------|
| অ্যাকাউন্ট আনবাইন্ড | অ্যাকাউন্টের নাম ইনপুট | অ্যাকাউন্টের নাম |
| প্ল্যান ব্যাচ স্টার্ট/স্টপ | নির্দিষ্ট কনফার্মেশন শব্দ ইনপুট | `ENABLE` / `PAUSE` |
| অ্যালার্ট রুল ডিলিট | রুলের নাম ইনপুট | রুলের নাম |
| ইউজার ডিসেবল/এনাবল | ইউজারনেম ইনপুট | ইউজারনেম |

সাধারণ `GlobalConfirm` কম্পোনেন্ট + `useConfirmStore` Pinia store চালিত, নতুন সংবেদনশীল অপারেশন যোগ করলে শুধু `confirmStore.show({...})` কল করুন।

---

## 七、সিকিউরিটি মিডলওয়্যার স্ট্যাক (মোট ৮ লেয়ার)

### রিকোয়েস্ট ফ্লো

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

| মিডলওয়্যার | ফাংশন |
|--------|------|
| CorsMiddleware | ক্রস-অরিজিন রিকোয়েস্ট হ্যান্ডলিং, X-Tenant-Id/X-Encrypted সাপোর্ট |
| RateLimitMiddleware | Redis স্লাইডিং উইন্ডো রেট লিমিট, ডিফল্ট ৬০ বার/৬০ সেকেন্ড |
| SqlGuardMiddleware | SQL ইনজেকশন প্যাটার্ন ডিটেকশন (UNION/DROP/ALTER/কমেন্ট) |
| ValidationMiddleware | ইনপুট ট্রিম + HTML ট্যাগ ফিল্টার |
| EncryptionMiddleware | রিকোয়েস্ট ডিক্রিপ্ট + রেসপন্স এনক্রিপ্ট (X-Encrypted হেডার) |
| AuthMiddleware | JWT Bearer Token ভেরিফিকেশন (erikwang2013/jwt-webman) |
| TenantIdentify | মাল্টি-টেন্যান্ট পার্সিং (X-Tenant-Id হেডার / Session) |

---

## 八、Web ম্যানেজমেন্ট ব্যাকএন্ড

টেক স্ট্যাক: Vue 3 + TypeScript + Element Plus + ECharts 5 + Pinia + Axios

### বাস্তবায়িত পেজ

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

- Axios জেনেরিক টাইপ `UnwrappedInstance` অটো আনর্যাপ করে `ApiResponse<T>` র্যাপার
- `vue-tsc --noEmit` **শূন্য error**

---

## 九、Flutter App

PC Web প্রায়োরিটি রেসপনসিভ ডিজাইন, ৩টি ব্রেকপয়েন্টে অ্যাডাপ্টিভ।

| ব্রেকপয়েন্ট | প্রস্থ | লেআউট | নেভিগেশন |
|------|------|------|------|
| Mobile | < 600px | এক-কলাম কার্ড | বটম NavigationBar |
| Tablet | 600-1200px | দুই-কলাম গ্রিড | Drawer ড্রয়ার |
| Desktop | > 1200px | মাল্টি-কলাম গ্রিড + DataTable | ফিক্সড SideNav (250px) |

**PC এন্ড ও ম্যানেজমেন্ট ব্যাকএন্ডের মধ্যে বিভাজন**:

- **webman-admin**: ভারী ম্যানেজমেন্ট (গভীর রিপোর্ট/সিস্টেম কনফিগ/টেন্যান্ট ম্যানেজমেন্ট/ব্যাচ অপারেশন)
- **Flutter Web/PC**: লাইটওয়েট অপারেশন প্যানেল (রিয়েল-টাইম মনিটরিং/অ্যালার্ট হ্যান্ডলিং/লাইটওয়েট ডেলিভারি, VPN প্রয়োজন নেই)

---

## 十、HarmonyOS App

টেক স্ট্যাক: ArkTS + ArkUI। ফিচার Flutter App-এর সাথে অ্যালাইন।

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

## 十一、API ডিজাইন

প্রিফিক্স `/api/v1`，ইউনিফাইড রেসপন্স ফরম্যাট：

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

### সব এন্ডপয়েন্ট

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

## 十二、বিজনেস লজিক ডায়াগ্রাম

### Admin ↔ Service কমিউনিকেশন

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

### OAuth প্ল্যাটফর্ম অথোরাইজেশন ফ্লো

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

### ডেটা সিঙ্ক ও অ্যালার্ট ফ্লো

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

### অ্যাডাপ্টার প্যাটার্ন


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

## 十三、ডেটা সিঙ্ক ও টাস্ক শিডিউলিং

webman/crontab ব্যবহার করে, Redis ক্যাশ দিয়ে ত্বরান্বিত।

| টাস্ক | ফ্রিকোয়েন্সি | ব্যাখ্যা |
|------|------|------|
| TokenRefreshTask | প্রতি ৫৫ মিনিট | মেয়াদোত্তীর্ণ টোকেন স্ক্যান, অটো রিফ্রেশ |
| DataSyncTask | প্রতি ১০ মিনিট | প্রতিটি প্ল্যাটফর্মের প্ল্যান + সাম্প্রতিক ২ দিনের রিপোর্ট পুল, সিঙ্কের পর ড্যাশবোর্ড ক্যাশ ক্লিয়ার |
| AlertCheckTask | প্রতি ৫ মিনিট | এনাবল করা রুল ট্রাভার্স, থ্রেশহোল্ড ইভালুয়েট, পুশ ট্রিগার |
| RetrySyncTask | প্রতি ৩ মিনিট | ব্যর্থ সিঙ্ক পুনরায় চেষ্টা (erik_sync_errors টেবিল, সর্বোচ্চ ৩ বার, এক্সপোনেনশিয়াল ব্যাকঅফ) |

সিঙ্ক কৌশল: অ্যাডাপ্টার Generator স্ট্রিমিং প্রসেসিং, কার্সর/পেজিনেশন দিয়ে লিক রোধ, ব্যর্থ হলে অটো রিট্রাই, curl_errno চেক, প্ল্যাটফর্ম-লেভেল QPS রেট লিমিট।

---

## 十四、ডিপ্লয়মেন্ট আর্কিটেকচার

### কন্টেইনারাইজড ডিপ্লয়মেন্ট

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

### প্রোডাকশন ডিপ্লয়মেন্ট ফ্লো

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

### Docker ওয়ান-ক্লিক ডিপ্লয়মেন্ট

```bash
docker-compose up -d          # MySQL + Redis + PHP + Nginx
make db-init                  # 初始化数据库
make admin-dev                # 前端开发模式
```

---

## 十五、বাস্তবায়ন ইতিহাস

| ফেজ | বিষয়বস্তু | স্ট্যাটাস |
|------|------|------|
| Phase 1 | webman v2 + ম্যানেজমেন্ট ব্যাকএন্ড স্কেলেটন + মাল্টি-টেন্যান্ট + OAuth + 巨量 | ✅ |
| Phase 2 | 百度 অ্যাডাপ্টার + 淘宝 অ্যাডাপ্টার + ডেটা সিঙ্ক + রিপোর্ট ইঞ্জিন | ✅ |
| Phase 3 | 腾讯 + 友盟 + 快手 + 小红书 (৪টি নতুন) | ✅ |
| Phase 4 | 微博 + B站 + 优酷 + 美团 + 知乎 + 360 + 搜狗 + 京东 + 拼多多 (৯টি নতুন দেশীয়) | ✅ |
| Phase 4 | Meta + LinkedIn + Snapchat + Pinterest + Twitter + Amazon + TTD + Spotify + Twitch + Netflix + Google + YouTube + TikTok (১৩টি নতুন আন্তর্জাতিক) | ✅ |
| Phase 5 | অ্যালার্ট সিস্টেম + রিপোর্ট এক্সপোর্ট + Flutter App + HarmonyOS App + ড্যাশবোর্ড এনহান্সমেন্ট | ✅ |
| Phase 6 | Erik Stack ইন্টিগ্রেশন (snowflake/hashids/jwt-webman/encryption/encryptable/scout/season) | ✅ |
| Phase 7 | Docker ডিপ্লয়মেন্ট + সিকিউরিটি হার্ডেনিং (RateLimit/CORS/SQLGuard) + ক্যাশ লেয়ার + README | ✅ |
| Phase 8 | ডিরেক্টরি রিঅর্গানাইজেশন (apps/) + Admin স্বাধীন webman-admin v2 (PHP ব্যাকএন্ড+ServiceProxy) + RBAC + অডিট লগ | ✅ |
| Phase 9 | API ডকুমেন্টেশন + প্ল্যাটফর্ম রেট লিমিট + সিঙ্ক রিট্রাই কিউ + PHPUnit 20 টেস্ট + GitHub Actions CI/CD | ✅ |
| Phase 10 | কনফিগ ফাইলে চাইনিজ কমেন্ট + .env কমেন্ট + প্ল্যাটফর্ম ক্রেডেনশিয়াল ডক + erik_ টেবিল প্রিফিক্স রিরাইট + BIGINT PK | ✅ |
| Phase 11 | আন্তর্জাতিকীকরণ (vue-i18n + I18n.php + Flutter + HarmonyOS) + স্লাইডার ক্যাপচা (poster-php) | ✅ |
| Phase 12 | সেকেন্ডারি কনফার্মেশন (ইনপুট টু কনফার্ম) — আনবাইন্ড/ডিলিট/ব্যাচ অপারেশনে টার্গেটের নাম টাইপ করতে হবে | ✅ |

---

## 十六、Admin ম্যানেজমেন্ট ব্যাকএন্ড আর্কিটেকচার

### PHP ব্যাকএন্ড (পোর্ট 8789)

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

### রোল পারমিশন (RBAC)

| রোল | slug | পারমিশন |
|------|------|------|
| সুপার অ্যাডমিন | super_admin | `*` সব পারমিশন |
| অপারেশন ম্যানেজার | ops_manager | dashboard, campaigns, reports, alerts, accounts |
| ডেটা অ্যানালিস্ট | analyst | dashboard, reports |

### Admin ও Service-এর মধ্যে কমিউনিকেশন

Admin `ServiceProxy` (cURL)-এর মাধ্যমে service API কল করে, JWT Token ফরওয়ার্ড করে। Admin নিজে অথেনটিকেশন এবং ইউজার ম্যানেজমেন্টের দায়িত্বে, বিজনেস ডেটা সম্পূর্ণভাবে service সরবরাহ করে।

---

## 十七、টেস্ট ও CI/CD

### PHPUnit টেস্ট স্যুট

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

প্রতি সপ্তাহে Composer + npm + Docker ডিপেন্ডেন্সি অটো আপডেট।
