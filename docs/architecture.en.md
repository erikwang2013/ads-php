# Architecture Design Document

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. System Overview

Multi-platform ad management system integrating **29 advertising platforms**, covering campaign management, cross-platform reporting, alert monitoring, auto-bidding, and audience targeting. Supports three modes: SaaS multi-tenancy, agency operations, and self-use.

---

## 2. Deployment Architecture

```
                         ┌──────────────────────────┐
                         │  客户端                   │
                         │  Vue Admin / Flutter      │
                         │  HarmonyOS / Browser      │
                         └──────────┬───────────────┘
                                    │ HTTP + JWT
                                    v
                         ┌──────────────────────────┐
                         │   Nginx :80               │
                         │   /          → admin:8789 │
                         │   /api       → service:8788│
                         └──────┬──────────┬────────┘
                                │          │
                   ┌────────────┘          └────────────┐
                   v                                    v
         ┌─────────────────┐                ┌─────────────────┐
         │  Admin :8789     │  ServiceProxy  │  Service :8788  │
         │  webman-admin v2 │───────────────→│  webman v2      │
         │  Vue 3 SPA       │   cURL HTTP    │  7 插件         │
         └────────┬────────┘                └────────┬────────┘
                  │                                   │
                  └──────────────┬────────────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              v                  v                  v
        ┌──────────┐      ┌──────────┐      ┌───────────┐
        │ MySQL 8.0│      │ Redis 7  │      │    ES     │
        │ 18 张表  │      │ 缓存/队列│      │ 搜索索引  │
        └──────────┘      └──────────┘      └───────────┘
```

---

## 3. Request Processing Pipeline

### 3.1 Service (15 middleware layers)

```
Request
  → CorsMiddleware            (CORS 白名单、OPTIONS 预检)
  → OriginGuardMiddleware     (Origin/Referer 校验 + 拦截 TRACE/DEBUG/CONNECT)
  → SecurityHeadersMiddleware (CSP/X-Frame-Options/X-Content-Type-Options/HSTS)
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body 10MiB/Content-Type白名单)
  → ClientPlatformMiddleware  (X-Client-Platform 8端来源识别)
  → ReplayGuardMiddleware     (Nonce+Timestamp 防重放, 非浏览器端强校验)
  → RateLimitMiddleware       (Redis 滑动窗口 60次/60s)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟锁定)
  → SessionLimitMiddleware    (并发会话限制 最大3个活跃Token)
  → SqlGuardMiddleware        (SQL 注入模式检测)
  → ValidationMiddleware      (输入 trim + strip_tags)
  → ResponseTimeMiddleware    (X-Response-Time 头 + 慢请求日志)
  → EncryptionMiddleware      (X-Encrypted 请求解密/响应加密)
  → AuthMiddleware            (JWT Bearer Token + IP/UA 绑定)
  → Controller
```

### 3.2 Admin (6 middleware layers)

```
Request
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body限制/Content-Type)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟)
  → ClientPlatformMiddleware  (X-Client-Platform 来源识别)
  → CsrfMiddleware            (CSRF Token 验证)
  → AuthCheck                 (Session + JWT 双通道)
  → Controller
```

---

## 4. Directory Structure

```
ads-php/
├── service/                               # 业务 API 服务 :8788
│   ├── config/                            # 全局配置
│   │   ├── app.php, database.php, redis.php
│   │   ├── log.php                        # Monolog (JSON/Line 双模式)
│   │   ├── middleware.php                 # 11 层全局中间件
│   │   ├── exception.php                  # API 异常处理器
│   │   └── scout.php                      # ES 配置
│   ├── support/                           # 共享工具类 (erik\support)
│   │   ├── ApiResponse.php                # 统一 JSON 响应
│   │   ├── ControllerTrait.php            # 控制器公共 trait
│   │   ├── JwtService.php                 # JWT 包装 (erikwang2013/jwt-webman)
│   │   ├── CacheService.php               # Redis 缓存
│   │   ├── HashidsService.php             # ID 加解密
│   │   ├── SnowflakeTrait.php             # Snowflake ID 生成
│   │   └── ExceptionHandler.php           # JSON 异常渲染
│   ├── plugin/
│   │   ├── ads-api/                       # REST API 层
│   │   │   ├── controller/v1/             # 14 个控制器
│   │   │   ├── middleware/                # 7 个中间件
│   │   │   ├── config/route.php           # 45+ 路由
│   │   ├── ads-platform/                  # 平台适配器核心
│   │   │   ├── adapter/                   # 29 个平台适配器
│   │   │   ├── src/                       # AdapterRegistry, CampaignData
│   │   │   ├── model/                     # Campaign, BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/                   # BidEngine
│   │   │   └── migration/                # SQL DDL + 性能索引
│   │   ├── ads-account/                   # OAuth 账户 + 平台账户
│   │   ├── ads-task/                      # 5 个 cron 任务
│   │   ├── ads-alert/                     # 告警引擎 + 通知
│   │   ├── ads-report/                    # 报表引擎 (CSV/Excel/PDF)
│   │   ├── ads-tenant/                    # 多租户
│   │   └── ads-storage/                   # Storage abstraction (local/OSS/COS/S3) + CDN providers
│   ├── tests/                             # PHPUnit
│   │   ├── Unit/Middleware/               # 中间件测试
│   │   ├── Unit/Task/                     # 任务测试 (规划)
│   │   └── Integration/                   # 控制器集成测试
│   └── start.php                          # 入口
├── admin/                                 # 管理后台 :8789
│   ├── app/
│   │   ├── controller/                    # Auth, AdminUser, AuditLog
│   │   ├── middleware/                    # AttackGuard, LoginThrottle, ClientPlatform, Csrf, Version, AuthCheck
│   │   ├── service/                       # AuditService, ServiceProxy
│   │   └── support/                       # HashidsService
│   ├── public/web/                        # Vue 3 + TS SPA
│   │   └── src/
│   │       ├── views/                     # 14 页面 (dashboard/campaign/adgroup/creative/report/alert/notification/bid/system)
│   │       ├── api/                       # 9 个 API 客户端
│   │       ├── stores/                    # 4 个 Pinia Store
│   │       └── components/                # ListPageLayout 等共享组件
│   └── config/                            # Admin 配置
├── apps/
│   ├── flutter/                           # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/                  # 12 功能页面 + Shell 布局
│   │       ├── config/menu_config.dart    # 两级菜单 + 面包屑
│   │       ├── router.dart                # GoRouter + ShellRoute + 路由守卫
│   │       ├── stores/auth_provider.dart  # Riverpod Auth
│   │       └── shared/api/api_client.dart # Dio + JWT + 平台检测
│   └── harmonyos/                         # HarmonyOS (API Client 就绪)
├── docker/                                # Nginx 配置 + Dockerfiles
├── .github/workflows/                     # CI (语法→测试→TS→Docker) + CD (构建推送)
└── docs/                                  # 设计文档
```

---

## 5. Data Model

### 5.1 Table Categories

| Category | Tables | Primary Key | Purpose |
|----------|--------|-------------|---------|
| Foundation | `ads_tenants` | BIGINT Snowflake | Multi-tenancy |
| Accounts | `ads_platform_accounts`, `ads_auth_tokens` | BIGINT Snowflake | OAuth platform accounts |
| Delivery hierarchy | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | BIGINT Snowflake | Ad delivery |
| Reporting | `ads_report_metrics`, `ads_report_extras` | BIGINT Snowflake | Unified metrics |
| Alerts | `ads_alert_rules`, `ads_alert_logs` | BIGINT Snowflake | Monitoring alerts |
| Bidding | `ads_bid_rules`, `ads_bid_logs` | BIGINT Snowflake | Auto-bidding |
| Targeting | `ads_targeting_templates` | BIGINT Snowflake | Audience templates |
| Assets | `ads_assets` | BIGINT Snowflake | Creative asset library |
| CDN | `ads_cdn_providers` | BIGINT Snowflake | CDN provider config (field-level encrypted credentials) |
| Notifications | `ads_notifications` | BIGINT Snowflake | In-app notifications |
| Attribution | `ads_conversions`, `ads_attribution_results` | BIGINT Snowflake | Conversion tracking + attribution |
| System | `ads_sync_errors` | BIGINT Snowflake | Sync errors |
| Admin | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + audit |

### 5.2 Naming Conventions

- Table prefix: `ads_`
- Primary key: `BIGINT UNSIGNED PRIMARY KEY` (no auto-increment, Snowflake ID)
- Engine: InnoDB, charset: utf8mb4
- Timestamps: `created_at`, `updated_at` (DATETIME)

---

## 6. Security Architecture

### 6.1 Defense Layers

| Layer | Mechanism | Coverage |
|-------|-----------|----------|
| Transport | Nginx (SSL termination) | All |
| Network | CORS whitelist + Origin check + HSTS | Service |
| Input | AttackGuard (XSS 11 patterns / path traversal 7 patterns / header injection) | Service + Admin |
| Injection | SQLGuard (SQL injection pattern detection) | Service |
| Sanitization | ValidationMiddleware (strip_tags) | Service |
| Authentication | JWT Bearer + bcrypt + IP/UA binding + refresh rotation | Service |
| Authentication | Session + JWT dual channel + CSRF Token | Admin |
| Authorization | RBAC (roles + permission JSON) | Admin |
| Throttling | RateLimit (sliding window) + LoginThrottle (5 failures → 15 min) | Service + Admin |
| Sessions | SessionLimit (max 3 active tokens) + blacklist | Service |
| Encryption | EncryptionMiddleware (transport) + Encryptable (storage) | Service |
| Replay | ReplayGuard (Nonce + Timestamp ±5min, non-browser) | Service + Clients |
| Resilience | CircuitBreaker (per-platform: 5 failures → OPEN → 30s half-open probe) + GuardedAdapter (degradation fast-fail) | Service |
| Audit | Operation trail (IP/UA/platform) | Admin |
| Redaction | Sensitive field masking in logs (password/token/secret → ***) | Service |

### 6.2 Client Platform Identification

Via the `X-Client-Platform` header:

| Value | Source |
|-------|--------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | HarmonyOS App |

---

## 7. API Version Routing

The API version is fixed in the URL path (`/api/v1/...`), and routes are statically bound to `plugin\ads_api\controller\v1\*`; it is not passed in headers. When a new version is added, register a separate route group (e.g. `/api/v2` → `controller\v2\*`).

```
请求: GET /api/v1/campaigns

路由 /api/v1/campaigns
  → controller\v1\CampaignController::index()
```

---

## 8. Scheduled Tasks

| Task | Cron | Function |
|------|------|----------|
| TokenRefreshTask | `55 */1 * * *` | Refresh expired OAuth tokens |
| DataSyncTask | `*/10 * * * *` | Sync Campaigns→AdGroups→Creatives→Reports→clear cache |
| AlertCheckTask | `*/5 * * * *` | Evaluate alert rules, trigger notifications |
| BidCheckTask | `*/10 * * * *` | Evaluate bid rules, execute budget adjustment/toggle |
| RetrySyncTask | `*/3 * * * *` | Retry failed syncs (max 3 retries, exponential backoff) |

---

## 9. Erik Stack Package Integration

| Package | Integration Point | Purpose |
|---------|-------------------|---------|
| `erikwang2013/snowflake-php` | 10 Models (SnowflakeTrait) + admin helpers.php | Primary key generation |
| `erikwang2013/hashids` | ApiResponse + 2 Admin Controllers | ID encoding |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | Auth tokens |
| `erikwang2013/encryption` | EncryptionMiddleware | Transport encryption |
| `erikwang2013/encryptable` | PlatformAccount + AuthToken Models | DB field encryption |
| `erikwang2013/webman-scout` | Campaign Model (Searchable trait) | ES search |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | Country flags |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | Slider CAPTCHA |
| `hg/apidoc` | Annotations → doc generation (Web UI: :8788/apidoc) | API docs |

---

## 10. High-Concurrency Architecture

### 10.1 Database Layer

| Optimization | Description |
|--------------|-------------|
| Read/write split | Primary `shared` (writes) + read replica `read_replica` (reporting/analytics queries) |
| Persistent connections | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent` to avoid frequent TCP handshakes |
| Connection warmup | Execute `SELECT 1` at worker startup, accept requests only after the pool is ready |

### 10.2 Cache Layer

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 Message Queue

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4 channels: `sync` | `report` | `export` | `notification`

### 10.4 Horizontal Scaling

```
                    ┌──────────────────┐
                    │   Nginx :80      │
                    │ upstream service │
                    └────────┬─────────┘
                             │
              ┌──────────────┼──────────────┐
              v              v              v
        ┌──────────┐  ┌──────────┐  ┌──────────┐
        │ php:8788 │  │ php2:8788│  │ php3:8788│
        │ worker 1 │  │ worker 2 │  │ worker 3 │
        └──────────┘  └──────────┘  └──────────┘
              │              │              │
              └──────────────┼──────────────┘
                             v
                    ┌──────────────────┐
                    │   MySQL + Redis  │
                    └──────────────────┘
```

- **keepalive**: 32 persistent connections reused
- **failover**: `proxy_next_upstream` automatic failover, 2 retries
- **rate limit**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 Static Asset CDN

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — pre-compressed js/css files
- CDN in production (CloudFront/Aliyun CDN)

### 10.6 Asset CDN Acceleration

Asset URL assembly, caching and purge policies: see [Chapter 12 Asset Storage & CDN Acceleration](#12-asset-storage--cdn-acceleration).

---

## 11. Deployment & CI/CD

### Docker Services

| Service | Port | Image |
|---------|------|-------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP Syntax → PHPUnit → TypeScript → Docker Build
- **CD** (`.github/workflows/deploy.yml`): Docker Buildx → GHCR Push (service/admin/admin-php) → Deploy

---

## 12. Asset Storage & CDN Acceleration

### 12.1 Storage Abstraction Layer

`service/plugin/ads-storage/` provides a unified `Storage` facade + `StorageDriver` interface (put/delete/signedUrl/publicUrl/putFile/deleteUrl/purge), switching implementations by driver:

| driver | implementation | use case |
|--------|----------------|----------|
| `local` | LocalStorage | Default, local `public/uploads/assets/` |
| `oss` | AlibabaOssStorage | Alibaba Cloud OSS |
| `cos` | TencentCosStorage | Tencent Cloud COS (S3 protocol) |
| `s3` | S3CompatibleStorage | S3-compatible: AWS S3 / Cloudflare R2 / MinIO |

Delivery prefers the DB default provider (configurable in admin), falling back to env/local.

### 12.2 CDN Provider Management

New table `ads_cdn_providers` (name/driver/bucket/region/endpoint/access_key/secret_key/cdn_domain/cdn_driver/cdn_token/enabled/is_default/status):

- Credentials (access_key/secret_key/cdn_token) encrypted at field level via `Erikwang2013\Encryptable`; API responses return masked fields only
- Only the platform master tenant (tenantId=1) can manage (AdminMiddleware), 8 endpoints under `/api/admin/cdn/providers`: list/create/update/delete/set-default/toggle/connectivity test/cache purge
- purge is really implemented for the `aliyun` cdn_driver (OpenAPI signing); cloudflare/cloudfront pending

### 12.3 URL Assembly

`ads_assets.url` always stores a relative path (`/uploads/assets/...`); at read time it is prefixed with the default provider's `cdn_domain` into a full HTTPS URL (`https://{cdn_domain}/{url}`), and returned as-is when no CDN is configured.

### 12.4 Cache Policy

| type | policy |
|------|--------|
| images | `immutable` long cache (random filenames, unique URLs, safe for long-lived caching) |
| video | short cache + Range support (segmented playback) |

Deleting an asset automatically purges its URL from the CDN cache.

### 12.5 Multi-Tenant Path Isolation

Asset keys carry a tenant-isolated prefix and are grouped by tenant_id; assets of different tenants are invisible to each other.

### 12.6 Presigned Direct Upload & Backfill

- `POST /api/assets/presign`: obtain a presigned upload URL (client direct upload to object storage, e.g. 50 MiB videos); `key` format `Ymd/32-hex.ext`
- `POST /api/assets/register`: register a directly uploaded asset; key format strictly validated against path traversal
- presign is unavailable under the `local` driver (no object-storage signing capability)
- `service/scripts/backfill-assets.php`: copies existing local assets into object storage (`--dry-run` preview); the `url` column stays unchanged

### 12.7 Origin Path

`service/config/static.php` enables webman static file serving; `/uploads/assets` is directly served over HTTP on 8788 as the CDN origin path.
