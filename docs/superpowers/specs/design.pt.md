# Design do sistema de gerenciamento de publicidade multiplataforma

[中文](docs/superpowers/specs/design.md) | [English](docs/superpowers/specs/design.en.md) | [한국어](docs/superpowers/specs/design.ko.md) | [Русский](docs/superpowers/specs/design.ru.md) | [Deutsch](docs/superpowers/specs/design.de.md) | [Français](docs/superpowers/specs/design.fr.md) | [Español](docs/superpowers/specs/design.es.md) | [Português](docs/superpowers/specs/design.pt.md) | [हिन्दी](docs/superpowers/specs/design.hi.md) | [العربية](docs/superpowers/specs/design.ar.md) | [বাংলা](docs/superpowers/specs/design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/design.id.md) | [日本語](docs/superpowers/specs/design.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## Visão geral

Plataforma unificada de gerenciamento de publicidade integrando **29 plataformas de anúncios**, cobrindo os principais fornecedores de publicidade nacionais e internacionais, com suporte a gerenciamento de veiculação, relatórios de dados entre plataformas e monitoramento de alertas em tempo real.

- **service** — serviço de negócio do usuário, webman v2 (PHP 8.2+), escutando em :8788
- **admin** — painel administrativo independente, webman-admin v2 (backend PHP :8789 + Vue 3 SPA)
- **apps** — apps clientes, Flutter (iOS/Android/Web PC) + HarmonyOS (ArkTS)
- **Infraestrutura**: Docker + Nginx + MySQL 8.0 + Redis 7 + Elasticsearch

Os cenários de negócio cobrem três modos: veiculação para uso próprio, SaaS multitenant e operação terceirizada.

### Arquitetura de comunicação

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

## Arquitetura geral

### Diagrama de arquitetura do sistema

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

## 1. Decomposição dos módulos do backend

Arquitetura de plugins do webman v2, com 7 plugins em `service/plugin/`:

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

## 2. Adaptadores de plataforma

### Plataformas adaptadas (29)

| Região | # | Plataforma | Classe do adaptador | Autenticação | Valor | Relatórios |
|------|---|------|---------|------|------|------|
| Nacionais | 1 | Juliang (Ocean Engine) | Juliang | OAuth2 Access-Token | yuan→centavos | Paginação síncrona |
| Nacionais | 2 | Baidu Marketing | Baidu | OAuth2 + assinatura de envelope | yuan→centavos | Polling assíncrono |
| Nacionais | 3 | Taobao/Alimama | Taobao | OAuth2 + assinatura MD5 | yuan→centavos | Paginação síncrona |
| Nacionais | 4 | Tencent Ads | Tencent | OAuth2 + nonce | centavos nativos | Paginação síncrona |
| Nacionais | 5 | Kuaishou Magnet Engine | Kuaishou | OAuth2 parâmetro de URL | yuan→centavos | Paginação síncrona |
| Nacionais | 6 | Xiaohongshu Dandelion | Xiaohongshu | OAuth2 Bearer | centavos nativos | Paginação síncrona |
| Nacionais | 7 | Weibo Fans Tong | Weibo | OAuth2 Bearer | centavos nativos | Paginação síncrona |
| Nacionais | 8 | Bilibili Huahuo | Bilibili | OAuth2 Bearer | centavos nativos | Paginação síncrona |
| Nacionais | 9 | Youku Ads | Youku | OAuth2 + assinatura MD5 | yuan→centavos | Paginação síncrona |
| Nacionais | 10 | Meituan Ads | Meituan | OAuth2 Bearer | centavos nativos | Paginação síncrona |
| Nacionais | 11 | Zhihu Ads | Zhihu | OAuth2 Bearer | yuan→centavos | Paginação síncrona |
| Nacionais | 12 | Qihoo 360 Promo | Qihoo360 | API Key + Sign | yuan→centavos | Paginação síncrona |
| Nacionais | 13 | Sogou Promo | Sogou | API Key + Sign | yuan→centavos | Paginação síncrona |
| Nacionais | 14 | Umeng | Umeng | API Key + MD5 | yuan→centavos | Paginação síncrona |
| Nacionais | 15 | Jingdong Jingzhuntong | Jingdong | OAuth2 + MD5 | yuan→centavos | Paginação síncrona |
| Nacionais | 16 | Pinduoduo Ads | Pinduoduo | OAuth2 + Sign personalizado | centavos nativos | Paginação síncrona |
| Internacionais | 17 | Google Ads | Google | OAuth2 + GAQL | microyuan→centavos | pageToken |
| Internacionais | 18 | YouTube Ads | Youtube | OAuth2 + GAQL | microyuan→centavos | pageToken |
| Internacionais | 19 | Meta Ads | Meta | OAuth2 parâmetro de URL | centavos nativos | Assíncrono |
| Internacionais | 20 | TikTok Ads | Tiktok | OAuth2 Access-Token | microyuan→centavos | Paginação síncrona |
| Internacionais | 21 | LinkedIn Ads | Linkedin | OAuth2 Bearer | microyuan→centavos | Paginação síncrona |
| Internacionais | 22 | Snapchat Ads | Snapchat | OAuth2 Bearer | microyuan→centavos | Paginação síncrona |
| Internacionais | 23 | Pinterest Ads | Pinterest | OAuth2 Bearer | microyuan→centavos | Paginação síncrona |
| Internacionais | 24 | Twitter/X Ads | Twitter | OAuth2 Bearer | microyuan→centavos | Paginação síncrona |
| Internacionais | 25 | Amazon Ads | Amazon | OAuth2 + Profile | centavos nativos | Assíncrono |
| Internacionais | 26 | The Trade Desk | TheTradeDesk | HMAC-SHA256 | centavos nativos | Assíncrono |
| Internacionais | 27 | Spotify Ads | Spotify | OAuth2 Bearer | centavos nativos | Assíncrono |
| Internacionais | 28 | Twitch Ads | Twitch | OAuth2 Bearer+ClientId | centavos nativos | Síncrono |
| Internacionais | 29 | Netflix Ads | Netflix | OAuth2 client_credentials | centavos nativos | Síncrono |

### Definição das interfaces

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

### Mapeamento de campos

Cada adaptador converte os campos brutos da plataforma para o modelo unificado via `FieldMapping`; os campos específicos da plataforma caem automaticamente no JSON `extra`. Os valores são unificados em **centavos** (yuan) / **centavos-cent** (dólar).

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

## 3. Design do banco de dados

### Convenção de nomenclatura
- Prefixo de tabela: `erik_`
- Chave primária: `BIGINT UNSIGNED PRIMARY KEY` (sem autoincremento, geração por Snowflake ID)
- Engine: InnoDB, charset: utf8mb4

### Tabelas principais (13)

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

## 4. Integração Erik Stack

| Pacote | Finalidade | Local de integração |
|----|------|---------|
| `erikwang2013/snowflake-php` | IDs de chave primária distribuídos | SnowflakeTrait → eventos creating de todos os Models |
| `erikwang2013/hashids` | Criptografia de IDs em requisições/respostas da API | ApiResponse codifica automaticamente os campos id/*_id |
| `erikwang2013/jwt-webman` | Token de autenticação JWT | AuthMiddleware + AuthController |
| `erikwang2013/encryption` | Criptografia de dados sensíveis na camada de API | EncryptionMiddleware (header X-Encrypted) |
| `erikwang2013/encryptable` | Criptografia automática de campos do DB | PlatformAccount/AuthToken $encryptable |
| `erikwang2013/webman-scout` | Sincronização de dados Elasticsearch | config/scout.php |
| `erikwang2013/season` | Bandeiras de países | PlatformBadge.vue (bandeiras Unicode) |
| `erikwang2013/poster-php` | Captcha deslizante | CaptchaService + CaptchaWidget |

---

## 5. Internacionalização (i18n)

Todas as interfaces suportam **Chinês (zh-CN)** e **Inglês (en)**:

| Plataforma | Tecnologia | Volume de tradução |
|----|------|--------|
| Admin | vue-i18n v9 | 158 keys (app/nav/login/dashboard/campaign/account/report/alert/system/common) |
| Service API | `erik\support\I18n` | 12 chaves de mensagem (header Accept-Language / parâmetro ?lang=) |
| Flutter | AppLocalizations + Delegate | 20+ UI keys |
| HarmonyOS | StringResources | 15+ UI keys |

---

## 6. Captcha

Operações sensíveis como login exigem a conclusão do captcha deslizante (erikwang2013/poster-php):

```
GET  /api/v1/captcha/generate  → 返回背景图 + 拼图块 + AES 加密 token
POST /api/v1/captcha/verify    → 验证偏移量（5px 容差，5 分钟有效）
```

O componente `CaptchaWidget` do frontend suporta arrastar/toque e atualiza automaticamente em caso de falha. O AuthController do backend valida captcha_token + captcha_offset no login.

### Confirmação secundária

Operações sensíveis como exclusão, desvinculação e operações em lote usam o modo "digitar para confirmar":

| Operação | Forma de confirmação | Palavra de confirmação |
|------|---------|--------|
| Desvincular conta | Digitar o nome da conta | Nome da conta |
| Iniciar/pausar planos em lote | Digitar a palavra fixa de confirmação | `ENABLE` / `PAUSE` |
| Excluir regra de alerta | Digitar o nome da regra | Nome da regra |
| Desabilitar/habilitar usuário | Digitar o nome de usuário | Nome de usuário |

Conduzido pelo componente `GlobalConfirm` genérico + a Pinia store `useConfirmStore`; para adicionar uma nova operação sensível basta chamar `confirmStore.show({...})`.

---

## 7. Pilha de middlewares de segurança (8 camadas no total)

### Fluxo de requisições

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

| Middleware | Função |
|--------|------|
| CorsMiddleware | Tratamento de requisições entre domínios, suporta X-Tenant-Id/X-Encrypted |
| RateLimitMiddleware | Rate limit com janela deslizante no Redis, padrão 60 req/60s |
| SqlGuardMiddleware | Detecção de padrões de injeção de SQL (UNION/DROP/ALTER/comentários) |
| ValidationMiddleware | Corte de entrada + filtragem de tags HTML |
| EncryptionMiddleware | Descriptografia de requisição + criptografia de resposta (header X-Encrypted) |
| AuthMiddleware | Validação do JWT Bearer Token (erikwang2013/jwt-webman) |
| TenantIdentify | Resolução multitenant (header X-Tenant-Id / Session) |

---

## 8. Painel administrativo Web

Stack de tecnologia: Vue 3 + TypeScript + Element Plus + ECharts 5 + Pinia + Axios

### Páginas implementadas

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

- O tipo genérico `UnwrappedInstance` do Axios desembrulha automaticamente o encapsulamento de `ApiResponse<T>`
- `vue-tsc --noEmit` com **zero erros**

---

## 9. App Flutter

Design responsivo priorizando PC Web, adaptativo em 3 breakpoints.

| Breakpoint | Largura | Layout | Navegação |
|------|------|------|------|
| Mobile | < 600px | Cartões em coluna única | NavigationBar inferior |
| Tablet | 600-1200px | Grade de duas colunas | Gaveta Drawer |
| Desktop | > 1200px | Grade de várias colunas + DataTable | SideNav fixo (250px) |

**Divisão de trabalho entre o PC e o painel administrativo**:

- **webman-admin**: administração pesada (relatórios aprofundados/configuração do sistema/gerenciamento de tenants/operações em lote)
- **Flutter Web/PC**: painel operacional leve (acompanhamento em tempo real/tratamento de alertas/veiculação leve, sem necessidade de VPN)

---

## 10. App HarmonyOS

Stack de tecnologia: ArkTS + ArkUI. Funcionalidades alinhadas com o App Flutter.

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

## 11. Design da API

Prefixo `/api/v1`, formato de resposta unificado:

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

### Todos os endpoints

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

## 12. Diagramas de lógica de negócio

### Comunicação Admin ↔ Service

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

### Fluxo de autorização da plataforma OAuth

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

### Fluxo de sincronização de dados & alertas

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

### Padrão de adaptador


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

## 13. Sincronização de dados & agendamento de tarefas

Usa webman/crontab, com aceleração por cache Redis.

| Tarefa | Frequência | Descrição |
|------|------|------|
| TokenRefreshTask | A cada 55 minutos | Escaneia Tokens expirados e atualiza automaticamente |
| DataSyncTask | A cada 10 minutos | Busca os planos de cada plataforma + relatórios dos últimos 2 dias e limpa o cache do dashboard após a sincronização |
| AlertCheckTask | A cada 5 minutos | Percorre as regras habilitadas, avalia limites e dispara notificações |
| RetrySyncTask | A cada 3 minutos | Repete sincronizações com falha (tabela erik_sync_errors, máximo de 3 vezes, backoff exponencial) |

Estratégia de sincronização: processamento em fluxo com Generator do adaptador, prevenção de perdas com cursor/paginação, nova tentativa automática em caso de falha, verificação de curl_errno e rate limit de QPS por plataforma.

---

## 14. Arquitetura de implantação

### Implantação em contêineres

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

### Fluxo de implantação em produção

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

### Implantação Docker com um clique

```bash
docker-compose up -d          # MySQL + Redis + PHP + Nginx
make db-init                  # 初始化数据库
make admin-dev                # 前端开发模式
```

---

## 15. Histórico de implementação

| Fase | Conteúdo | Status |
|------|------|------|
| Phase 1 | webman v2 + esqueleto do painel administrativo + multitenant + OAuth + Juliang | ✅ |
| Phase 2 | Adaptador Baidu + adaptador Taobao + sincronização de dados + engine de relatórios | ✅ |
| Phase 3 | Tencent + Umeng + Kuaishou + Xiaohongshu (4 novos) | ✅ |
| Phase 4 | Weibo + Bilibili + Youku + Meituan + Zhihu + 360 + Sogou + Jingdong + Pinduoduo (9 novos nacionais) | ✅ |
| Phase 4 | Meta + LinkedIn + Snapchat + Pinterest + Twitter + Amazon + TTD + Spotify + Twitch + Netflix + Google + YouTube + TikTok (13 novos internacionais) | ✅ |
| Phase 5 | Sistema de alertas + exportação de relatórios + App Flutter + App HarmonyOS + melhorias no dashboard | ✅ |
| Phase 6 | Integração Erik Stack (snowflake/hashids/jwt-webman/encryption/encryptable/scout/season) | ✅ |
| Phase 7 | Implantação Docker + reforço de segurança (RateLimit/CORS/SQLGuard) + camada de cache + README | ✅ |
| Phase 8 | Reorganização de diretórios (apps/) + Admin independente webman-admin v2 (backend PHP + ServiceProxy) + RBAC + logs de auditoria | ✅ |
| Phase 9 | Documentação da API + rate limit por plataforma + fila de nova tentativa de sincronização + 20 testes PHPUnit + GitHub Actions CI/CD | ✅ |
| Phase 10 | Comentários em chinês nos arquivos de configuração + comentários no .env + documentação de credenciais das plataformas + reescrita do prefixo erik_ nas tabelas + BIGINT PK | ✅ |
| Phase 11 | Internacionalização (vue-i18n + I18n.php + Flutter + HarmonyOS) + captcha deslizante (poster-php) | ✅ |
| Phase 12 | Confirmação secundária (digitar para confirmar) — desvincular/excluir/operações em lote exigem digitar o nome do alvo para executar | ✅ |

---

## 16. Arquitetura do painel administrativo Admin

### Backend PHP (porta 8789)

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

### Papéis e permissões (RBAC)

| Papel | slug | Permissões |
|------|------|------|
| Super administrador | super_admin | `*` todas as permissões |
| Gerente de operações | ops_manager | dashboard, campaigns, reports, alerts, accounts |
| Analista de dados | analyst | dashboard, reports |

### Comunicação entre Admin e Service

O Admin chama a API do service via `ServiceProxy` (cURL), encaminhando o JWT Token. O Admin é responsável pela autenticação e gerenciamento de usuários; os dados de negócio são fornecidos integralmente pelo service.

---

## 17. Testes & CI/CD

### Suíte de testes PHPUnit

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

As dependências Composer + npm + Docker são atualizadas automaticamente toda semana.

