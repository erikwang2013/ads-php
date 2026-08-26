# تصميم نظام إدارة الإعلانات متعدد المنصات

[中文](docs/superpowers/specs/design.md) | [English](docs/superpowers/specs/design.en.md) | [한국어](docs/superpowers/specs/design.ko.md) | [Русский](docs/superpowers/specs/design.ru.md) | [Deutsch](docs/superpowers/specs/design.de.md) | [Français](docs/superpowers/specs/design.fr.md) | [Español](docs/superpowers/specs/design.es.md) | [Português](docs/superpowers/specs/design.pt.md) | [हिन्दी](docs/superpowers/specs/design.hi.md) | [العربية](docs/superpowers/specs/design.ar.md) | [বাংলা](docs/superpowers/specs/design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/design.id.md) | [日本語](docs/superpowers/specs/design.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## نظرة عامة

منصة إدارة إعلانات موحدة تربط **29 منصة إعلانية**، تغطي كبرى شركات الإعلان محليًا ودوليًا، وتدعم إدارة الإطلاق، وتقارير البيانات عبر المنصات، ومراقبة التنبيهات الفورية.

- **service** — خدمات الأعمال لطرف المستخدم، webman v2 (PHP 8.2+)، يستمع على :8788
- **admin** — لوحة إدارة مستقلة، webman-admin v2 (خلفية PHP :8789 + Vue 3 SPA)
- **apps** — تطبيقات العميل، Flutter (iOS/Android/Web PC) + HarmonyOS (ArkTS)
- **البنية التحتية**: Docker + Nginx + MySQL 8.0 + Redis 7 + Elasticsearch

تغطي سيناريوهات الأعمال ثلاثة نماذج: الإطلاق للاستخدام الذاتي، وSaaS متعدد المستأجرين، والإدارة بالوكالة.

### بنية الاتصالات

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

## البنية العامة

### مخطط بنية النظام

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

## أولاً: تفكيك وحدات الخادم

بنية إضافات webman v2، 7 إضافات تحت `service/plugin/`:

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

## ثانيًا: محولات المنصات

### المنصات المُكيَّفة (29)

| المنطقة | # | المنصة | فئة المحول | المصادقة | المبلغ | التقارير |
|------|---|------|---------|------|------|------|
| محلي | 1 | 巨量引擎 | Juliang | OAuth2 Access-Token | يوان→فين | ترقيم متزامن |
| محلي | 2 | 百度营销 | Baidu | OAuth2 + توقيع مغلف | يوان→فين | استطلاع غير متزامن |
| محلي | 3 | 淘宝/阿里妈妈 | Taobao | OAuth2 + توقيع MD5 | يوان→فين | ترقيم متزامن |
| محلي | 4 | 腾讯广告 | Tencent | OAuth2 + nonce | فين أصلي | ترقيم متزامن |
| محلي | 5 | 快手磁力引擎 | Kuaishou | OAuth2 معاملات URL | يوان→فين | ترقيم متزامن |
| محلي | 6 | 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer | فين أصلي | ترقيم متزامن |
| محلي | 7 | 微博粉丝通 | Weibo | OAuth2 Bearer | فين أصلي | ترقيم متزامن |
| محلي | 8 | B站花火 | Bilibili | OAuth2 Bearer | فين أصلي | ترقيم متزامن |
| محلي | 9 | 优酷广告 | Youku | OAuth2 + توقيع MD5 | يوان→فين | ترقيم متزامن |
| محلي | 10 | 美团广告 | Meituan | OAuth2 Bearer | فين أصلي | ترقيم متزامن |
| محلي | 11 | 知乎广告 | Zhihu | OAuth2 Bearer | يوان→فين | ترقيم متزامن |
| محلي | 12 | 360推广 | Qihoo360 | API Key + Sign | يوان→فين | ترقيم متزامن |
| محلي | 13 | 搜狗推广 | Sogou | API Key + Sign | يوان→فين | ترقيم متزامن |
| محلي | 14 | 友盟 | Umeng | API Key + MD5 | يوان→فين | ترقيم متزامن |
| محلي | 15 | 京东京准通 | Jingdong | OAuth2 + MD5 | يوان→فين | ترقيم متزامن |
| محلي | 16 | 拼多多广告 | Pinduoduo | OAuth2 + Sign مخصص | فين أصلي | ترقيم متزامن |
| دولي | 17 | Google Ads | Google | OAuth2 + GAQL | ميكرويوان→فين | pageToken |
| دولي | 18 | YouTube Ads | Youtube | OAuth2 + GAQL | ميكرويوان→فين | pageToken |
| دولي | 19 | Meta Ads | Meta | OAuth2 معاملات URL | فين أصلي | غير متزامن |
| دولي | 20 | TikTok Ads | Tiktok | OAuth2 Access-Token | ميكرويوان→فين | ترقيم متزامن |
| دولي | 21 | LinkedIn Ads | Linkedin | OAuth2 Bearer | ميكرويوان→فين | ترقيم متزامن |
| دولي | 22 | Snapchat Ads | Snapchat | OAuth2 Bearer | ميكرويوان→فين | ترقيم متزامن |
| دولي | 23 | Pinterest Ads | Pinterest | OAuth2 Bearer | ميكرويوان→فين | ترقيم متزامن |
| دولي | 24 | Twitter/X Ads | Twitter | OAuth2 Bearer | ميكرويوان→فين | ترقيم متزامن |
| دولي | 25 | Amazon Ads | Amazon | OAuth2 + Profile | فين أصلي | غير متزامن |
| دولي | 26 | The Trade Desk | TheTradeDesk | HMAC-SHA256 | فين أصلي | غير متزامن |
| دولي | 27 | Spotify Ads | Spotify | OAuth2 Bearer | فين أصلي | غير متزامن |
| دولي | 28 | Twitch Ads | Twitch | OAuth2 Bearer+ClientId | فين أصلي | متزامن |
| دولي | 29 | Netflix Ads | Netflix | OAuth2 client_credentials | فين أصلي | متزامن |

### تعريف الواجهات

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

### تعيين الحقول

يحوّل كل محول عبر `FieldMapping` الحقول الأصلية للمنصة إلى النموذج الموحد، وتُوضع الحقول الخاصة بالمنصة تلقائيًا في `extra` JSON. المبالغ موحدة على **الفين** (يوان صيني) / **سنت** (دولار).

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

## ثالثًا: تصميم قاعدة البيانات

### اصطلاحات التسمية
- بادئة الجداول: `erik_`
- المفتاح الأساسي: `BIGINT UNSIGNED PRIMARY KEY` (بدون تلقائي، يولّد بـ Snowflake ID)
- المحرك: InnoDB، مجموعة الأحرف: utf8mb4

### الجداول الأساسية (13)

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

## رابعًا: تكامل Erik Stack

| الحزمة | الاستخدام | موقع التكامل |
|----|------|---------|
| `erikwang2013/snowflake-php` | معرّف مفتاح أساسي موزع | SnowflakeTrait → جميع أحداث creating للنماذج |
| `erikwang2013/hashids` | تشفير/فك معرّفات طلبات/استجابات API | ApiResponse ترميز تلقائي لحقول id/*_id |
| `erikwang2013/jwt-webman` | رمز مصادقة JWT | AuthMiddleware + AuthController |
| `erikwang2013/encryption` | تشفير/فك البيانات الحساسة في طبقة API | EncryptionMiddleware (ترويسة X-Encrypted) |
| `erikwang2013/encryptable` | تشفير/فك تلقائي لحقول قاعدة البيانات | PlatformAccount/AuthToken $encryptable |
| `erikwang2013/webman-scout` | مزامنة بيانات Elasticsearch | config/scout.php |
| `erikwang2013/season` | أعلام الدول | PlatformBadge.vue (أعلام Unicode) |
| `erikwang2013/poster-php` | كابتشا السحب | CaptchaService + CaptchaWidget |

---

## خامسًا: التدويل (i18n)

تدعم جميع الواجهات **الصينية (zh-CN)** و**الإنجليزية (en)**:

| الطرف | التقنية | حجم الترجمة |
|----|------|--------|
| Admin | vue-i18n v9 | 158 مفتاحًا (app/nav/login/dashboard/campaign/account/report/alert/system/common) |
| Service API | `erik\support\I18n` | 12 مفتاح رسالة (ترويسة Accept-Language / معامل ?lang=) |
| Flutter | AppLocalizations + Delegate | أكثر من 20 مفتاح واجهة |
| HarmonyOS | StringResources | أكثر من 15 مفتاح واجهة |

---

## سادسًا: الكابتشا

تتطلب العمليات الحساسة مثل تسجيل الدخول إكمال كابتشا السحب (erikwang2013/poster-php):

```
GET  /api/v1/captcha/generate  → 返回背景图 + 拼图块 + AES 加密 token
POST /api/v1/captcha/verify    → 验证偏移量（5px 容差，5 分钟有效）
```

يدعم مكوّن `CaptchaWidget` في الواجهة الأمامية السحب/اللمس، مع تحديث تلقائي عند الفشل. يتحقق AuthController في الواجهة الخلفية من captcha_token + captcha_offset عند تسجيل الدخول.

### التأكيد الثانوي

تستخدم العمليات الحساسة مثل الحذف وإلغاء الربط والعمليات المجمعة نمط "الإدخال للتأكيد":

| العملية | طريقة التأكيد | كلمة التأكيد |
|------|---------|--------|
| إلغاء ربط الحساب | إدخال اسم الحساب | اسم الحساب |
| تشغيل/إيقاف الخطط مجمعة | إدخال كلمة تأكيد ثابتة | `ENABLE` / `PAUSE` |
| حذف قاعدة تنبيه | إدخال اسم القاعدة | اسم القاعدة |
| تعطيل/تفعيل مستخدم | إدخال اسم المستخدم | اسم المستخدم |

يقودها مكوّن `GlobalConfirm` العام + مخزن Pinia `useConfirmStore`، ولإضافة عملية حساسة جديدة يكفي استدعاء `confirmStore.show({...})`.

---

## سابعًا: كومة الوسائط الأمنية (8 طبقات إجمالاً)

### تدفق الطلبات

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

| الوسيط | الوظيفة |
|--------|------|
| CorsMiddleware | معالجة الطلبات عبر النطاقات، دعم X-Tenant-Id/X-Encrypted |
| RateLimitMiddleware | تقييد المعدل بنافذة منزلقة عبر Redis، افتراضيًا 60/60 ثانية |
| SqlGuardMiddleware | كشف أنماط حقن SQL (UNION/DROP/ALTER/التعليقات) |
| ValidationMiddleware | تقليم المدخلات + تصفية وسوم HTML |
| EncryptionMiddleware | فك تشفير الطلبات + تشفير الاستجابات (ترويسة X-Encrypted) |
| AuthMiddleware | التحقق من JWT Bearer Token (erikwang2013/jwt-webman) |
| TenantIdentify | تحليل المستأجرين المتعددين (ترويسة X-Tenant-Id / الجلسة) |

---

## ثامنًا: لوحة إدارة الويب

التقنيات: Vue 3 + TypeScript + Element Plus + ECharts 5 + Pinia + Axios

### الصفحات المنفذة

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

- النوع العام Axios `UnwrappedInstance` يفك تلقائيًا مغلف `ApiResponse<T>`
- `vue-tsc --noEmit` **صفر أخطاء**

---

## تاسعًا: تطبيق Flutter

تصميم متجاوب بأولوية PC Web، مع 3 نقاط انقطاع متكيفة.

| نقطة الانقطاع | العرض | التخطيط | التنقل |
|------|------|------|------|
| الجوال | < 600px | بطاقات بعمود واحد | NavigationBar سفلي |
| اللوحي | 600-1200px | شبكة بعمودين | درج Drawer |
| سطح المكتب | > 1200px | شبكة متعددة الأعمدة + DataTable | SideNav ثابت (250px) |

**تقسيم العمل بين PC ولوحة الإدارة**:

- **webman-admin**: الإدارة الثقيلة (تقارير متعمقة/إعدادات النظام/إدارة المستأجرين/عمليات مجمعة)
- **Flutter Web/PC**: لوحة تشغيل خفيفة (مراقبة فورية/معالجة التنبيهات/إطلاق خفيف، دون حاجة VPN)

---

## عاشرًا: تطبيق HarmonyOS

التقنيات: ArkTS + ArkUI. الوظائف متماشية مع تطبيق Flutter.

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

## حادي عشر: تصميم API

البادئة `/api/v1`، بتنسيق استجابة موحد:

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

### جميع نقاط النهاية

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

## ثاني عشر: مخططات منطق الأعمال

### اتصالات Admin ↔ Service

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

### تدفق تفويض منصة OAuth

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

### مزامنة البيانات وتدفق التنبيهات

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

### نمط المحول


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

## ثالث عشر: مزامنة البيانات وجدولة المهام

باستخدام webman/crontab، مع تسريع ذاكرة Redis.

| المهمة | التكرار | الوصف |
|------|------|------|
| TokenRefreshTask | كل 55 دقيقة | فحص Token المنتهي وتحديثه تلقائيًا |
| DataSyncTask | كل 10 دقائق | جلب خطط كل منصة + تقارير آخر يومين، ومسح ذاكرة لوحة التحكم بعد المزامنة |
| AlertCheckTask | كل 5 دقائق | اجتياز القواعد المفعلة، تقييم العتبات، إطلاق الدفع |
| RetrySyncTask | كل 3 دقائق | إعادة محاولة المزامنات الفاشلة (جدول erik_sync_errors، حتى 3 مرات، تراجع أُسي) |

استراتيجية المزامنة: معالجة تدفقية عبر Generator في المحولات، حماية من الفقدان بالمؤشر/الترقيم، إعادة محاولة تلقائية عند الفشل، فحص curl_errno، وتقييد QPS على مستوى المنصة.

---

## رابع عشر: بنية النشر

### النشر بالحاويات

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

### تدفق النشر الإنتاجي

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

### نشر Docker بضغطة واحدة

```bash
docker-compose up -d          # MySQL + Redis + PHP + Nginx
make db-init                  # 初始化数据库
make admin-dev                # 前端开发模式
```

---

## خامس عشر: سجل التنفيذ

| المرحلة | المحتوى | الحالة |
|------|------|------|
| المرحلة 1 | webman v2 + هيكل لوحة الإدارة + مستأجرون متعددون + OAuth + Juliang | ✅ |
| المرحلة 2 | محول Baidu + محول Taobao + مزامنة البيانات + محرك التقارير | ✅ |
| المرحلة 3 | Tencent + Umeng + Kuaishou + Xiaohongshu (4 جديدة) | ✅ |
| المرحلة 4 | Weibo + Bilibili + Youku + Meituan + Zhihu + 360 + Sogou + Jingdong + Pinduoduo (9 محلية جديدة) | ✅ |
| المرحلة 4 | Meta + LinkedIn + Snapchat + Pinterest + Twitter + Amazon + TTD + Spotify + Twitch + Netflix + Google + YouTube + TikTok (13 دولية جديدة) | ✅ |
| المرحلة 5 | نظام التنبيهات + تصدير التقارير + تطبيق Flutter + تطبيق HarmonyOS + تعزيز لوحة التحكم | ✅ |
| المرحلة 6 | تكامل Erik Stack (snowflake/hashids/jwt-webman/encryption/encryptable/scout/season) | ✅ |
| المرحلة 7 | نشر Docker + تعزيز الأمان (RateLimit/CORS/SQLGuard) + طبقة التخزين + README | ✅ |
| المرحلة 8 | إعادة تنظيم الأدلة (apps/) + Admin مستقل webman-admin v2 (خلفية PHP + ServiceProxy) + RBAC + سجل التدقيق | ✅ |
| المرحلة 9 | توثيق API + تقييد معدل المنصات + قائمة انتظار إعادة محاولة المزامنة + 20 اختبار PHPUnit + GitHub Actions CI/CD | ✅ |
| المرحلة 10 | تعليقات صينية في ملفات التكوين + تعليقات .env + توثيق بيانات اعتماد المنصات + إعادة كتابة بادئة الجداول erik_ + BIGINT PK | ✅ |
| المرحلة 11 | التدويل (vue-i18n + I18n.php + Flutter + HarmonyOS) + كابتشا السحب (poster-php) | ✅ |
| المرحلة 12 | التأكيد الثانوي (الإدخال للتأكيد) — إلغاء الربط/الحذف/العمليات المجمعة تتطلب كتابة الاسم الهدف قبل التنفيذ | ✅ |

---

## سادس عشر: بنية لوحة إدارة Admin

### واجهة PHP الخلفية (المنفذ 8789)

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

### صلاحيات الأدوار (RBAC)

| الدور | slug | الصلاحيات |
|------|------|------|
| مدير فائق | super_admin | `*` جميع الصلاحيات |
| مدير التشغيل | ops_manager | dashboard, campaigns, reports, alerts, accounts |
| محلل البيانات | analyst | dashboard, reports |

### اتصالات Admin وService

يستدعي Admin واجهات service API عبر `ServiceProxy` (cURL)، مع إعادة توجيه JWT Token. يتولى Admin بنفسه المصادقة وإدارة المستخدمين، بينما تُوفَّر بيانات الأعمال بالكامل من service.

---

## سابع عشر: الاختبارات وCI/CD

### مجموعة اختبارات PHPUnit

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

تحديث تلقائي أسبوعي لتبعيات Composer + npm + Docker.
