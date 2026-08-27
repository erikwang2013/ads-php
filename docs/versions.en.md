# Version Comparison

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| Version | License | How to Get |
|---------|---------|------------|
| **Lite** | Open source (MIT) | Public GitHub repository |
| **Standard** | Commercial license | Contact erik@erik.xyz |
| **Full** | Commercial license | Contact erik@erik.xyz |

---

## Feature Comparison

### Basic Features

| Feature | Lite | Standard | Full |
|---------|:---:|:---:|:---:|
| Auth (login/token refresh/current user) | ✅ | ✅ | ✅ |
| Platform management (29 platform list + OAuth) | ✅ | ✅ | ✅ |
| Account management (CRUD + sync) | ✅ | ✅ | ✅ |
| Campaigns (CRUD + toggle + batch) | ✅ | ✅ | ✅ |
| Reporting (dashboard + custom + export CSV/Excel/PDF) | ✅ | ✅ | ✅ |
| Health check + API docs + CAPTCHA | ✅ | ✅ | ✅ |
| Data sync (Campaign + Report) | ✅ | ✅ | ✅ |

### Delivery Management

| Feature | Lite | Standard | Full |
|---------|:---:|:---:|:---:|
| Ad groups (CRUD + toggle) | — | ✅ | ✅ |
| Creatives (list + detail) | — | ✅ | ✅ |
| Ad group/creative data sync | — | ✅ | ✅ |

### Monitoring & Notifications

| Feature | Lite | Standard | Full |
|---------|:---:|:---:|:---:|
| Alert rule engine (7 metrics/4 conditions/3 scopes) | — | ✅ | ✅ |
| Alert logs + acknowledge + unread count | — | ✅ | ✅ |
| Notification center (list/read/read all) | — | ✅ | ✅ |

### Advanced Features

| Feature | Lite | Standard | Full |
|---------|:---:|:---:|:---:|
| Auto-bid rule engine (3 actions/cooldown) | — | — | ✅ |
| Audience targeting templates (common JSON Schema) | — | — | ✅ |
| Ad asset library (upload/gallery/preview) | — | — | ✅ |
| Budget alerts (3-tier 50/80/100%) | — | — | ✅ |
| Campaign calendar (Gantt visualization) | — | — | ✅ |
| Cross-platform attribution (5 models/30-day lookback) | — | — | ✅ |

---

## Security Comparison

| Protection | Lite | Standard | Full |
|------------|:---:|:---:|:---:|
| CORS whitelist | ✅ | ✅ | ✅ |
| Security response headers (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| Version routing (X-API-Version) | ✅ | ✅ | ✅ |
| API rate limiting (sliding window) | ✅ | ✅ | ✅ |
| SQL injection detection (pattern matching) | ✅ | ✅ | ✅ |
| Input filtering (strip_tags + trim) | ✅ | ✅ | ✅ |
| Transport encryption (X-Encrypted) | ✅ | ✅ | ✅ |
| JWT Bearer auth | ✅ | ✅ | ✅ |
| XSS detection (11 patterns) | — | ✅ | ✅ |
| Path traversal detection (7 patterns) | — | ✅ | ✅ |
| Header injection detection | — | ✅ | ✅ |
| Body size limit (10 MiB) | — | ✅ | ✅ |
| Content-Type whitelist | — | ✅ | ✅ |
| Client source identification (8 clients) | — | ✅ | ✅ |
| Login throttle (5 failures → 15 min) | — | ✅ | ✅ |
| Response time monitoring (X-Response-Time) | — | ✅ | ✅ |
| Origin/Referer validation | — | — | ✅ |
| Anti-replay (Nonce + Timestamp) | — | — | ✅ |
| Concurrent session limit (max 3) | — | — | ✅ |
| CSRF Token (Admin) | — | — | ✅ |
| SSRF protection (OAuth whitelist) | — | — | ✅ |
| Log data redaction | — | — | ✅ |
| JWT IP/UA binding | — | — | ✅ |

---

## Middleware Chain Comparison

### Service

| Lite (7 layers) | Standard (11 layers) | Full (15 layers) |
|-----------------|----------------------|------------------|
| CorsMiddleware | CorsMiddleware | CorsMiddleware |
| — | — | OriginGuardMiddleware |
| SecurityHeadersMiddleware | SecurityHeadersMiddleware | SecurityHeadersMiddleware |
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | ReplayGuardMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |
| RateLimitMiddleware | RateLimitMiddleware | RateLimitMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | — | SessionLimitMiddleware |
| SqlGuardMiddleware | SqlGuardMiddleware | SqlGuardMiddleware |
| ValidationMiddleware | ValidationMiddleware | ValidationMiddleware |
| — | ResponseTimeMiddleware | ResponseTimeMiddleware |
| EncryptionMiddleware | EncryptionMiddleware | EncryptionMiddleware |

### Admin

| Lite (1 layer) | Standard (4 layers) | Full (5 layers) |
|----------------|---------------------|-----------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## Scheduled Task Comparison

| Task | Frequency | Lite | Standard | Full |
|------|-----------|:---:|:---:|:---:|
| TokenRefreshTask | 55min | ✅ | ✅ | ✅ |
| DataSyncTask | 10min | ✅ (Campaign+Report only) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5min | — | ✅ | ✅ |
| BidCheckTask | 10min | — | — | ✅ |
| BudgetCheckTask | 15min | — | — | ✅ |

---

## Database Table Comparison

| Category | Tables | Lite | Standard | Full |
|----------|--------|:---:|:---:|:---:|
| Foundation | ads_tenants | ✅ | ✅ | ✅ |
| Accounts | ads_platform_accounts | ✅ | ✅ | ✅ |
| | ads_auth_tokens | ✅ | ✅ | ✅ |
| Delivery | ads_campaigns | ✅ | ✅ | ✅ |
| | ads_report_metrics | ✅ | ✅ | ✅ |
| | ads_report_extras | ✅ | ✅ | ✅ |
| | ads_ad_groups | — | ✅ | ✅ |
| | ads_creatives | — | ✅ | ✅ |
| Alerts | ads_alert_rules | — | ✅ | ✅ |
| | ads_alert_logs | — | ✅ | ✅ |
| Notifications | ads_notifications | — | ✅ | ✅ |
| Bidding | ads_bid_rules | — | — | ✅ |
| | ads_bid_logs | — | — | ✅ |
| Targeting | ads_targeting_templates | — | — | ✅ |
| Assets | ads_assets | — | — | ✅ |
| Attribution | ads_conversions | — | — | ✅ |
| | ads_attribution_results | — | — | ✅ |
| System | ads_sync_errors | ✅ | ✅ | ✅ |
| Admin | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **Total** | | **8** | **13** | **18** |

---

## Frontend Page Comparison

### Vue Admin SPA

| Page | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Login | ✅ | ✅ | ✅ |
| Dashboard | ✅ | ✅ | ✅ |
| Account list + bind | ✅ | ✅ | ✅ |
| Campaigns | ✅ | ✅ | ✅ |
| Report export | ✅ | ✅ | ✅ |
| User management | ✅ | ✅ | ✅ |
| Audit logs | ✅ | ✅ | ✅ |
| Ad groups | — | ✅ | ✅ |
| Creatives | — | ✅ | ✅ |
| Report analysis (ECharts) | — | ✅ | ✅ |
| Alert rules | — | ✅ | ✅ |
| Alert logs | — | ✅ | ✅ |
| Notification center | — | ✅ | ✅ |
| Auto-bidding | — | — | ✅ |
| Asset library | — | — | ✅ |
| Campaign calendar | — | — | ✅ |
| Attribution analysis | — | — | ✅ |
| **Total** | **7** | **13** | **17** |

### Flutter

| Page | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Login | ✅ | ✅ | ✅ |
| Dashboard | ✅ | ✅ | ✅ |
| Campaigns (list + detail) | ✅ | ✅ | ✅ |
| Reporting | ✅ | ✅ | ✅ |
| Platform accounts | ✅ | ✅ | ✅ |
| Alert management | ✅ | ✅ | ✅ |
| Ad groups | — | ✅ | ✅ |
| Creatives | — | ✅ | ✅ |
| Report analysis | — | ✅ | ✅ |
| Notification center | — | ✅ | ✅ |
| Auto-bidding | — | — | ✅ |
| **Total** | **6** | **10** | **11** |

---

## API Endpoint Comparison

| Module | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| System (health/ping/docs/captcha) | 6 | 6 | 6 |
| Auth (login/me/refresh) | 3 | 3 | 3 |
| Platforms (list/oauthUrl/callback) | 3 | 3 | 3 |
| Accounts (index/show/destroy/sync) | 4 | 4 | 4 |
| Campaigns (CRUD/toggle/batch) | 6 | 6 | 6 |
| Ad groups (CRUD/toggle) | — | 5 | 5 |
| Creatives (index/show) | — | 2 | 2 |
| Reporting (summary/custom/export×2) | 4 | 4 | 4 |
| Reporting (calendar/budget/attribution/models) | — | — | 4 |
| Alerts (rules CRUD + logs + acknowledge + unread) | — | 7 | 7 |
| Notifications (index/unread/read/readAll) | — | 4 | 4 |
| Auto-bidding (CRUD + logs) | — | — | 5 |
| Targeting templates (CRUD) | — | — | 5 |
| Asset library (index/upload/show/destroy) | — | — | 4 |
| **Total** | **26** | **44** | **62** |

---

## Tech Stack

All three versions share a unified tech stack:

| Layer | Technology |
|-------|------------|
| Backend framework | webman v2, PHP 8.2+ |
| Database | MySQL 8.0 (InnoDB, utf8mb4) |
| Cache | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| Auth | erikwang2013/jwt-webman |
| ID generation | erikwang2013/snowflake-php |
| ID encoding | erikwang2013/hashids |
| Frontend | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| Deployment | Docker + Nginx + Docker Compose |

---

## Upgrade Path

```
Lite (开源)
  │
  ├─→ 升级到 Standard (联系 erik@erik.xyz)
  │     │
  │     └─→ 新增: 广告组/创意管理、告警引擎、通知中心、
  │              AttackGuard/XSS/路径遍历/登录节流/响应时间监控
  │
  └─→ 升级到 Full (联系 erik@erik.xyz)
        │
        └─→ 新增: Standard 全部 + 自动出价、定向模板、素材库、
                  预算预警、投放日历、跨平台归因、防重放/并发限制/CSRF/SSRF
```
