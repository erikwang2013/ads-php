# Design des Multiplattform-Werbeanlagen-Managementsystems

[中文](docs/superpowers/specs/design.md) | [English](docs/superpowers/specs/design.en.md) | [한국어](docs/superpowers/specs/design.ko.md) | [Русский](docs/superpowers/specs/design.ru.md) | [Deutsch](docs/superpowers/specs/design.de.md) | [Français](docs/superpowers/specs/design.fr.md) | [Español](docs/superpowers/specs/design.es.md) | [Português](docs/superpowers/specs/design.pt.md) | [हिन्दी](docs/superpowers/specs/design.hi.md) | [العربية](docs/superpowers/specs/design.ar.md) | [বাংলা](docs/superpowers/specs/design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/design.id.md) | [日本語](docs/superpowers/specs/design.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## Überblick

Vereinheitlichte Werbemanagement-Plattform für die Anbindung von **29 Werbeplattformen**, die namhafte in- und ausländische Werbefirmen abdeckt und Anzeigenverwaltung, plattformübergreifende Datenberichte sowie Echtzeit-Alarmüberwachung unterstützt.

- **service** — Benutzerseitiger Business-Dienst, webman v2 (PHP 8.2+), hört auf :8788
- **admin** — Eigenständiges Admin-Backend, webman-admin v2 (PHP-Backend :8789 + Vue 3 SPA)
- **apps** — Client-Apps, Flutter (iOS/Android/Web PC) + HarmonyOS (ArkTS)
- **Infrastruktur**: Docker + Nginx + MySQL 8.0 + Redis 7 + Elasticsearch

Die Geschäftsszenarien decken Eigenwerbung, SaaS-Multi-Tenant und Agenturbetrieb (代运营) ab.

### Kommunikationsarchitektur

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

## Gesamtarchitektur

### Systemarchitekturdiagramm

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

## 一、服务端模块拆解

webman v2 Plugin-Architektur, 7 Plugins unter `service/plugin/`:

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

## 二、平台适配器

### Adaptierte Plattformen (29)

| Region | # | Plattform | Adapterklasse | Authentifizierung | Betrag | Berichte |
|------|---|------|---------|------|------|------|
| Inland | 1 | 巨量引擎 | Juliang | OAuth2 Access-Token | Yuan→Fen | Synchrone Paginierung |
| Inland | 2 | 百度营销 | Baidu | OAuth2 + Envelope-Signatur | Yuan→Fen | Asynchrones Polling |
| Inland | 3 | 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5-Signatur | Yuan→Fen | Synchrone Paginierung |
| Inland | 4 | 腾讯广告 | Tencent | OAuth2 + nonce | Fen nativ | Synchrone Paginierung |
| Inland | 5 | 快手磁力引擎 | Kuaishou | OAuth2 URL-Parameter | Yuan→Fen | Synchrone Paginierung |
| Inland | 6 | 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer | Fen nativ | Synchrone Paginierung |
| Inland | 7 | 微博粉丝通 | Weibo | OAuth2 Bearer | Fen nativ | Synchrone Paginierung |
| Inland | 8 | B站花火 | Bilibili | OAuth2 Bearer | Fen nativ | Synchrone Paginierung |
| Inland | 9 | 优酷广告 | Youku | OAuth2 + MD5-Signatur | Yuan→Fen | Synchrone Paginierung |
| Inland | 10 | 美团广告 | Meituan | OAuth2 Bearer | Fen nativ | Synchrone Paginierung |
| Inland | 11 | 知乎广告 | Zhihu | OAuth2 Bearer | Yuan→Fen | Synchrone Paginierung |
| Inland | 12 | 360推广 | Qihoo360 | API Key + Sign | Yuan→Fen | Synchrone Paginierung |
| Inland | 13 | 搜狗推广 | Sogou | API Key + Sign | Yuan→Fen | Synchrone Paginierung |
| Inland | 14 | 友盟 | Umeng | API Key + MD5 | Yuan→Fen | Synchrone Paginierung |
| Inland | 15 | 京东京准通 | Jingdong | OAuth2 + MD5 | Yuan→Fen | Synchrone Paginierung |
| Inland | 16 | 拼多多广告 | Pinduoduo | OAuth2 + benutzerdefinierte Signatur | Fen nativ | Synchrone Paginierung |
| International | 17 | Google Ads | Google | OAuth2 + GAQL | Mikro-Dollar→Fen | pageToken |
| International | 18 | YouTube Ads | Youtube | OAuth2 + GAQL | Mikro-Dollar→Fen | pageToken |
| International | 19 | Meta Ads | Meta | OAuth2 URL-Parameter | Fen nativ | Asynchron |
| International | 20 | TikTok Ads | Tiktok | OAuth2 Access-Token | Mikro-Dollar→Fen | Synchrone Paginierung |
| International | 21 | LinkedIn Ads | Linkedin | OAuth2 Bearer | Mikro-Dollar→Fen | Synchrone Paginierung |
| International | 22 | Snapchat Ads | Snapchat | OAuth2 Bearer | Mikro-Dollar→Fen | Synchrone Paginierung |
| International | 23 | Pinterest Ads | Pinterest | OAuth2 Bearer | Mikro-Dollar→Fen | Synchrone Paginierung |
| International | 24 | Twitter/X Ads | Twitter | OAuth2 Bearer | Mikro-Dollar→Fen | Synchrone Paginierung |
| International | 25 | Amazon Ads | Amazon | OAuth2 + Profile | Fen nativ | Asynchron |
| International | 26 | The Trade Desk | TheTradeDesk | HMAC-SHA256 | Fen nativ | Asynchron |
| International | 27 | Spotify Ads | Spotify | OAuth2 Bearer | Fen nativ | Asynchron |
| International | 28 | Twitch Ads | Twitch | OAuth2 Bearer+ClientId | Fen nativ | Synchron |
| International | 29 | Netflix Ads | Netflix | OAuth2 client_credentials | Fen nativ | Synchron |

### Schnittstellendefinition

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

### Feldzuordnung

Jeder Adapter überführt über `FieldMapping` die plattformspezifischen Rohfelder in das einheitliche Modell; plattformspezifische Felder fallen automatisch in das `extra`-JSON. Beträge werden einheitlich in **Fen** (CNY) / **Cent** (USD) angegeben.

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

## 三、数据库设计

### Namenskonventionen
- Tabellenpräfix: `erik_`
- Primärschlüssel: `BIGINT UNSIGNED PRIMARY KEY` (kein Auto-Increment, Snowflake-ID-Generierung)
- Engine: InnoDB, Zeichensatz: utf8mb4

### Kerntabellen (13)

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

## 四、Erik Stack-Integration

| Paket | Zweck | Integrationsort |
|----|------|---------|
| `erikwang2013/snowflake-php` | Verteilte Primärschlüssel-IDs | SnowflakeTrait → creating-Ereignis aller Modelle |
| `erikwang2013/hashids` | Ver-/Entschlüsselung von API-Request-/Response-IDs | ApiResponse kodiert id/*_id-Felder automatisch |
| `erikwang2013/jwt-webman` | JWT-Authentifizierungstoken | AuthMiddleware + AuthController |
| `erikwang2013/encryption` | Ver-/Entschlüsselung sensibler Daten auf API-Ebene | EncryptionMiddleware (X-Encrypted-Header) |
| `erikwang2013/encryptable` | Automatische Ver-/Entschlüsselung von DB-Feldern | PlatformAccount/AuthToken $encryptable |
| `erikwang2013/webman-scout` | Elasticsearch-Datensynchronisierung | config/scout.php |
| `erikwang2013/season` | Länderflaggen | PlatformBadge.vue (Unicode-Flaggen) |
| `erikwang2013/poster-php` | Slider-Captcha | CaptchaService + CaptchaWidget |

---

## 五、Internationalisierung (i18n)

Alle Oberflächen unterstützen **Chinesisch (zh-CN)** und **English (en)**:

| Endpunkt | Technologie | Übersetzungsumfang |
|----|------|--------|
| Admin | vue-i18n v9 | 158 keys (app/nav/login/dashboard/campaign/account/report/alert/system/common) |
| Service API | `erik\support\I18n` | 12 Nachrichten-keys (Accept-Language-Header / ?lang=-Parameter) |
| Flutter | AppLocalizations + Delegate | 20+ UI-keys |
| HarmonyOS | StringResources | 15+ UI-keys |

---

## 六、Captcha

Für sensible Vorgänge wie Login ist ein Slider-Captcha (erikwang2013/poster-php) erforderlich:

```
GET  /api/v1/captcha/generate  → 返回背景图 + 拼图块 + AES 加密 token
POST /api/v1/captcha/verify    → 验证偏移量（5px 容差，5 分钟有效）
```

Das Frontend-Widget `CaptchaWidget` unterstützt Ziehen/Berühren und lädt bei Fehlern automatisch neu. Der AuthController prüft beim Login captcha_token + captcha_offset.

### Zweitbestätigung

Sensible Vorgänge wie Löschen, Entbinden und Massenoperationen verwenden das Muster „Eingabe zur Bestätigung":

| Vorgang | Bestätigungsmethode | Bestätigungswort |
|------|---------|--------|
| Konto entbinden | Kontonamen eingeben | Kontoname |
| Massenweise Pläne starten/stoppen | Festes Bestätigungswort eingeben | `ENABLE` / `PAUSE` |
| Alarmregel löschen | Regelnamen eingeben | Regelname |
| Benutzer deaktivieren/aktivieren | Benutzernamen eingeben | Benutzername |

Angetrieben von der allgemeinen Komponente `GlobalConfirm` + dem Pinia-Store `useConfirmStore`; für neue sensible Vorgänge genügt ein Aufruf von `confirmStore.show({...})`.

---

## 七、Sicherheits-Middleware-Stack (insgesamt 8 Ebenen)

### Request-Fluss

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

| Middleware | Funktion |
|--------|------|
| CorsMiddleware | CORS-Requests, unterstützt X-Tenant-Id/X-Encrypted |
| RateLimitMiddleware | Ratenbegrenzung mit gleitendem Redis-Fenster, Standard 60/60s |
| SqlGuardMiddleware | Erkennung von SQL-Injection-Mustern (UNION/DROP/ALTER/Kommentare) |
| ValidationMiddleware | Eingabe-Kürzen + HTML-Tag-Filter |
| EncryptionMiddleware | Request-Entschlüsselung + Response-Verschlüsselung (X-Encrypted-Header) |
| AuthMiddleware | JWT-Bearer-Token-Verifikation (erikwang2013/jwt-webman) |
| TenantIdentify | Multi-Tenant-Auflösung (X-Tenant-Id-Header / Session) |

---

## 八、Web-Admin-Backend

Technologie-Stack: Vue 3 + TypeScript + Element Plus + ECharts 5 + Pinia + Axios

### Implementierte Seiten

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

- Der Axios-Generic-Typ `UnwrappedInstance` entpackt die `ApiResponse<T>`-Hülle automatisch
- `vue-tsc --noEmit` **null Fehler**

---

## 九、Flutter-App

Responsives Design mit PC-Web-Priorität, 3 Breakpoints mit adaptiver Anpassung.

| Breakpoint | Breite | Layout | Navigation |
|------|------|------|------|
| Mobile | < 600px | Einspaltige Karten | Untere NavigationBar |
| Tablet | 600-1200px | Zweispaltiges Grid | Drawer-Schublade |
| Desktop | > 1200px | Mehrspaltiges Grid + DataTable | Feste SideNav (250px) |

**Arbeitsteilung PC-Client und Admin-Backend**:

- **webman-admin**: Schwergewichtige Verwaltung (Tiefenberichte/Systemkonfiguration/Mandantenverwaltung/Massenoperationen)
- **Flutter Web/PC**: Leichtgewichtiges Betriebs-Panel (Echtzeit-Monitoring/Alert-Bearbeitung/Leichte Werbeschaltung, kein VPN nötig)

---

## 十、HarmonyOS-App

Technologie-Stack: ArkTS + ArkUI. Funktionen sind auf die Flutter-App abgestimmt.

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

## 十一、API-Design

Präfix `/api/v1`, einheitliches Antwortformat:

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

### Alle Endpunkte

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

## 十二、Geschäftsablaufdiagramme

### Admin ↔ Service-Kommunikation

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

### OAuth-Plattform-Autorisierungsablauf

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

### Datensynchronisierung & Alarmablauf

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

### Adapter-Muster


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

## 十三、Datensynchronisierung & Aufgabenplanung

Verwendet webman/crontab, beschleunigt durch Redis-Cache.

| Aufgabe | Häufigkeit | Beschreibung |
|------|------|------|
| TokenRefreshTask | Alle 55 Minuten | Abgelaufene Token scannen und automatisch erneuern |
| DataSyncTask | Alle 10 Minuten | Pläne aller Plattformen + Berichte der letzten 2 Tage abrufen, danach Dashboard-Cache leeren |
| AlertCheckTask | Alle 5 Minuten | Aktivierte Regeln durchlaufen, Schwellenwerte auswerten, Push auslösen |
| RetrySyncTask | Alle 3 Minuten | Fehlgeschlagene Synchronisierungen wiederholen (erik_sync_errors-Tabelle, max. 3 Versuche, exponentielle Backoff) |

Synchronisierungsstrategie: Generator-Streaming der Adapter, Cursor/Paginierung gegen Lücken, automatische Wiederholung bei Fehlern, curl_errno-Prüfung, plattformweite QPS-Begrenzung.

---

## 十四、Bereitstellungsarchitektur

### Containerisierte Bereitstellung

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

### Produktions-Bereitstellungsablauf

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

### Docker-Ein-Klick-Bereitstellung

```bash
docker-compose up -d          # MySQL + Redis + PHP + Nginx
make db-init                  # 初始化数据库
make admin-dev                # 前端开发模式
```

---

## 十五、Implementierungshistorie

| Phase | Inhalt | Status |
|------|------|------|
| Phase 1 | webman v2 + Admin-Backend-Skelett + Multi-Tenant + OAuth + Juliang | ✅ |
| Phase 2 | Baidu-Adapter + Taobao-Adapter + Datensynchronisierung + Report-Engine | ✅ |
| Phase 3 | Tencent + Umeng + Kuaishou + Xiaohongshu (4 neue) | ✅ |
| Phase 4 | Weibo + Bilibili + Youku + Meituan + Zhihu + 360 + Sogou + Jingdong + Pinduoduo (9 neue Inland) | ✅ |
| Phase 4 | Meta + LinkedIn + Snapchat + Pinterest + Twitter + Amazon + TTD + Spotify + Twitch + Netflix + Google + YouTube + TikTok (13 neue International) | ✅ |
| Phase 5 | Alarmsystem + Berichtsexport + Flutter-App + HarmonyOS-App + Dashboard-Erweiterungen | ✅ |
| Phase 6 | Erik Stack-Integration (snowflake/hashids/jwt-webman/encryption/encryptable/scout/season) | ✅ |
| Phase 7 | Docker-Bereitstellung + Sicherheitshärtung (RateLimit/CORS/SQLGuard) + Cache-Schicht + README | ✅ |
| Phase 8 | Verzeichnisreorganisation (apps/) + eigenständiges Admin webman-admin v2 (PHP-Backend + ServiceProxy) + RBAC + Audit-Protokoll | ✅ |
| Phase 9 | API-Dokumentation + Plattform-Ratenbegrenzung + Synchronisierungs-Wiederholungswarteschlange + PHPUnit 20 Tests + GitHub Actions CI/CD | ✅ |
| Phase 10 | Chinesische Kommentare in Konfigurationsdateien + .env-Kommentare + Plattform-Credential-Dokumentation + erik_-Tabellenpräfix-Umschreibung + BIGINT-PK | ✅ |
| Phase 11 | Internationalisierung (vue-i18n + I18n.php + Flutter + HarmonyOS) + Slider-Captcha (poster-php) | ✅ |
| Phase 12 | Zweitbestätigung (Eingabe zur Bestätigung) — Entbinden/Löschen/Massenoperationen erfordern die Eingabe des Zielnamens | ✅ |

---

## 十六、Admin-Backend-Architektur

### PHP-Backend (Port 8789)

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

### Rollen & Berechtigungen (RBAC)

| Rolle | slug | Berechtigungen |
|------|------|------|
| Super-Administrator | super_admin | `*` alle Berechtigungen |
| Betriebsleiter | ops_manager | dashboard, campaigns, reports, alerts, accounts |
| Datenanalyst | analyst | dashboard, reports |

### Admin- und Service-Kommunikation

Admin ruft die Service-API über `ServiceProxy` (cURL) auf und leitet das JWT-Token weiter. Admin übernimmt selbst Authentifizierung und Benutzerverwaltung; die Geschäftsdaten liefert vollständig der Service.

---

## 十七、Tests & CI/CD

### PHPUnit-Testsuite

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

Aktualisiert wöchentlich automatisch die Composer-, npm- und Docker-Abhängigkeiten.
