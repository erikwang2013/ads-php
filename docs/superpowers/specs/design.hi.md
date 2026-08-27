# मल्टी-प्लेटफ़ॉर्म विज्ञापन प्रबंधन प्रणाली डिज़ाइन

[中文](docs/superpowers/specs/design.md) | [English](docs/superpowers/specs/design.en.md) | [한국어](docs/superpowers/specs/design.ko.md) | [Русский](docs/superpowers/specs/design.ru.md) | [Deutsch](docs/superpowers/specs/design.de.md) | [Français](docs/superpowers/specs/design.fr.md) | [Español](docs/superpowers/specs/design.es.md) | [Português](docs/superpowers/specs/design.pt.md) | [हिन्दी](docs/superpowers/specs/design.hi.md) | [العربية](docs/superpowers/specs/design.ar.md) | [বাংলা](docs/superpowers/specs/design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/design.id.md) | [日本語](docs/superpowers/specs/design.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## अवलोकन

**29 विज्ञापन प्लेटफ़ॉर्म** से जुड़ा एकीकृत विज्ञापन प्रबंधन मंच, घरेलू और अंतर्राष्ट्रीय प्रमुख विज्ञापन विक्रेताओं को कवर करता है, विज्ञापन डिलीवरी प्रबंधन, क्रॉस-प्लेटफ़ॉर्म डेटा रिपोर्ट और रीयल-टाइम अलर्ट मॉनिटरिंग का समर्थन करता है।

- **service** — उपयोगकर्ता-साइड बिज़नेस सेवा, webman v2 (PHP 8.2+), :8788 पर सुनती है
- **admin** — स्वतंत्र एडमिन पैनल, webman-admin v2 (PHP बैकएंड :8789 + Vue 3 SPA)
- **apps** — क्लाइंट ऐप, Flutter (iOS/Android/Web PC) + HarmonyOS (ArkTS)
- **इंफ्रास्ट्रक्चर**: Docker + Nginx + MySQL 8.0 + Redis 7 + Elasticsearch

बिज़नेस परिदृश्य सेल्फ-यूज़ डिलीवरी, SaaS मल्टी-टेनेंट और एजेंसी ऑपरेशन तीन मोड कवर करते हैं।

### संचार आर्किटेक्चर

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

## समग्र आर्किटेक्चर

### सिस्टम आर्किटेक्चर आरेख

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

## 一、सर्वर-साइड मॉड्यूल विभाजन

webman v2 प्लगइन-आधारित आर्किटेक्चर, `service/plugin/` के अंतर्गत 7 प्लगइन:

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

## 二、प्लेटफ़ॉर्म एडाप्टर

### अनुकूलित प्लेटफ़ॉर्म (29)

| क्षेत्र | # | प्लेटफ़ॉर्म | एडाप्टर क्लास | प्रमाणीकरण | राशि | रिपोर्ट |
|------|---|------|---------|------|------|------|
| घरेलू | 1 | 巨量引擎 | Juliang | OAuth2 Access-Token | 元→分 | सिंक पेजिनेशन |
| घरेलू | 2 | 百度营销 | Baidu | OAuth2 + 信封签名 | 元→分 | एसिंक पोलिंग |
| घरेलू | 3 | 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5签名 | 元→分 | सिंक पेजिनेशन |
| घरेलू | 4 | 腾讯广告 | Tencent | OAuth2 + nonce | 分原生 | सिंक पेजिनेशन |
| घरेलू | 5 | 快手磁力引擎 | Kuaishou | OAuth2 URL参数 | 元→分 | सिंक पेजिनेशन |
| घरेलू | 6 | 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer | 分原生 | सिंक पेजिनेशन |
| घरेलू | 7 | 微博粉丝通 | Weibo | OAuth2 Bearer | 分原生 | सिंक पेजिनेशन |
| घरेलू | 8 | B站花火 | Bilibili | OAuth2 Bearer | 分原生 | सिंक पेजिनेशन |
| घरेलू | 9 | 优酷广告 | Youku | OAuth2 + MD5签名 | 元→分 | सिंक पेजिनेशन |
| घरेलू | 10 | 美团广告 | Meituan | OAuth2 Bearer | 分原生 | सिंक पेजिनेशन |
| घरेलू | 11 | 知乎广告 | Zhihu | OAuth2 Bearer | 元→分 | सिंक पेजिनेशन |
| घरेलू | 12 | 360推广 | Qihoo360 | API Key + Sign | 元→分 | सिंक पेजिनेशन |
| घरेलू | 13 | 搜狗推广 | Sogou | API Key + Sign | 元→分 | सिंक पेजिनेशन |
| घरेलू | 14 | 友盟 | Umeng | API Key + MD5 | 元→分 | सिंक पेजिनेशन |
| घरेलू | 15 | 京东京准通 | Jingdong | OAuth2 + MD5 | 元→分 | सिंक पेजिनेशन |
| घरेलू | 16 | 拼多多广告 | Pinduoduo | OAuth2 + 自定义Sign | 分原生 | सिंक पेजिनेशन |
| अंतर्राष्ट्रीय | 17 | Google Ads | Google | OAuth2 + GAQL | 微元→分 | pageToken |
| अंतर्राष्ट्रीय | 18 | YouTube Ads | Youtube | OAuth2 + GAQL | 微元→分 | pageToken |
| अंतर्राष्ट्रीय | 19 | Meta Ads | Meta | OAuth2 URL参数 | 分原生 | एसिंक |
| अंतर्राष्ट्रीय | 20 | TikTok Ads | Tiktok | OAuth2 Access-Token | 微元→分 | सिंक पेजिनेशन |
| अंतर्राष्ट्रीय | 21 | LinkedIn Ads | Linkedin | OAuth2 Bearer | 微元→分 | सिंक पेजिनेशन |
| अंतर्राष्ट्रीय | 22 | Snapchat Ads | Snapchat | OAuth2 Bearer | 微元→分 | सिंक पेजिनेशन |
| अंतर्राष्ट्रीय | 23 | Pinterest Ads | Pinterest | OAuth2 Bearer | 微元→分 | सिंक पेजिनेशन |
| अंतर्राष्ट्रीय | 24 | Twitter/X Ads | Twitter | OAuth2 Bearer | 微元→分 | सिंक पेजिनेशन |
| अंतर्राष्ट्रीय | 25 | Amazon Ads | Amazon | OAuth2 + Profile | 分原生 | एसिंक |
| अंतर्राष्ट्रीय | 26 | The Trade Desk | TheTradeDesk | HMAC-SHA256 | 分原生 | एसिंक |
| अंतर्राष्ट्रीय | 27 | Spotify Ads | Spotify | OAuth2 Bearer | 分原生 | एसिंक |
| अंतर्राष्ट्रीय | 28 | Twitch Ads | Twitch | OAuth2 Bearer+ClientId | 分原生 | सिंक |
| अंतर्राष्ट्रीय | 29 | Netflix Ads | Netflix | OAuth2 client_credentials | 分原生 | सिंक |

### इंटरफ़ेस परिभाषा

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

### फ़ील्ड मैपिंग

प्रत्येक एडाप्टर `FieldMapping` के माध्यम से प्लेटफ़ॉर्म के मूल फ़ील्ड को एकीकृत मॉडल में बदलता है, प्लेटफ़ॉर्म-विशिष्ट फ़ील्ड स्वचालित रूप से `extra` JSON में जाते हैं। राशि एकीकृत रूप से **分**（रॅन्मिन्बी）/ **分-cent**（अमेरिकी डॉलर）होती है।

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

## 三、डेटाबेस डिज़ाइन

### नामकरण मानक
- टेबल प्रीफ़िक्स: `ads_`
- प्राइमरी की: `BIGINT UNSIGNED PRIMARY KEY` (बिना auto-increment, Snowflake ID जनरेशन)
- इंजन: InnoDB, कैरेक्टर सेट: utf8mb4

### कोर टेबल (13)

```sql
-- 租户
CREATE TABLE ads_tenants (
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
CREATE TABLE ads_platform_accounts (
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
CREATE TABLE ads_auth_tokens (
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
CREATE TABLE ads_campaigns (
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
CREATE TABLE ads_ad_groups (
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
CREATE TABLE ads_creatives (
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
CREATE TABLE ads_report_metrics (
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
CREATE TABLE ads_report_extras (
    id BIGINT UNSIGNED PRIMARY KEY,
    report_metric_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(32) NOT NULL,
    extra JSON,
    FOREIGN KEY (report_metric_id) REFERENCES ads_report_metrics(id) ON DELETE CASCADE
);

-- 告警规则
CREATE TABLE ads_alert_rules (
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
CREATE TABLE ads_alert_logs (
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

## 四、Erik Stack एकीकरण

| पैकेज | उपयोग | एकीकरण स्थान |
|----|------|---------|
| `erikwang2013/snowflake-php` | वितरित प्राइमरी की ID | SnowflakeTrait → सभी Model creating इवेंट |
| `erikwang2013/hashids` | API अनुरोध/प्रतिक्रिया ID एन्क्रिप्शन/डिक्रिप्शन | ApiResponse स्वचालित id/*_id फ़ील्ड एन्कोड |
| `erikwang2013/jwt-webman` | JWT प्रमाणीकरण टोकन | AuthMiddleware + AuthController |
| `erikwang2013/encryption` | API परत संवेदनशील डेटा एन्क्रिप्शन/डिक्रिप्शन | EncryptionMiddleware (X-Encrypted हेडर) |
| `erikwang2013/encryptable` | DB फ़ील्ड स्वचालित एन्क्रिप्शन/डिक्रिप्शन | PlatformAccount/AuthToken $encryptable |
| `erikwang2013/webman-scout` | Elasticsearch डेटा सिंक | config/scout.php |
| `erikwang2013/season` | देश ध्वज | PlatformBadge.vue (Unicode ध्वज) |
| `erikwang2013/poster-php` | स्लाइडर कैप्चा | CaptchaService + CaptchaWidget |

---

## 五、अंतर्राष्ट्रीयकरण (i18n)

सभी इंटरफ़ेस **चीनी (zh-CN)** और **English (en)** का समर्थन करते हैं:

| एंड | तकनीक | अनुवाद मात्रा |
|----|------|--------|
| Admin | vue-i18n v9 | 158 keys (app/nav/login/dashboard/campaign/account/report/alert/system/common) |
| Service API | `erik\support\I18n` | 12 मैसेज keys (Accept-Language header / ?lang= param) |
| Flutter | AppLocalizations + Delegate | 20+ UI keys |
| HarmonyOS | StringResources | 15+ UI keys |

---

## 六、कैप्चा

लॉगिन जैसे संवेदनशील ऑपरेशन के लिए स्लाइडर कैप्चा पूरा करना आवश्यक है (erikwang2013/poster-php):

```
GET  /api/v1/captcha/generate  → 返回背景图 + 拼图块 + AES 加密 token
POST /api/v1/captcha/verify    → 验证偏移量（5px 容差，5 分钟有效）
```

फ्रंटएंड `CaptchaWidget` कंपोनेंट ड्रैग/टच का समर्थन करता है, विफलता पर स्वचालित रीफ़्रेश। बैकएंड AuthController लॉगिन पर captcha_token + captcha_offset सत्यापित करता है।

### द्वितीयक पुष्टि

डिलीट, अनबाइंड, बैच ऑपरेशन जैसे संवेदनशील ऑपरेशन "पुष्टि के लिए इनपुट" मोड अपनाते हैं:

| ऑपरेशन | पुष्टि विधि | पुष्टि शब्द |
|------|---------|--------|
| खाता अनबाइंड | खाता नाम इनपुट करें | खाता नाम |
| बैच स्टार्ट/स्टॉप अभियान | निश्चित पुष्टि शब्द इनपुट करें | `ENABLE` / `PAUSE` |
| अलर्ट नियम हटाना | नियम नाम इनपुट करें | नियम नाम |
| उपयोगकर्ता निष्क्रिय/सक्रिय | उपयोगकर्ता नाम इनपुट करें | उपयोगकर्ता नाम |

सामान्य `GlobalConfirm` कंपोनेंट + `useConfirmStore` Pinia store से संचालित, नया संवेदनशील ऑपरेशन केवल `confirmStore.show({...})` कॉल करना होता है।

---

## 七、सुरक्षा मिडलवेयर स्टैक (कुल 8 परतें)

### अनुरोध प्रवाह

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

| मिडलवेयर | फ़ंक्शन |
|--------|------|
| CorsMiddleware | क्रॉस-डोमेन अनुरोध हैंडलिंग, X-Tenant-Id/X-Encrypted समर्थन |
| RateLimitMiddleware | Redis स्लाइडिंग विंडो रेट-लिमिट, डिफ़ॉल्ट 60 बार/60 सेकंड |
| SqlGuardMiddleware | SQL इंजेक्शन पैटर्न डिटेक्शन (UNION/DROP/ALTER/कमेंट) |
| ValidationMiddleware | इनपुट ट्रिम + HTML टैग फ़िल्टरिंग |
| EncryptionMiddleware | अनुरोध डिक्रिप्शन + प्रतिक्रिया एन्क्रिप्शन (X-Encrypted हेडर) |
| AuthMiddleware | JWT Bearer Token सत्यापन (erikwang2013/jwt-webman) |
| TenantIdentify | मल्टी-टेनेंट पार्सिंग (X-Tenant-Id हेडर / Session) |

---

## 八、Web एडमिन पैनल

तकनीकी स्टैक: Vue 3 + TypeScript + Element Plus + ECharts 5 + Pinia + Axios

### लागू पेज

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

- Axios जेनेरिक प्रकार `UnwrappedInstance` स्वचालित रूप से `ApiResponse<T>` एनवेलप अनरैप करता है
- `vue-tsc --noEmit` **शून्य त्रुटि**

---

## 九、Flutter App

PC Web प्राथमिकता रिस्पॉन्सिव डिज़ाइन, 3 ब्रेकपॉइंट पर स्वचालित अनुकूलन।

| ब्रेकपॉइंट | चौड़ाई | लेआउट | नेविगेशन |
|------|------|------|------|
| Mobile | < 600px | एकल कॉलम कार्ड | नीचे NavigationBar |
| Tablet | 600-1200px | दो-कॉलम ग्रिड | Drawer |
| Desktop | > 1200px | मल्टी-कॉलम ग्रिड + DataTable | फिक्स्ड SideNav (250px) |

**PC एंड और एडमिन पैनल का विभाजन**:

- **webman-admin**: भारी प्रबंधन (गहन रिपोर्ट/सिस्टम कॉन्फ़िगरेशन/टेनेंट प्रबंधन/बैच ऑपरेशन)
- **Flutter Web/PC**: हल्का ऑपरेशन पैनल (रीयल-टाइम मॉनिटरिंग/अलर्ट हैंडलिंग/हल्की डिलीवरी, VPN की आवश्यकता नहीं)

---

## 十、HarmonyOS App

तकनीकी स्टैक: ArkTS + ArkUI। फ़ंक्शन Flutter App के अनुरूप।

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

## 十一、API डिज़ाइन

प्रीफ़िक्स `/api/v1`, एकीकृत प्रतिक्रिया फ़ॉर्मेट:

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

### सभी एंडपॉइंट

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

## 十二、बिज़नेस लॉजिक आरेख

### Admin ↔ Service संचार

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

### OAuth प्लेटफ़ॉर्म प्राधिकरण प्रवाह

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

### डेटा सिंक और अलर्ट प्रवाह

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

### एडाप्टर पैटर्न


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

## 十三、डेटा सिंक और कार्य शेड्यूलिंग

webman/crontab का उपयोग, Redis कैश से त्वरण।

| कार्य | आवृत्ति | विवरण |
|------|------|------|
| TokenRefreshTask | हर 55 मिनट | समाप्त Token स्कैन करें, स्वचालित रिफ़्रेश |
| DataSyncTask | हर 10 मिनट | प्रत्येक प्लेटफ़ॉर्म के अभियान + पिछले 2 दिन की रिपोर्ट प्राप्त करें, सिंक के बाद डैशबोर्ड कैश साफ़ करें |
| AlertCheckTask | हर 5 मिनट | सक्षम नियमों को स्कैन करें, थ्रेशोल्ड का मूल्यांकन करें, पुश ट्रिगर करें |
| RetrySyncTask | हर 3 मिनट | असफल सिंक पुनः प्रयास करें (ads_sync_errors टेबल, अधिकतम 3 बार, एक्सपोनेंशियल बैकऑफ़) |

सिंक रणनीति: एडाप्टर Generator स्ट्रीमिंग प्रोसेसिंग, कर्सर/पेजिनेशन से डेटा-लॉस रोकथाम, विफलता पर स्वचालित रीट्राई, curl_errno जाँच, प्लेटफ़ॉर्म-स्तरीय QPS रेट-लिमिट।

---

## 十四、डिप्लॉयमेंट आर्किटेक्चर

### कंटेनराइज़्ड डिप्लॉयमेंट

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
        │ads_*   │ │ cache,   │ │ search   │
        │admin_*  │ │ queue    │ │          │
        └─────────┘ └──────────┘ └──────────┘
```

### प्रोडक्शन डिप्लॉयमेंट प्रवाह

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

### Docker वन-क्लिक डिप्लॉयमेंट

```bash
docker-compose up -d          # MySQL + Redis + PHP + Nginx
make db-init                  # 初始化数据库
make admin-dev                # 前端开发模式
```

---

## 十五、कार्यान्वयन इतिहास

| चरण | सामग्री | स्थिति |
|------|------|------|
| Phase 1 | webman v2 + एडमिन पैनल स्केलेटन + मल्टी-टेनेंट + OAuth + 巨量 | ✅ |
| Phase 2 | Baidu एडाप्टर + Taobao एडाप्टर + डेटा सिंक + रिपोर्ट इंजन | ✅ |
| Phase 3 | Tencent + Umeng + Kuaishou + Xiaohongshu (4 नए) | ✅ |
| Phase 4 | Weibo + Bilibili + Youku + Meituan + Zhihu + Qihoo360 + Sogou + Jingdong + Pinduoduo (9 नए घरेलू) | ✅ |
| Phase 4 | Meta + LinkedIn + Snapchat + Pinterest + Twitter + Amazon + TTD + Spotify + Twitch + Netflix + Google + YouTube + TikTok (13 नए अंतर्राष्ट्रीय) | ✅ |
| Phase 5 | अलर्ट सिस्टम + रिपोर्ट एक्सपोर्ट + Flutter App + HarmonyOS App + डैशबोर्ड एन्हांसमेंट | ✅ |
| Phase 6 | Erik Stack एकीकरण (snowflake/hashids/jwt-webman/encryption/encryptable/scout/season) | ✅ |
| Phase 7 | Docker डिप्लॉयमेंट + सुरक्षा सख्ती (RateLimit/CORS/SQLGuard) + कैश परत + README | ✅ |
| Phase 8 | निर्देशिका पुनर्गठन (apps/) + स्वतंत्र Admin webman-admin v2 (PHP बैकएंड+ServiceProxy) + RBAC + ऑडिट लॉग | ✅ |
| Phase 9 | API दस्तावेज़ + प्लेटफ़ॉर्म रेट-लिमिट + सिंक रीट्राई क्यू + PHPUnit 20 टेस्ट + GitHub Actions CI/CD | ✅ |
| Phase 10 | कॉन्फ़िगरेशन फ़ाइलें चीनी टिप्पणियाँ + .env टिप्पणियाँ + प्लेटफ़ॉर्म क्रेडेंशियल दस्तावेज़ + ads_ टेबल प्रीफ़िक्स + BIGINT PK | ✅ |
| Phase 11 | अंतर्राष्ट्रीयकरण (vue-i18n + I18n.php + Flutter + HarmonyOS) + स्लाइडर कैप्चा (poster-php) | ✅ |
| Phase 12 | द्वितीयक पुष्टि (इनपुट-टू-कन्फ़र्म) — अनबाइंड/डिलीट/बैच ऑपरेशन के लिए लक्ष्य नाम टाइप करना आवश्यक | ✅ |

---

## 十六、Admin एडमिन पैनल आर्किटेक्चर

### PHP बैकएंड (पोर्ट 8789)

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

### भूमिका अनुमतियाँ (RBAC)

| भूमिका | slug | अनुमतियाँ |
|------|------|------|
| सुपर एडमिन | super_admin | `*` सभी अनुमतियाँ |
| ऑपरेशन मैनेजर | ops_manager | dashboard, campaigns, reports, alerts, accounts |
| डेटा विश्लेषक | analyst | dashboard, reports |

### Admin और Service संचार

Admin `ServiceProxy` (cURL) के माध्यम से service API को कॉल करता है, JWT Token फ़ॉरवर्ड करता है। Admin स्वयं प्रमाणीकरण/प्राधिकरण और उपयोगकर्ता प्रबंधन संभालता है, बिज़नेस डेटा पूरी तरह से service द्वारा प्रदान किया जाता है।

---

## 十七、टेस्ट और CI/CD

### PHPUnit टेस्ट सूट

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

हर सप्ताह स्वचालित रूप से Composer + npm + Docker डिपेंडेंसी अपडेट करता है।
