# マルチプラットフォーム広告管理システム設計

[中文](docs/superpowers/specs/design.md) | [English](docs/superpowers/specs/design.en.md) | [한국어](docs/superpowers/specs/design.ko.md) | [Русский](docs/superpowers/specs/design.ru.md) | [Deutsch](docs/superpowers/specs/design.de.md) | [Français](docs/superpowers/specs/design.fr.md) | [Español](docs/superpowers/specs/design.es.md) | [Português](docs/superpowers/specs/design.pt.md) | [हिन्दी](docs/superpowers/specs/design.hi.md) | [العربية](docs/superpowers/specs/design.ar.md) | [বাংলা](docs/superpowers/specs/design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/design.id.md) | [日本語](docs/superpowers/specs/design.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## 概要

**29 の広告プラットフォーム**に連携する統一広告管理プラットフォーム。国内外の主要広告ベンダーをカバーし、広告配信管理、クロスプラットフォームのデータレポート、リアルタイムアラート監視をサポートします。

- **service** — ユーザー向け業務サービス、webman v2 (PHP 8.2+)、:8788 で待ち受け
- **admin** — 独立管理バックエンド、webman-admin v2 (PHP バックエンド :8789 + Vue 3 SPA)
- **apps** — クライアント App、Flutter (iOS/Android/Web PC) + HarmonyOS (ArkTS)
- **インフラ**: Docker + Nginx + MySQL 8.0 + Redis 7 + Elasticsearch

業務シーンは自社利用配信、SaaS マルチテナント、代理運用の 3 モードをカバーします。

### 通信アーキテクチャ

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

## 全体アーキテクチャ

### システムアーキテクチャ図

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

## 一、サーバー側モジュール分解

webman v2 プラグインアーキテクチャ、`service/plugin/` の下に 7 プラグイン：

```
service/
├── config/                     # 設定（コメント付き）
│   ├── app.php, database.php, redis.php
│   ├── middleware.php, server.php
│   ├── log.php, container.php, scout.php
├── support/                    # Erik Stack ユーティリティクラス
│   ├── ApiResponse.php         # 統一 JSON レスポンス（hashids ID エンコード含む）
│   ├── SnowflakeTrait.php      # 分散 ID 生成
│   ├── HashidsService.php      # API ID 暗号化/復号
│   ├── CacheService.php        # Redis キャッシュ層
│   └── QueryOptimizer.php      # SQL オプティマイザー
├── plugin/
│   ├── ads-tenant/             # マルチテナント管理
│   │   ├── model/Tenant.php
│   │   ├── middleware/TenantIdentify.php
│   │   └── migration/create_tenants.sql
│   │
│   ├── ads-account/            # 広告アカウント & OAuth 認可（encryptable 暗号化含む）
│   │   ├── model/PlatformAccount.php, AuthToken.php
│   │   ├── service/OAuthService.php
│   │   └── migration/create_platform_accounts.sql
│   │
│   ├── ads-platform/           # プラットフォームアダプターコア
│   │   ├── src/PlatformAdapter.php, AdapterRegistry.php, FieldMapping.php
│   │   ├── src/CampaignData.php, ReportRequest.php
│   │   ├── adapter/            # 29 アダプター（国内16 + 国際13）
│   │   └── migration/create_campaign_tables.sql
│   │
│   ├── ads-api/                # RESTful API（25+ エンドポイント）
│   │   ├── controller/         # 7 コントローラー
│   │   ├── middleware/          # 7 ミドルウェア
│   │   └── config/route.php
│   │
│   ├── ads-task/               # 定期タスクスケジューリング
│   │   ├── task/DataSyncTask.php, TokenRefreshTask.php, AlertCheckTask.php
│   │   └── config/cron.php
│   │
│   ├── ads-report/             # レポートエンジン & エクスポート
│   │   ├── service/ReportBuilder.php, ReportExporter.php, PdfExporter.php
│   │   └── config/plugin.php
│   │
│   └── ads-alert/              # アラート監視
│       ├── model/AlertRule.php, AlertLog.php
│       ├── service/AlertEngine.php, NotificationService.php
│       └── migration/create_alerts.sql
```

---

## 二、プラットフォームアダプター

### 対応済みプラットフォーム (29)

| 地区 | # | プラットフォーム | アダプタークラス | 認証 | 金額 | レポート |
|------|---|------|---------|------|------|------|
| 国内 | 1 | 巨量引擎 | Juliang | OAuth2 Access-Token | 元→分 | 同期ページング |
| 国内 | 2 | 百度营销 | Baidu | OAuth2 + 信封签名 | 元→分 | 非同期ポーリング |
| 国内 | 3 | 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5签名 | 元→分 | 同期ページング |
| 国内 | 4 | 腾讯广告 | Tencent | OAuth2 + nonce | 分ネイティブ | 同期ページング |
| 国内 | 5 | 快手磁力引擎 | Kuaishou | OAuth2 URL参数 | 元→分 | 同期ページング |
| 国内 | 6 | 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer | 分ネイティブ | 同期ページング |
| 国内 | 7 | 微博粉丝通 | Weibo | OAuth2 Bearer | 分ネイティブ | 同期ページング |
| 国内 | 8 | B站花火 | Bilibili | OAuth2 Bearer | 分ネイティブ | 同期ページング |
| 国内 | 9 | 优酷广告 | Youku | OAuth2 + MD5签名 | 元→分 | 同期ページング |
| 国内 | 10 | 美团广告 | Meituan | OAuth2 Bearer | 分ネイティブ | 同期ページング |
| 国内 | 11 | 知乎广告 | Zhihu | OAuth2 Bearer | 元→分 | 同期ページング |
| 国内 | 12 | 360推广 | Qihoo360 | API Key + Sign | 元→分 | 同期ページング |
| 国内 | 13 | 搜狗推广 | Sogou | API Key + Sign | 元→分 | 同期ページング |
| 国内 | 14 | 友盟 | Umeng | API Key + MD5 | 元→分 | 同期ページング |
| 国内 | 15 | 京东京准通 | Jingdong | OAuth2 + MD5 | 元→分 | 同期ページング |
| 国内 | 16 | 拼多多广告 | Pinduoduo | OAuth2 + 自定义Sign | 分ネイティブ | 同期ページング |
| 国際 | 17 | Google Ads | Google | OAuth2 + GAQL | マイクロ元→分 | pageToken |
| 国際 | 18 | YouTube Ads | Youtube | OAuth2 + GAQL | マイクロ元→分 | pageToken |
| 国際 | 19 | Meta Ads | Meta | OAuth2 URL参数 | 分ネイティブ | 非同期 |
| 国際 | 20 | TikTok Ads | Tiktok | OAuth2 Access-Token | マイクロ元→分 | 同期ページング |
| 国際 | 21 | LinkedIn Ads | Linkedin | OAuth2 Bearer | マイクロ元→分 | 同期ページング |
| 国際 | 22 | Snapchat Ads | Snapchat | OAuth2 Bearer | マイクロ元→分 | 同期ページング |
| 国際 | 23 | Pinterest Ads | Pinterest | OAuth2 Bearer | マイクロ元→分 | 同期ページング |
| 国際 | 24 | Twitter/X Ads | Twitter | OAuth2 Bearer | マイクロ元→分 | 同期ページング |
| 国際 | 25 | Amazon Ads | Amazon | OAuth2 + Profile | 分ネイティブ | 非同期 |
| 国際 | 26 | The Trade Desk | TheTradeDesk | HMAC-SHA256 | 分ネイティブ | 非同期 |
| 国際 | 27 | Spotify Ads | Spotify | OAuth2 Bearer | 分ネイティブ | 非同期 |
| 国際 | 28 | Twitch Ads | Twitch | OAuth2 Bearer+ClientId | 分ネイティブ | 同期 |
| 国際 | 29 | Netflix Ads | Netflix | OAuth2 client_credentials | 分ネイティブ | 同期 |

### インターフェース定義

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

### フィールドマッピング

各アダプターは `FieldMapping` を通じてプラットフォームの生フィールドを統一モデルに変換し、プラットフォーム固有フィールドは自動的に `extra` JSON に格納されます。金額は統一して **分**（人民元）/ **分-cent**（米ドル）です。

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

## 三、データベース設計

### 命名規則
- テーブルプレフィックス: `erik_`
- 主キー: `BIGINT UNSIGNED PRIMARY KEY` (オートインクリメントなし、Snowflake ID 生成)
- エンジン: InnoDB、文字セット: utf8mb4

### コアテーブル (13枚)

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

## 四、Erik Stack 統合

| パッケージ | 用途 | 統合位置 |
|----|------|---------|
| `erikwang2013/snowflake-php` | 分散主キー ID | SnowflakeTrait → 全 Model の creating イベント |
| `erikwang2013/hashids` | API リクエスト/レスポンス ID の暗号化/復号 | ApiResponse が id/*_id フィールドを自動エンコード |
| `erikwang2013/jwt-webman` | JWT 認証トークン | AuthMiddleware + AuthController |
| `erikwang2013/encryption` | API 層の機密データ暗号化/復号 | EncryptionMiddleware (X-Encrypted ヘッダー) |
| `erikwang2013/encryptable` | DB フィールド自動暗号化/復号 | PlatformAccount/AuthToken $encryptable |
| `erikwang2013/webman-scout` | Elasticsearch データ同期 | config/scout.php |
| `erikwang2013/season` | 国旗 | PlatformBadge.vue (Unicode 国旗) |
| `erikwang2013/poster-php` | スライダー認証コード | CaptchaService + CaptchaWidget |

---

## 五、国際化 (i18n)

全インターフェースが **中文 (zh-CN)** と **English (en)** に対応：

| 端 | 技術 | 翻訳量 |
|----|------|--------|
| Admin | vue-i18n v9 | 158 keys (app/nav/login/dashboard/campaign/account/report/alert/system/common) |
| Service API | `erik\support\I18n` | 12 メッセージ keys (Accept-Language header / ?lang= param) |
| Flutter | AppLocalizations + Delegate | 20+ UI keys |
| HarmonyOS | StringResources | 15+ UI keys |

---

## 六、認証コード

ログインなどの機密操作ではスライダー認証コード（erikwang2013/poster-php）の完了が必要：

```
GET  /api/v1/captcha/generate  → 背景図 + パズルピース + AES 暗号化 token を返す
POST /api/v1/captcha/verify    → オフセットを検証（5px 許容差、5 分有効）
```

フロントエンドの `CaptchaWidget` コンポーネントはドラッグ/タッチに対応し、失敗時は自動リフレッシュします。バックエンドの AuthController はログイン時に captcha_token + captcha_offset を検証します。

### 再確認

削除、解除、一括操作などの機密操作は「入力による確認」方式を採用：

| 操作 | 確認方法 | 確認語 |
|------|---------|--------|
| アカウント解除 | アカウント名を入力 | アカウント名 |
| プラン一括開始・停止 | 固定確認語を入力 | `ENABLE` / `PAUSE` |
| アラートルール削除 | ルール名を入力 | ルール名 |
| ユーザー無効化/有効化 | ユーザー名を入力 | ユーザー名 |

共通の `GlobalConfirm` コンポーネント + `useConfirmStore` Pinia store で駆動。新しい機密操作は `confirmStore.show({...})` を呼び出すだけで済みます。

---

## 七、セキュリティミドルウェアスタック（全 8 層）

### リクエストフロー

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

| ミドルウェア | 機能 |
|--------|------|
| CorsMiddleware | クロスドメインリクエスト処理、X-Tenant-Id/X-Encrypted 対応 |
| RateLimitMiddleware | Redis スライディングウィンドウ限流、デフォルト 60回/60秒 |
| SqlGuardMiddleware | SQL インジェクションパターン検出（UNION/DROP/ALTER/コメント） |
| ValidationMiddleware | 入力トリム + HTML タグフィルタ |
| EncryptionMiddleware | リクエスト復号 + レスポンス暗号化（X-Encrypted ヘッダー） |
| AuthMiddleware | JWT Bearer Token 検証（erikwang2013/jwt-webman） |
| TenantIdentify | マルチテナント解決（X-Tenant-Id ヘッダー / Session） |

---

## 八、Web 管理バックエンド

技術スタック: Vue 3 + TypeScript + Element Plus + ECharts 5 + Pinia + Axios

### 実装済みページ

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

- Axios ジェネリック型 `UnwrappedInstance` が `ApiResponse<T>` ラッパーを自動アンラップ
- `vue-tsc --noEmit` **エラーゼロ**

---

## 九、Flutter App

PC Web 優先のレスポンシブデザイン、3 ブレークポイントで自動適応。

| ブレークポイント | 幅 | レイアウト | ナビゲーション |
|------|------|------|------|
| Mobile | < 600px | 単一列カード | 下部 NavigationBar |
| Tablet | 600-1200px | 2 列グリッド | Drawer ドロワー |
| Desktop | > 1200px | 多列グリッド + DataTable | 固定 SideNav (250px) |

**PC 端と管理バックエンドの役割分担**:

- **webman-admin**: ヘビーな管理（詳細レポート/システム設定/テナント管理/一括操作）
- **Flutter Web/PC**: ライトな運用パネル（リアルタイム監視/アラート処理/ライトな配信、VPN 不要）

---

## 十、HarmonyOS App

技術スタック: ArkTS + ArkUI。機能は Flutter App に準拠。

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

## 十一、API 設計

プレフィックス `/api/v1`、統一レスポンス形式：

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

### 全エンドポイント

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

## 十二、業務ロジック図

### Admin ↔ Service 通信

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

### OAuth プラットフォーム認可フロー

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

### データ同期 & アラートフロー

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

### アダプターパターン


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

## 十三、データ同期 & タスクスケジューリング

webman/crontab を使用し、Redis キャッシュで高速化。

| タスク | 頻度 | 説明 |
|------|------|------|
| TokenRefreshTask | 55 分ごと | 期限切れ Token をスキャンし自動リフレッシュ |
| DataSyncTask | 10 分ごと | 各プラットフォームのプラン+直近 2 日分のレポートを取得、同期後にダッシュボードキャッシュをクリア |
| AlertCheckTask | 5 分ごと | 有効ルールを走査、しきい値を評価、プッシュをトリガー |
| RetrySyncTask | 3 分ごと | 失敗した同期を再試行（erik_sync_errors テーブル、最大 3 回、指数バックオフ） |

同期戦略：アダプターは Generator ストリーム処理、カーソル/ページングで取りこぼし防止、失敗は自動再試行、curl_errno チェック、プラットフォーム単位の QPS レート制限。

---

## 十四、デプロイアーキテクチャ

### コンテナ化デプロイ

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

### 本番デプロイフロー

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

### Docker ワンクリックデプロイ

```bash
docker-compose up -d          # MySQL + Redis + PHP + Nginx
make db-init                  # 初始化数据库
make admin-dev                # 前端开发模式
```

---

## 十五、実装履歴

| フェーズ | 内容 | ステータス |
|------|------|------|
| Phase 1 | webman v2 + 管理バックエンド骨組み + マルチテナント + OAuth + 巨量 | ✅ |
| Phase 2 | 百度アダプター + 淘宝アダプター + データ同期 + レポートエンジン | ✅ |
| Phase 3 | 腾讯 + 友盟 + 快手 + 小红书 (4新規) | ✅ |
| Phase 4 | 微博 + B站 + 优酷 + 美团 + 知乎 + 360 + 搜狗 + 京东 + 拼多多 (9新規国内) | ✅ |
| Phase 4 | Meta + LinkedIn + Snapchat + Pinterest + Twitter + Amazon + TTD + Spotify + Twitch + Netflix + Google + YouTube + TikTok (13新規国際) | ✅ |
| Phase 5 | アラートシステム + レポートエクスポート + Flutter App + HarmonyOS App + ダッシュボード拡張 | ✅ |
| Phase 6 | Erik Stack 統合（snowflake/hashids/jwt-webman/encryption/encryptable/scout/season）| ✅ |
| Phase 7 | Docker デプロイ + セキュリティ強化 (RateLimit/CORS/SQLGuard) + キャッシュ層 + README | ✅ |
| Phase 8 | ディレクトリ再編 (apps/) + Admin 独立 webman-admin v2 (PHPバックエンド+ServiceProxy) + RBAC + 監査ログ | ✅ |
| Phase 9 | API ドキュメント + プラットフォームレート制限 + 同期リトライキュー + PHPUnit 20テスト + GitHub Actions CI/CD | ✅ |
| Phase 10 | 設定ファイルの中国語コメント + .env コメント + プラットフォーム認証情報ドキュメント + erik_ テーブルプレフィックス書き換え + BIGINT PK | ✅ |
| Phase 11 | 国際化 (vue-i18n + I18n.php + Flutter + HarmonyOS) + スライダー認証コード (poster-php) | ✅ |
| Phase 12 | 再確認（入力による確認）— 解除/削除/一括操作は対象名の入力が必須 | ✅ |

---

## 十六、Admin 管理バックエンドアーキテクチャ

### PHP バックエンド（ポート 8789）

```
admin/
├── public/web/              # Vue SPA ソース（開発モード Vite :5173）
├── app/
│   ├── controller/
│   │   ├── AuthController.php       # 管理者ログイン（JWT）
│   │   ├── AdminUserController.php  # ユーザー CRUD（bcrypt パスワード）
│   │   ├── AuditLogController.php   # 監査ログ照会
│   │   └── ServiceProxy.php         # HTTP プロキシ → service:8788
│   ├── middleware/AuthCheck.php     # JWT/Session 二重認証
│   └── service/AuditService.php     # 操作監査の書き込み
├── config/route.php                # Admin API ルート
└── migration/create_admin_tables.sql # admin_users/roles/audit_logs
```

### ロール権限（RBAC）

| ロール | slug | 権限 |
|------|------|------|
| スーパー管理者 | super_admin | `*` 全権限 |
| 運用マネージャー | ops_manager | dashboard, campaigns, reports, alerts, accounts |
| データアナリスト | analyst | dashboard, reports |

### Admin と Service の通信

Admin は `ServiceProxy`（cURL）を通じて service API を呼び出し、JWT Token を転送します。Admin 自身は認証・認可とユーザー管理を担当し、業務データはすべて service が提供します。

---

## 十七、テスト & CI/CD

### PHPUnit テストスイート

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

毎週 Composer + npm + Docker 依存関係を自動更新。
