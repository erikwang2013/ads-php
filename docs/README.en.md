# Ads Platform — Multi-Platform Ad Management System

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## Overview

**Ads Platform** is a multi-platform ad management system integrating **29 advertising platforms** (16 domestic + 13 international), providing unified ad delivery management and cross-platform reporting.

- **Campaign Management** — OAuth account authorization, unified management of campaigns/ad groups/creatives across platforms
- **Reporting** — cross-platform metric aggregation, CSV/Excel/PDF export, 5-model cross-platform attribution
- **Smart Delivery** — auto-bidding, budget alerts, campaign calendar (Gantt), asset library
- **Global Acceleration** — asset CDN delivery (local / Alibaba Cloud OSS / Tencent Cloud COS / S3-compatible multi-driver, multi-provider config in admin)
- **Monitoring & Alerts** — alert rule engine, multi-channel push, scheduled auto-sync
- **Multi-Device Access** — Web admin (Vue 3), Flutter PC/Mobile, HarmonyOS
- **Stability & Reliability** — platform call circuit breaker/degradation/timeout, 3-tier cache, high-concurrency optimizations, 22 security protections
- **Internationalization** — 12-language docs, bilingual UI (ZH/EN)

> Architecture → [docs/architecture.en.md](docs/architecture.en.md)  
> Features → [docs/features.en.md](docs/features.en.md)  
> API Reference → [docs/api.en.md](docs/api.en.md) | hg/apidoc: `http://127.0.0.1:8788/apidoc`  
> Version Comparison → [docs/versions.en.md](docs/versions.en.md) (Lite open-source / Standard & Full: contact erik@erik.xyz)

### Supported Platforms

#### China (16)
| Platform | Adapter | Auth |
|----------|---------|------|
| Juliang (Ocean Engine) | Juliang | OAuth2 Access-Token |
| Baidu Marketing | Baidu | OAuth2 + Envelope Sign |
| Taobao / Alimama | Taobao | OAuth2 + MD5 |
| Tencent Ads | Tencent | OAuth2 + nonce |
| Kuaishou (Kwai) | Kuaishou | OAuth2 URL Params |
| Xiaohongshu (RED) | Xiaohongshu | OAuth2 Bearer |
| Weibo Fans Connect | Weibo | OAuth2 Bearer |
| Bilibili Huahuo | Bilibili | OAuth2 Bearer |
| Youku Ads | Youku | OAuth2 + MD5 |
| Meituan Ads | Meituan | OAuth2 Bearer |
| Zhihu Ads | Zhihu | OAuth2 Bearer |
| Qihoo 360 | Qihoo360 | API Key + Sign |
| Sogou | Sogou | API Key + Sign |
| Umeng | Umeng | API Key + MD5 |
| JD Jingzhuntong | Jingdong | OAuth2 + MD5 |
| Pinduoduo Ads | Pinduoduo | OAuth2 + Custom Sign |

#### International (13)
| Platform | Adapter | Auth |
|----------|---------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 URL Params |
| TikTok Ads | Tiktok | OAuth2 Access-Token |
| LinkedIn Ads | Linkedin | OAuth2 Bearer |
| Snapchat Ads | Snapchat | OAuth2 Bearer |
| Pinterest Ads | Pinterest | OAuth2 Bearer |
| Twitter/X Ads | Twitter | OAuth2 Bearer |
| Amazon Ads | Amazon | OAuth2 + Profile |
| The Trade Desk | TheTradeDesk | HMAC-SHA256 |
| Spotify Ads | Spotify | OAuth2 Bearer |
| Twitch Ads | Twitch | OAuth2 Bearer + ClientId |
| Netflix Ads | Netflix | OAuth2 client_credentials |

---

## Tech Stack

| Layer | Technology | Notes |
|-------|-----------|-------|
| Server | webman v2 + PHP 8.2+ | 8 plugins, 75+ API endpoints |
| Database | MySQL 8.0 | 29 tables, `ads_` prefix, Snowflake BIGINT PK |
| Cache | Redis 7 | 3-tier cache (L1 memory / L2 APCu / L3 Redis), rate limiting, Pub/Sub, message queue |
| Search | Elasticsearch | webman-scout auto index sync (configured) |
| Admin Panel | webman-admin v2 + Vue 3 + TypeScript + Element Plus | PHP backend (port 8789), SPA calls business API directly (port 8788), 19 pages, ECharts |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | PC/Mobile responsive, Desktop Shell layout, 12 pages |
| HarmonyOS | ArkTS + ArkUI | 6 pages implemented, HTTP client ready |
| Deployment | Docker + Nginx + GHCR | Docker Compose one-command start, GitHub Actions CI/CD |

## Architecture Diagram

![System Architecture Diagram](docs/diagrams/svg/architecture.en.svg)

### Request Flow

![Request Flow Diagram](docs/diagrams/svg/request-flow.en.svg)

### Functional Modules

![Functional Modules Diagram](docs/diagrams/svg/functional-modules.en.svg)

### Data Lifecycle

![Data Lifecycle Diagram](docs/diagrams/svg/data-lifecycle.en.svg)

> Full versions with all annotations, Admin pipeline, cron Gantt chart, cache state machine → [docs/diagrams/](docs/diagrams/)

> Detailed architecture, security architecture, and high-concurrency design → [Architecture Design Document](docs/architecture.en.md) | Historical design specs → [design.md](docs/superpowers/specs/design.en.md)

## Architecture Notes

- **`service/`** — webman v2 user-facing business API, port **8788**. Handles ad platform integration, OAuth, data sync, reporting engine, alert monitoring.
- **`admin/`** — webman-admin v2 standalone admin panel, port **8789**. PHP backend (auth, user management, system config) + Vue 3 SPA frontend.
- **Admin-to-Service Communication** — The Vue SPA calls the business API directly (axios, baseURL `/api`, proxied to service:8788); admin-only routes (`/api/admin/*`) are served by the admin PHP backend on 8789 via Nginx location splitting.
- **Dev Mode** — Vite dev server (port 5173) proxies `/api` to service:8788; admin PHP backend on 8789 provides session auth and SPA static serving.
- **Production** — Nginx routes `/` to admin:8789 (admin SPA), `/api/` to service:8788 (business API).

## Erik Stack Integration

| Package | Purpose |
|---------|---------|
| `erikwang2013/snowflake-php` | Distributed Snowflake ID generation |
| `erikwang2013/hashids` | API ID parameter obfuscation |
| `erikwang2013/jwt-webman` | JWT authentication tokens |
| `erikwang2013/encryption` | API-layer sensitive data encryption |
| `erikwang2013/encryptable` | DB field-level auto encryption |
| `erikwang2013/webman-scout` | Elasticsearch data sync |
| `erikwang2013/season` | Country flag identifiers |
| `erikwang2013/poster-php` | Slider CAPTCHA (login protection) |
| `hg/apidoc` | Auto-generated API docs (annotations + Web UI) |

## Internationalization

All interfaces support **Chinese (zh-CN)** / **English (en)** bilingual switching:

| End | Technology | Switch |
|-----|-----------|--------|
| Admin | vue-i18n v9 | TopBar language dropdown, localStorage persistence |
| Service API | `erik\support\I18n` | Accept-Language header / `?lang=` parameter |
| Flutter | AppLocalizations + Delegate | System language auto-detect |
| HarmonyOS | StringResources | `setLang()` toggle |

## Security

### Service (14 global + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware (route-level)

### Admin (10 global + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck (route-level)

### Defense Summary (22 items)

| Category | Protection | Details |
|----------|-----------|---------|
| Input Detection | XSS (11 patterns) | script/iframe/event handler/javascript:/data: |
| | Path Traversal (7 patterns) | ../ / null byte / /etc/passwd / .env / .git |
| | Header Injection | CRLF detection |
| | Body Size Limit | 10 MiB |
| | Content-Type Whitelist | JSON/Form/Multipart/Plain |
| | SQL Injection | UNION/DROP/ALTER pattern detection |
| Authentication | JWT Token Binding | IP + User-Agent hash verification |
| | Token Refresh + Blacklist | Old tokens auto-invalidated |
| | Login Throttle | 5 failures → 15 min lockout (Redis) |
| | Concurrent Session Limit | Max 3 active tokens per user |
| | CAPTCHA | Slider CAPTCHA (5 min valid, 5px tolerance) |
| Request Validation | CORS Whitelist | Production domain whitelist |
| | Origin/Referer Check | Cross-origin source verification |
| | CSRF Token | Admin session token verification |
| | Anti-Replay | Nonce + Timestamp ±5min (non-browser) |
| | Rate Limiting | Sliding window 60 req/60s |
| | SSRF Protection | OAuth redirect_uri whitelist |
| Response Headers | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | Anti-clickjacking + HTTPS enforcement |
| | X-Content-Type-Options | nosniff |
| Data Protection | Transport Encryption | EncryptionMiddleware (X-Encrypted) |
| | Storage Encryption | Encryptable (DB field-level) |
| | Log Redaction | password/token/secret → `***` |

### Security Architecture

![Security Architecture Diagram](docs/diagrams/svg/security.en.svg)

**Defense in Depth**: Perimeter (Nginx) → Entry Guard (5 middleware) → Authentication (7 items) → Input Validation (4 items) → Rate Control → Encryption → Audit Trail

**Authentication**: Unified `admin_users` table + bcrypt hashing across service and admin, JWT 24h + refresh rotation.

**Auditing**: All operations logged with IP / User-Agent / Client-Platform / action details.

**Confirmation**: Delete/unbind/batch operations use "type-to-confirm" pattern (`GlobalConfirm` + `useConfirmStore`).

---

## Advanced Features

| Feature | Description | Technology |
|---------|-------------|------------|
| Asset Library | Image/video upload, gallery preview, copy URL | AssetController + Vue Gallery |
| Budget Alerts | Daily budget real-time tracking, 3-tier alerts (50/80/100%) | BudgetAlertService + 15min Cron |
| Campaign Calendar | Cross-platform Gantt chart, month/week views, color-coded | CalendarService + Vue Gantt |
| Cross-Platform Attribution | 5 attribution models (first/last/linear/time_decay/position_based), 30-day lookback | AttributionEngine + ECharts |
| Platform Call Resilience | Per-platform circuit breaker state machine (5 failures → OPEN → 30s half-open probe), degradation fast-fail, 29 adapter timeout audit | CircuitBreaker + GuardedAdapter |
| CDN Asset Acceleration | Object storage multi-driver (local/oss/cos/s3), admin CDN provider management, presigned direct upload, auto cache purge on delete | ads-storage plugin + CdnProviderController |

---

## High Concurrency

| Optimization | Approach | File |
|-------------|----------|------|
| DB Read/Write Split | Primary `shared` + read replica `read_replica`, SELECT auto-routed | `config/database.php` |
| DB Connection Pool | `PDO::ATTR_PERSISTENT` + timezone init warmup | `config/database.php` |
| Redis Connection Pool | `persistent` connections + read/write split `readonly` config | `config/redis.php` |
| 3-Tier Cache | L1 process memory → L2 APCu shared → L3 Redis | `support/CacheService.php` |
| Async Message Queue | Redis List 4 channels (sync/report/export/notification) | `support/AsyncJobService.php` |
| Nginx Tiered Rate Limit | 30r/s + burst 20 + 20 concurrent + keepalive 32 | `docker/nginx/admin.conf` |
| Horizontal Scaling | upstream multi-instance + failover + sticky session | `docker/nginx/admin.conf` |
| CDN Acceleration | Static assets `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## Quick Start

### One-Click Web Install (Recommended)

Start the server and visit `/install` for the setup wizard:

```bash
# Start admin panel (port 8789)
cd admin && composer install && php start.php start

# Open http://localhost:8789/install in your browser
# Fill in database info, admin account, click "Install"
```

The wizard guides you through:
1. **Database Connection** — MySQL host, port, database name, credentials, with connection test
2. **Redis Configuration** — Redis connection settings (optional)
3. **Admin Account** — Backend login username, password, display name
4. **One-Click Install** — Auto-create database, run `install.sql` to create 29 tables with seed data, set admin password

After installation, visit `/` and log in with your configured credentials.

### Docker (Recommended for Production)

```bash
# Start all services (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# Initialize database (create tables + seed data)
make db-init

# Access
# Admin Panel: http://localhost
# Install Wizard: http://localhost/install
# API: http://localhost/api (Header: X-API-Version: v1)
```

### Local Development

```bash
# Service API (port 8788)
cd service && composer install && php start.php start

# Admin Panel (port 5173)
cd admin/public/web && npm install && npm run dev

# Flutter App
cd apps/flutter && flutter run -d chrome  # Web PC
# HarmonyOS App
# Open apps/harmonyos in DevEco Studio
cd apps/flutter && flutter run -d android # Mobile

# TypeScript Check
cd admin/public/web && npx vue-tsc --noEmit   # zero errors
```

Usage Guide → [docs/usage.en.md](docs/usage.en.md)
---

## Project Structure

```
ads-php/
├── service/                           # Business API service (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (45+ endpoints, versioned routes)
│   │   │   ├── controller/v1/         # 14 controllers
│   │   │   ├── middleware/            # 7 middleware
│   │   │   ├── config/route.php       # Route definitions
│   │   │   └── route_helpers.php      # versioned() helper
│   │   ├── ads-platform/              # Platform adapter core
│   │   │   ├── adapter/               # 29 platform adapters
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # SQL migrations + performance indexes
│   │   ├── ads-account/               # OAuth account management
│   │   ├── ads-task/                  # Scheduled tasks (6 cron jobs)
│   │   ├── ads-alert/                 # Alert monitoring engine + budget alerts
│   │   ├── ads-report/                # Reporting engine (CSV/Excel/PDF) + attribution + calendar
│   │   ├── ads-tenant/                # Multi-tenant management
│   │   └── ads-storage/               # Storage abstraction (local/OSS/COS/S3) + CDN providers
│   ├── scripts/backfill-assets.php    # Backfill existing assets to object storage
│   ├── support/                       # Erik Stack utilities
│   │   ├── ControllerTrait.php        # Controller shared trait
│   │   ├── JwtService.php             # JWT wrapper
│   │   ├── CacheService.php           # Redis cache service
│   │   ├── ExceptionHandler.php       # API exception handler
│   │   └── ApiResponse.php            # Unified response format
│   ├── config/                        # Global config (DB/Redis/Log/Middleware)
│   ├── tests/                         # PHPUnit tests (288 tests)
│   │   ├── Unit/                      # Unit tests (Middleware, Task)
│   │   └── Integration/               # Integration tests (Auth, Health)
│   └── start.php                      # Service entry point
├── admin/                             # Standalone admin panel (webman-admin v2 :8789)
│   ├── public/web/src/
│   │   ├── views/                     # 15 Vue pages
│   │   │   ├── dashboard/             # Dashboard (ECharts)
│   │   │   ├── campaign/              # Campaigns
│   │   │   ├── adgroup/               # Ad Groups
│   │   │   ├── creative/              # Creatives
│   │   │   ├── report/                # Reports + export
│   │   │   ├── alert/                 # Alert rules + logs
│   │   │   ├── notification/          # Notification center
│   │   │   ├── bid/                   # Auto-bid rules
│   │   │   └── system/                # User management + audit logs
│   │   ├── api/                       # 9 API clients
│   │   ├── stores/                    # 4 Pinia stores
│   │   └── components/                # Shared components (ListPageLayout etc.)
│   ├── app/                           # PHP backend (controller/middleware)
│   └── config/                        # Admin config
├── apps/
│   ├── flutter/                       # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/              # 12 feature pages + Shell layout
│   │       ├── config/menu_config.dart # 2-level menu config
│   │       ├── router.dart            # GoRouter (ShellRoute + route guard)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API Client ready)
├── docker/                            # Docker & Nginx config
├── .github/workflows/                 # CI (lint→test→TS→Docker) + CD (build→push)
├── docs/                              # Design docs, implementation plans, Skills
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## API Endpoints

> Full API endpoint definitions (with request/response examples, error codes, rate limits) are in [docs/api.en.md](docs/api.en.md).
> hg/apidoc live docs: visit `http://127.0.0.1:8788/apidoc` after starting the service

## Database

**Naming Convention**: Table prefix `ads_`, PK `BIGINT UNSIGNED PRIMARY KEY` (no auto-increment, Snowflake ID), engine InnoDB, charset utf8mb4

| Category | Tables | Purpose |
|----------|--------|---------|
| Foundation | `ads_tenants` | Multi-tenancy |
| Accounts | `ads_platform_accounts`, `ads_auth_tokens` | OAuth platform accounts |
| Campaigns | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | Ad delivery hierarchy |
| Reporting | `ads_report_metrics`, `ads_report_extras` | Unified report metrics |
| Assets | `ads_assets` | Creative asset library |
| CDN | `ads_cdn_providers` | CDN provider config (credentials encrypted) |
| Targeting | `ads_targeting_templates` | Audience targeting templates |
| Attribution | `ads_conversions`, `ads_attribution_results` | Conversion tracking + attribution |
| Bidding | `ads_bid_rules`, `ads_bid_logs` | Auto-bid rules + history |
| Alerts | `ads_alert_rules`, `ads_alert_logs` | Alert monitoring |
| Notifications | `ads_notifications` | In-app notifications |
| System | `ads_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | Sync errors, RBAC, audit |

---

## Scheduled Tasks

| Task | Frequency | Function |
|------|-----------|----------|
| TokenRefreshTask | Every 55 min | Scan expired OAuth tokens, auto-refresh |
| DataSyncTask | Every 10 min | Pull platform campaigns+adgroups+creatives+reports, write to unified tables, clear cache |
| AlertCheckTask | Every 5 min | Iterate enabled alert rules, evaluate thresholds, trigger push |
| BidCheckTask | Every 10 min | Iterate auto-bid rules, query metrics, execute budget adjustment/toggle |
| BudgetCheckTask | Every 15 min | Track daily budget consumption, 3-tier alerts (50/80/100%) |
| RetrySyncTask | Every 3 min | Retry failed sync jobs (max 3 retries, exponential backoff) |

---

## Testing

```bash
cd service && ./vendor/bin/phpunit
# 288 tests / 862 assertions
```

**Coverage**: 14 middleware · 8 plugin business layers (account/alert/platform/report/task/tenant/storage) · engines (Bid/Alert/Attribution/Report) · API integration tests (76 routes) · UI E2E (18 pages)

```bash
# TypeScript Check
cd admin/public/web && npx vue-tsc --noEmit   # zero errors

# Dart Analysis
cd apps/flutter && dart analyze   # zero errors
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): Automated pipeline — **PHP Syntax → PHPUnit → TypeScript → Docker Build**

**CD** (`.github/workflows/deploy.yml`): Manual trigger — **Docker Buildx → Push GHCR (service/admin/admin-php) → Deploy notification**

`.github/dependabot.yml` auto-updates Composer + npm + Docker dependencies weekly.

---

## Skills

`docs/skills/` — 11 reusable project skills:

| Skill | Description |
|-------|-------------|
| `adapter-generator` | Generate new ad platform adapter (14-method template) |
| `migration-generator` | Generate SQL migration (`ads_` prefix + BIGINT PK) |
| `erik-stack` | Erik Stack 8-package integration guide |
| `admin-page-generator` | Generate Vue 3 admin page |
| `api-endpoint` | Add RESTful API endpoint |
| `tdd-workflow` | TDD verification workflow (test→implement→lint→TS→commit) |
| `security-middleware` | Add security middleware layer (spec + register + ref existing chain) |
| `version-split` | Lite/Standard/Full version split (steps + config update) |
| `cache-strategy` | 3-tier caching strategy (L1 memory/L2 APCu/L3 Redis + TTL advice) |
| `attribution-setup` | Cross-platform attribution engine (5 models + API calls + data prep) |
| `high-concurrency` | 8 high-concurrency optimizations (R/W split/pooling/queue/scaling/CDN) |

---

## Open Source Needs Your Support

| WeChat | Alipay |
|:---:|:---:|
| ![WeChat](./docs/weixinpay.png "WeChat") | ![Alipay](./docs/alipay.png "Alipay") |

### Global Transfer Donation

**Beneficiary**

| Field | Value |
|-------|-------|
| Beneficiary Name | WANG KEXUN |
| Account Number | 881015918251 |

**Receiving Bank — ZA Bank**

| Field | Value |
|-------|-------|
| SWIFT Code | AABLHKHHXXX |
| Bank Name | ZA Bank Limited |
| Bank Code | 387 |
| Bank Address | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **Correspondent Bank (if required)**: This is the intermediary bank information, not the receiving bank. Please check with your remitting bank whether correspondent bank details are needed.
>
> - **HKD, CNY & USD**: Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · Bank Code 006 · Hong Kong Branch (Branch 391) · Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **Other currencies**: THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

### Crypto Donation

If this project helps you, scan the QR code to donate, thank you!

| Network | QR Code | Wallet Address |
|---|---|---|
| BNB Smart Chain (BEP20) | [<img src="./coin/1.jpg" width="150" alt="BNB Smart Chain (BEP20)">](./coin/1.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Tron (TRC20) | [<img src="./coin/2.jpg" width="150" alt="Tron (TRC20)">](./coin/2.jpg) | `TEdDHWLajt1XvqtPDWmQctdrJaC3pzZZzz` |
| Ethereum (ERC20) | [<img src="./coin/3.jpg" width="150" alt="Ethereum (ERC20)">](./coin/3.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Aptos | [<img src="./coin/4.jpg" width="150" alt="Aptos">](./coin/4.jpg) | `0x836e3780edfc3f7b2372b39e2a1a3a5d7adfaccd96c726f21cfde1b50dd68030` |
| Plasma | [<img src="./coin/5.jpg" width="150" alt="Plasma">](./coin/5.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Polygon POS | [<img src="./coin/6.jpg" width="150" alt="Polygon POS">](./coin/6.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Solana | [<img src="./coin/7.jpg" width="150" alt="Solana">](./coin/7.jpg) | `2hfhboHdmdrYsY25XfQSsEWxq5ip4EQsR7f4AzSRMUyr` |
| The Open Network (TON) | [<img src="./coin/8.jpg" width="150" alt="The Open Network (TON)">](./coin/8.jpg) | `UQB9kFQohzmXUir9QSSZq01iwl9aQZIDdBpNmDklljRtCoGK` |
| Arbitrum One | [<img src="./coin/9.jpg" width="150" alt="Arbitrum One">](./coin/9.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| AVAX C-Chain | [<img src="./coin/10.jpg" width="150" alt="AVAX C-Chain">](./coin/10.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |

## License

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
