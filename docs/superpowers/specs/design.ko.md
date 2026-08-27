# 다중 플랫폼 광고 관리 시스템 설계

[中文](docs/superpowers/specs/design.md) | [English](docs/superpowers/specs/design.en.md) | [한국어](docs/superpowers/specs/design.ko.md) | [Русский](docs/superpowers/specs/design.ru.md) | [Deutsch](docs/superpowers/specs/design.de.md) | [Français](docs/superpowers/specs/design.fr.md) | [Español](docs/superpowers/specs/design.es.md) | [Português](docs/superpowers/specs/design.pt.md) | [हिन्दी](docs/superpowers/specs/design.hi.md) | [العربية](docs/superpowers/specs/design.ar.md) | [বাংলা](docs/superpowers/specs/design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/design.id.md) | [日本語](docs/superpowers/specs/design.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## 개요

**29개 광고 플랫폼**을 연동하는 통합 광고 관리 플랫폼으로, 국내외 주요 광고 사업자를 포괄하며 광고 집행 관리, 플랫폼 간 데이터 보고서, 실시간 경보 모니터링을 지원합니다.

- **service** — 사용자 측 비즈니스 서비스, webman v2 (PHP 8.2+), :8788 리슨
- **admin** — 독립 관리 백엔드, webman-admin v2 (PHP 백엔드 :8789 + Vue 3 SPA)
- **apps** — 클라이언트 App, Flutter (iOS/Android/Web PC) + HarmonyOS (ArkTS)
- **인프라**: Docker + Nginx + MySQL 8.0 + Redis 7 + Elasticsearch

비즈니스 시나리오는 자체 집행, SaaS 멀티 테넌트, 대행 운영 3가지 모드를 포괄합니다.

### 통신 아키텍처

```
admin:8789 (관리 백엔드)          service:8788 (비즈니스 API)
┌─────────────────┐    HTTP    ┌──────────────────┐
│ webman-admin v2 │ ────────→  │ webman v2 API    │
│ PHP后端+Vue SPA │ ServiceProxy│ 7插件・29适配器   │
└────────┬────────┘            └────────┬─────────┘
         │                              │
     관리 작업                      비즈니스 데이터
  (사용자/RBAC/감사)           (광고/보고서/경보/동기화)
```

---

## 전체 아키텍처

### 시스템 아키텍처 다이어그램

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
               외부 광고 플랫폼 APIs
```

---

## 1. 서버 측 모듈 분해

webman v2 플러그인 아키텍처, `service/plugin/` 아래 7개 플러그인:

```
service/
├── config/                     # 설정 (주석 포함)
│   ├── app.php, database.php, redis.php
│   ├── middleware.php, server.php
│   ├── log.php, container.php, scout.php
├── support/                    # Erik Stack 유틸리티 클래스
│   ├── ApiResponse.php         # 통일 JSON 응답 (hashids ID 인코딩 포함)
│   ├── SnowflakeTrait.php      # 분산 ID 생성
│   ├── HashidsService.php      # API ID 암복호화
│   ├── CacheService.php        # Redis 캐시 계층
│   └── QueryOptimizer.php      # SQL 최적화기
├── plugin/
│   ├── ads-tenant/             # 멀티 테넌트 관리
│   │   ├── model/Tenant.php
│   │   ├── middleware/TenantIdentify.php
│   │   └── migration/create_tenants.sql
│   │
│   ├── ads-account/            # 광고 계정 & OAuth 인증 (encryptable 암호화 포함)
│   │   ├── model/PlatformAccount.php, AuthToken.php
│   │   ├── service/OAuthService.php
│   │   └── migration/create_platform_accounts.sql
│   │
│   ├── ads-platform/           # 플랫폼 어댑터 코어
│   │   ├── src/PlatformAdapter.php, AdapterRegistry.php, FieldMapping.php
│   │   ├── src/CampaignData.php, ReportRequest.php
│   │   ├── adapter/            # 29개 어댑터 (국내16 + 국제13)
│   │   └── migration/create_campaign_tables.sql
│   │
│   ├── ads-api/                # RESTful API (25+ 엔드포인트)
│   │   ├── controller/         # 7개 컨트롤러
│   │   ├── middleware/          # 7개 미들웨어
│   │   └── config/route.php
│   │
│   ├── ads-task/               # 예약 작업 스케줄링
│   │   ├── task/DataSyncTask.php, TokenRefreshTask.php, AlertCheckTask.php
│   │   └── config/cron.php
│   │
│   ├── ads-report/             # 보고서 엔진 & 내보내기
│   │   ├── service/ReportBuilder.php, ReportExporter.php, PdfExporter.php
│   │   └── config/plugin.php
│   │
│   └── ads-alert/              # 경보 모니터링
│       ├── model/AlertRule.php, AlertLog.php
│       ├── service/AlertEngine.php, NotificationService.php
│       └── migration/create_alerts.sql
```

---

## 2. 플랫폼 어댑터

### 적용 완료 플랫폼 (29)

| 지역 | # | 플랫폼 | 어댑터 클래스 | 인증 | 금액 | 보고서 |
|------|---|------|---------|------|------|------|
| 국내 | 1 | 巨量引擎 | Juliang | OAuth2 Access-Token | 元→分 | 동기 페이징 |
| 국내 | 2 | 百度营销 | Baidu | OAuth2 + 信封签名 | 元→分 | 비동기 폴링 |
| 국내 | 3 | 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5签名 | 元→分 | 동기 페이징 |
| 국내 | 4 | 腾讯广告 | Tencent | OAuth2 + nonce | 分原生 | 동기 페이징 |
| 국내 | 5 | 快手磁力引擎 | Kuaishou | OAuth2 URL参数 | 元→分 | 동기 페이징 |
| 국내 | 6 | 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer | 分原生 | 동기 페이징 |
| 국내 | 7 | 微博粉丝通 | Weibo | OAuth2 Bearer | 分原生 | 동기 페이징 |
| 국내 | 8 | B站花火 | Bilibili | OAuth2 Bearer | 分原生 | 동기 페이징 |
| 국내 | 9 | 优酷广告 | Youku | OAuth2 + MD5签名 | 元→分 | 동기 페이징 |
| 국내 | 10 | 美团广告 | Meituan | OAuth2 Bearer | 分原生 | 동기 페이징 |
| 국내 | 11 | 知乎广告 | Zhihu | OAuth2 Bearer | 元→分 | 동기 페이징 |
| 국내 | 12 | 360推广 | Qihoo360 | API Key + Sign | 元→分 | 동기 페이징 |
| 국내 | 13 | 搜狗推广 | Sogou | API Key + Sign | 元→分 | 동기 페이징 |
| 국내 | 14 | 友盟 | Umeng | API Key + MD5 | 元→分 | 동기 페이징 |
| 국내 | 15 | 京东京准通 | Jingdong | OAuth2 + MD5 | 元→分 | 동기 페이징 |
| 국내 | 16 | 拼多多广告 | Pinduoduo | OAuth2 + 自定义Sign | 分原生 | 동기 페이징 |
| 국제 | 17 | Google Ads | Google | OAuth2 + GAQL | 微元→分 | pageToken |
| 국제 | 18 | YouTube Ads | Youtube | OAuth2 + GAQL | 微元→分 | pageToken |
| 국제 | 19 | Meta Ads | Meta | OAuth2 URL参数 | 分原生 | 비동기 |
| 국제 | 20 | TikTok Ads | Tiktok | OAuth2 Access-Token | 微元→分 | 동기 페이징 |
| 국제 | 21 | LinkedIn Ads | Linkedin | OAuth2 Bearer | 微元→分 | 동기 페이징 |
| 국제 | 22 | Snapchat Ads | Snapchat | OAuth2 Bearer | 微元→分 | 동기 페이징 |
| 국제 | 23 | Pinterest Ads | Pinterest | OAuth2 Bearer | 微元→分 | 동기 페이징 |
| 국제 | 24 | Twitter/X Ads | Twitter | OAuth2 Bearer | 微元→分 | 동기 페이징 |
| 국제 | 25 | Amazon Ads | Amazon | OAuth2 + Profile | 分原生 | 비동기 |
| 국제 | 26 | The Trade Desk | TheTradeDesk | HMAC-SHA256 | 分原生 | 비동기 |
| 국제 | 27 | Spotify Ads | Spotify | OAuth2 Bearer | 分原生 | 비동기 |
| 국제 | 28 | Twitch Ads | Twitch | OAuth2 Bearer+ClientId | 分原生 | 동기 |
| 국제 | 29 | Netflix Ads | Netflix | OAuth2 client_credentials | 分原生 | 동기 |

### 인터페이스 정의

```php
interface PlatformAdapter
{
    public function code(): string;
    public function name(): string;
    public function capabilities(): array;

    // 인증
    public function buildAuthUrl(string $redirectUri, string $state): string;
    public function exchangeToken(string $code, string $redirectUri): array;
    public function refreshToken(string $refreshToken): array;
    public function fetchAccountInfo(string $accessToken): array;

    // 데이터 동기화 (Generator 스트리밍)
    public function fetchCampaigns(string $accessToken, string $accountId): Generator;
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): Generator;
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): Generator;
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): Generator;

    // 집행 작업
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string;
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void;
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void;
}
```

### 필드 매핑

각 어댑터는 `FieldMapping`으로 플랫폼 원시 필드를 통일 모델로 변환하며, 플랫폼 고유 필드는 자동으로 `extra` JSON에 저장됩니다. 금액은 **분(分)**(위안)/**분-cent**(달러)으로 통일됩니다.

```php
// 巨量引擎: 元→分, 백분율→소수
protected array $fieldMap = [
    'campaign_id' => 'platform_campaign_id',
    'stat_cost'   => 'cost',         // 元 → ×100 → 分
    'show_cnt'    => 'impressions',
    'click_cnt'   => 'clicks',
    'ctr'         => 'ctr',          // 백분율 → ÷100 → 소수
];

// Google Ads: 微元→分
protected array $fieldMap = [
    'campaign.id'                => 'platform_campaign_id',
    'metrics.cost_micros'        => 'cost',         // 微元 → ÷10000 → 分
    'metrics.impressions'        => 'impressions',
    'metrics.clicks'             => 'clicks',
];
```

---

## 3. 데이터베이스 설계

### 네이밍 규칙
- 테이블 접두사: `ads_`
- 기본 키: `BIGINT UNSIGNED PRIMARY KEY` (자동 증가 없음, Snowflake ID 생성)
- 엔진: InnoDB, 문자셋: utf8mb4

### 핵심 테이블 (13장)

```sql
-- 테넌트
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

-- 플랫폼 계정 (access_token/refresh_token은 encryptable이 자동 암복호화)
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

-- OAuth 상태 Token
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

-- 통일 광고 캠페인
CREATE TABLE ads_campaigns (
    id BIGINT UNSIGNED PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    platform_account_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(32) NOT NULL,
    platform_campaign_id VARCHAR(128) NOT NULL,
    name VARCHAR(255) NOT NULL,
    daily_budget BIGINT DEFAULT 0,       -- 단위: 分
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

-- 통일 광고 그룹
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

-- 통일 소재
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

-- 보고서 핵심 지표
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
    cost BIGINT DEFAULT 0,              -- 소진액, 단위: 分
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

-- 보고서 확장 데이터
CREATE TABLE ads_report_extras (
    id BIGINT UNSIGNED PRIMARY KEY,
    report_metric_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(32) NOT NULL,
    extra JSON,
    FOREIGN KEY (report_metric_id) REFERENCES ads_report_metrics(id) ON DELETE CASCADE
);

-- 경보 규칙
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

-- 경보 기록
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

## 4. Erik Stack 통합

| 패키지 | 용도 | 통합 위치 |
|----|------|---------|
| `erikwang2013/snowflake-php` | 분산 기본 키 ID | SnowflakeTrait → 모든 Model creating 이벤트 |
| `erikwang2013/hashids` | API 요청/응답 ID 암복호화 | ApiResponse가 id/*_id 필드 자동 인코딩 |
| `erikwang2013/jwt-webman` | JWT 인증 토큰 | AuthMiddleware + AuthController |
| `erikwang2013/encryption` | API 계층 민감 데이터 암복호화 | EncryptionMiddleware (X-Encrypted 헤더) |
| `erikwang2013/encryptable` | DB 필드 자동 암복호화 | PlatformAccount/AuthToken $encryptable |
| `erikwang2013/webman-scout` | Elasticsearch 데이터 동기화 | config/scout.php |
| `erikwang2013/season` | 국가 국기 | PlatformBadge.vue (Unicode 국기) |
| `erikwang2013/poster-php` | 슬라이더 캡차 | CaptchaService + CaptchaWidget |

---

## 5. 국제화 (i18n)

전체 인터페이스가 **중국어 (zh-CN)**와 **English (en)** 지원:

| 단말 | 기술 | 번역량 |
|----|------|--------|
| Admin | vue-i18n v9 | 158 keys (app/nav/login/dashboard/campaign/account/report/alert/system/common) |
| Service API | `erik\support\I18n` | 12 메시지 keys (Accept-Language header / ?lang= param) |
| Flutter | AppLocalizations + Delegate | 20+ UI keys |
| HarmonyOS | StringResources | 15+ UI keys |

---

## 6. 캡차

로그인 등 민감 작업은 슬라이더 캡차(erikwang2013/poster-php) 완료 필요:

```
GET  /api/v1/captcha/generate  → 배경 이미지 + 퍼즐 조각 + AES 암호화 token 반환
POST /api/v1/captcha/verify    → 오프셋 검증 (5px 허용 오차, 5분 유효)
```

프론트엔드 `CaptchaWidget` 컴포넌트가 드래그/터치 지원, 실패 시 자동 새로고침. 백엔드 AuthController가 로그인 시 captcha_token + captcha_offset 검증.

### 이중 확인

삭제, 해제, 일괄 작업 등 민감 작업은 "입력하여 확인" 패턴 사용:

| 작업 | 확인 방식 | 확인 단어 |
|------|---------|--------|
| 계정 해제 | 계정 이름 입력 | 계정 이름 |
| 캠페인 일괄 시작/중지 | 고정 확인 단어 입력 | `ENABLE` / `PAUSE` |
| 경보 규칙 삭제 | 규칙 이름 입력 | 규칙 이름 |
| 사용자 비활성화/활성화 | 사용자 이름 입력 | 사용자 이름 |

공용 `GlobalConfirm` 컴포넌트 + `useConfirmStore` Pinia store로 구동되며, 새 민감 작업은 `confirmStore.show({...})` 호출만 하면 됩니다.

---

## 7. 보안 미들웨어 스택 (총 8계층)

### 요청 흐름

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

| 미들웨어 | 기능 |
|--------|------|
| CorsMiddleware | 크로스 도메인 요청 처리, X-Tenant-Id/X-Encrypted 지원 |
| RateLimitMiddleware | Redis 슬라이딩 윈도우 속도 제한, 기본 60회/60초 |
| SqlGuardMiddleware | SQL 주입 패턴 검출 (UNION/DROP/ALTER/주석) |
| ValidationMiddleware | 입력 정리 + HTML 태그 필터링 |
| EncryptionMiddleware | 요청 복호화 + 응답 암호화 (X-Encrypted 헤더) |
| AuthMiddleware | JWT Bearer Token 검증 (erikwang2013/jwt-webman) |
| TenantIdentify | 멀티 테넌트 해석 (X-Tenant-Id 헤더 / Session) |

---

## 8. Web 관리 백엔드

기술 스택: Vue 3 + TypeScript + Element Plus + ECharts 5 + Pinia + Axios

### 구현된 페이지

```
admin/src/views/
├── login/LoginPage.vue              # 로그인
├── dashboard/DashboardPage.vue      # 대시보드 (KPI 추세/플랫폼 비교/TOP10/날짜 필터/PDF 내보내기)
├── account/
│   ├── AccountList.vue              # 플랫폼 계정 목록 (동기화/해제)
│   └── AccountBind.vue              # OAuth 바인딩 안내 (3단계 마법사)
├── campaign/CampaignList.vue        # 광고 캠페인 (CRUD/일괄 작업/필터/페이징)
├── alert/
│   ├── AlertRuleList.vue            # 경보 규칙 CRUD
│   └── AlertLogList.vue             # 경보 기록 (상태 필터/확인)
├── report/ReportExport.vue          # 보고서 내보내기 (CSV/Excel/PDF)
└── components/
    ├── layout/AppLayout.vue, SideNav.vue, TopBar.vue
    ├── MetricCard.vue               # KPI 지표 카드 (추세 화살표 포함)
    └── PlatformBadge.vue            # 플랫폼 태그 (국기 포함)
```

### TypeScript

- Axios 제네릭 타입 `UnwrappedInstance`가 `ApiResponse<T>` 래퍼 자동 해제
- `vue-tsc --noEmit` **오류 0개**

---

## 9. Flutter App

PC Web 우선의 반응형 설계, 3개 브레이크포인트 적응.

| 브레이크포인트 | 너비 | 레이아웃 | 내비게이션 |
|------|------|------|------|
| Mobile | < 600px | 단일 열 카드 | 하단 NavigationBar |
| Tablet | 600-1200px | 2열 그리드 | Drawer 서랍 |
| Desktop | > 1200px | 다중 열 그리드 + DataTable | 고정 SideNav (250px) |

**PC 측과 관리 백엔드의 역할 분담**:

- **webman-admin**: 중량 관리 (심층 보고서/시스템 설정/테넌트 관리/일괄 작업)
- **Flutter Web/PC**: 경량 운영 패널 (실시간 모니터링/경보 처리/경량 집행, VPN 불필요)

---

## 10. HarmonyOS App

기술 스택: ArkTS + ArkUI. 기능은 Flutter App과 동일.

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

## 11. API 설계

접두사 `/api/v1`, 통일 응답 형식:

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

### 전체 엔드포인트

```
# 인증
POST   /api/v1/auth/login
GET    /api/v1/auth/me

# 플랫폼 & 계정
GET    /api/v1/platforms
GET    /api/v1/accounts
GET    /api/v1/accounts/:id
DELETE /api/v1/accounts/:id
POST   /api/v1/accounts/:id/sync
GET    /api/v1/platforms/:code/oauth-url
POST   /api/v1/platforms/:code/callback

# 광고 캠페인
GET    /api/v1/campaigns
POST   /api/v1/campaigns
GET    /api/v1/campaigns/:id
PUT    /api/v1/campaigns/:id
POST   /api/v1/campaigns/:id/toggle

# 보고서
GET    /api/v1/reports/summary
GET    /api/v1/reports/custom
GET    /api/v1/reports/export
GET    /api/v1/reports/export-dashboard

# 경보
GET    /api/v1/alerts/rules
POST   /api/v1/alerts/rules
PUT    /api/v1/alerts/rules/:id
DELETE /api/v1/alerts/rules/:id
GET    /api/v1/alerts/logs
POST   /api/v1/alerts/logs/:id/acknowledge
GET    /api/v1/alerts/unread-count
```

---

## 12. 비즈니스 로직 다이어그램

### Admin ↔ Service 통신

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

### OAuth 플랫폼 인증 흐름

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

### 데이터 동기화 & 경보 흐름

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

### 어댑터 패턴


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

## 13. 데이터 동기화 & 작업 스케줄링

webman/crontab 사용, Redis 캐시로 가속.

| 작업 | 빈도 | 설명 |
|------|------|------|
| TokenRefreshTask | 55분마다 | 만료 Token 스캔, 자동 갱신 |
| DataSyncTask | 10분마다 | 각 플랫폼 캠페인+최근 2일 보고서 수집, 동기화 후 대시보드 캐시 삭제 |
| AlertCheckTask | 5분마다 | 활성 규칙 순회, 임계값 평가, 푸시 트리거 |
| RetrySyncTask | 3분마다 | 실패 동기화 재시도 (ads_sync_errors 테이블, 최대 3회, 지수 백오프) |

동기화 전략: 어댑터 Generator 스트리밍 처리, 커서/페이징으로 누락 방지, 실패 자동 재시도, curl_errno 검사, 플랫폼별 QPS 속도 제한.

---

## 14. 배포 아키텍처

### 컨테이너화 배포

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

### 프로덕션 배포 흐름

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

### Docker 원클릭 배포

```bash
docker-compose up -d          # MySQL + Redis + PHP + Nginx
make db-init                  # 데이터베이스 초기화
make admin-dev                # 프론트엔드 개발 모드
```

---

## 15. 구현 이력

| 단계 | 내용 | 상태 |
|------|------|------|
| Phase 1 | webman v2 + 관리 백엔드 골격 + 멀티 테넌트 + OAuth + 巨量 | ✅ |
| Phase 2 | 百度 어댑터 + 淘宝 어댑터 + 데이터 동기화 + 보고서 엔진 | ✅ |
| Phase 3 | 腾讯 + 友盟 + 快手 + 小红书 (4신규) | ✅ |
| Phase 4 | 微博 + B站 + 优酷 + 美团 + 知乎 + 360 + 搜狗 + 京东 + 拼多多 (9신규 국내) | ✅ |
| Phase 4 | Meta + LinkedIn + Snapchat + Pinterest + Twitter + Amazon + TTD + Spotify + Twitch + Netflix + Google + YouTube + TikTok (13신규 국제) | ✅ |
| Phase 5 | 경보 시스템 + 보고서 내보내기 + Flutter App + HarmonyOS App + 대시보드 강화 | ✅ |
| Phase 6 | Erik Stack 통합 (snowflake/hashids/jwt-webman/encryption/encryptable/scout/season) | ✅ |
| Phase 7 | Docker 배포 + 보안 강화 (RateLimit/CORS/SQLGuard) + 캐시 계층 + README | ✅ |
| Phase 8 | 디렉터리 재구성 (apps/) + Admin 독립 webman-admin v2 (PHP 백엔드+ServiceProxy) + RBAC + 감사 로그 | ✅ |
| Phase 9 | API 문서 + 플랫폼 속도 제한 + 동기화 재시도 큐 + PHPUnit 20테스트 + GitHub Actions CI/CD | ✅ |
| Phase 10 | 설정 파일 중국어 주석 + .env 주석 + 플랫폼 자격 증명 문서 + ads_ 테이블 접두사 재작성 + BIGINT PK | ✅ |
| Phase 11 | 국제화 (vue-i18n + I18n.php + Flutter + HarmonyOS) + 슬라이더 캡차 (poster-php) | ✅ |
| Phase 12 | 이중 확인 (입력하여 확인) — 해제/삭제/일괄 작업 모두 대상 이름 입력 후 실행 가능 | ✅ |

---

## 16. Admin 관리 백엔드 아키텍처

### PHP 백엔드 (포트 8789)

```
admin/
├── public/web/              # Vue SPA 소스 (개발 모드 Vite :5173)
├── app/
│   ├── controller/
│   │   ├── AuthController.php       # 관리자 로그인 (JWT)
│   │   ├── AdminUserController.php  # 사용자 CRUD (bcrypt 비밀번호)
│   │   ├── AuditLogController.php   # 감사 로그 조회
│   │   └── ServiceProxy.php         # HTTP 프록시 → service:8788
│   ├── middleware/AuthCheck.php     # JWT/Session 이중 인증
│   └── service/AuditService.php     # 작업 감사 기록
├── config/route.php                # Admin API 라우트
└── migration/create_admin_tables.sql # admin_users/roles/audit_logs
```

### 역할 권한 (RBAC)

| 역할 | slug | 권한 |
|------|------|------|
| 슈퍼 관리자 | super_admin | `*` 전체 권한 |
| 운영 매니저 | ops_manager | dashboard, campaigns, reports, alerts, accounts |
| 데이터 분석가 | analyst | dashboard, reports |

### Admin과 Service 통신

Admin은 `ServiceProxy`(cURL)로 service API를 호출하며 JWT Token을 전달합니다. Admin은 인증/권한과 사용자 관리만 담당하고, 비즈니스 데이터는 전적으로 service가 제공합니다.

---

## 17. 테스트 & CI/CD

### PHPUnit 테스트 스위트

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

매주 Composer + npm + Docker 의존성 자동 업데이트.
