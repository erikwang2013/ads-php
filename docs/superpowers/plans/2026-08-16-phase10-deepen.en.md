# Phase 10: Deepening and Commercialization Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**Goal:** On top of the Phase 7-9 contracts and multi-channel foundation, deliver four deepening capabilities: sync status visualization, conversion data loop, mobile CI packaging, and multi-tenant SaaS quotas.

**Source:** Direction inferred from the Phase 7 team audit (researcher: ES/read-write split/queue implementation, Flutter/HarmonyOS CI, 29-platform real integration, SaaS billing quotas, conversion data loop, sync status visualization, AI bidding)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## Current State (verified)

| Candidate sub-item | Current state |
|---|---|
| Sync status visualization | `ads_sync_errors` table + `RetrySyncTask` (retry 3 times, backoff 5^n minutes) already exist; **no frontend page/API showing sync failure rate and latency** |
| Conversion data loop | `ads_conversions` + `ads_attribution_results` tables already exist, attribution engine implemented; **no conversion data collection entry point** (callback/tracking API) |
| Mobile CI | `ci.yml` only does PHP syntax → PHPUnit → vue-tsc → Docker; **no Flutter/HarmonyOS build packaging** |
| Multi-tenant SaaS | `ads_tenants` table + TenantIdentify middleware already exist; **no billing/quota/usage statistics** |
| ES implementation | scout.php configured + webman-scout dependency added; **docker-compose has no ES service** |
| 29-platform real integration | 29 adapter codebases complete; **no sandbox/credential integration records** (requires external credentials, marked as manual item) |

## Task 1: Sync status visualization

### Files:
- Modify: `service/plugin/ads-api/controller/v1/DashboardController.php` or add `service/plugin/ads-api/controller/v1/SyncController.php` + route
- Create: `admin/public/web/src/api/sync.ts`
- Create: `admin/public/web/src/views/sync/SyncStatus.vue` (or merge into system page)

### Design points
- Endpoints: `GET /api/sync/status` (account dimension: last_sync_at, success rate, today's failure count, pending retry count) + `GET /api/sync/errors` (paginated error list, including last_error/retry_count/next_retry_at)
- Frontend: sync status page (table + summary cards), Full/Standard version lines only
- Data sources: ads_platform_accounts (last_sync_at) + ads_sync_errors

## Task 2: Conversion data collection API

### Files:
- Modify: `service/plugin/ads-api/controller/v1/` (add ConversionController + route)
- Create: `service/plugin/ads-report/service/ConversionService.php`

### Design points
- Endpoints: `POST /api/conversions` (business-side conversion callbacks: platform/campaign_id/order_id/conversion_time/value/currency/channel) + `GET /api/conversions` (query)
- Validation: campaign_id exists, amount non-negative, time format; write to ads_conversions
- Attribution integration: callbacks can trigger attribution recomputation (or document that the existing AttributionEngine recomputes on a schedule/manually)
- Frontend: attribution report page adds "conversion callback" explanation/demo (optional)

## Task 3: Mobile CI packaging

### Files:
- Modify: `.github/workflows/ci.yml` (add jobs: Flutter build (web + linux or apk) + HarmonyOS static check)

### Design points
- Flutter: `flutter pub get && flutter analyze && flutter build web` (or apk, pick a buildable target per repo state; use dart analyze if the Flutter environment is constrained)
- HarmonyOS: no standard Linux CI toolchain; do a static-check note or skip (annotated)
- Runs in parallel with the existing php-tests job, does not block the main flow

## Task 4: Multi-tenant SaaS quota (MVP)

### Files:
- Modify: `service/plugin/ads-tenant/` (add QuotaService)
- Modify: `service/plugin/ads-api/config/route.php` + controller

### Design points
- Data: add quota fields to ads_tenants or a new table ads_tenant_quotas (plan/account_limit/campaign_limit/sync_quota)
- Check points: bound account count, campaign creation count, daily sync count (checked at AccountController/CampaignController/DataSyncTask entry points)
- Endpoints: `GET /api/tenant/quota` (usage + quota)
- Frontend: system page shows quota usage (optional; MVP can be API-only)
- Version lines: quota defaults differ by lite/standard/full (config constants)

## Acceptance (per Task)
- [ ] Task 1: sync API endpoints usable, frontend page displays, test coverage
- [ ] Task 2: conversions callback API writable and queryable, validation effective, test coverage
- [ ] Task 3: new CI jobs pass (or explicitly annotated skip items)
- [ ] Task 4: quota API returns correct values, over-limit interception works, test coverage
- [ ] All: `php vendor/bin/phpunit --no-coverage` all pass, vue-tsc passes

## Out of Scope for This Phase (requires external resources)
- 29-platform real integration (requires platform credentials/sandboxes)
- ES service implementation (requires adding ES service and index initialization to docker-compose)
- AI bidding suggestions (model/data preparation)
