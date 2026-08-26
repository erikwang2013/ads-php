# Phase 7: Cross-Client Contract Fix Implementation Plan

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **Status update (2026-08-16):** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ all complete, tester regression verification passed (35 tests OK, contract cross-check found no ghost endpoints, Phase 7 ready for acceptance).

**Goal:** Fix cross-client API contract issues found by the team audit: 3 Flutter ghost endpoints (404), Admin `admin.ts` double-prefix bug, `/system/info` without a route, ServiceProxy not wired up, outdated documentation. Restore consistent consumption of the service API across all three clients (Admin/Flutter/HarmonyOS).

**Source:** 2026-08-07 team parallel audit (backend-dev route inventory of 61 endpoints, vue-dev Admin call inventory of 50 call sites, mobile-dev mobile inventory, researcher cross-comparison of implemented/planned inventory)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1: Fix Flutter ghost endpoints (🔴 highest priority)

### Background
Flutter 3 pages call routes that do not exist in the service, all returning 404:

| Flutter call | Actual service route | Fix |
|---|---|---|
| `GET /dashboard` | None (dashboard summary is at `/reports/summary`) | Change to `GET /reports/summary` |
| `GET /alerts` | None (alerts are at `/alerts/rules`, `/alerts/logs`, `/alerts/unread-count`) | Change to `GET /alerts/logs` (alert list semantics) |
| `GET /reports` | None (reports are at `/reports/summary`, `/reports/custom`) | Change to `GET /reports/custom` (with date/dimension/metric parameters, matching ReportBuilder::buildCustom) |

### Files:
- Modify: `apps/flutter/lib/features/dashboard/dashboard_page.dart` (`/dashboard` → `/reports/summary` ×2 spots, adapt response structure `data.overview`/`by_platform`/`daily`) ✅
- Modify: `apps/flutter/lib/features/alert/alert_page.dart` (`/alerts` → `/alerts/logs`, adapt pagination structure `data.list`, AlertLog fields rule_name/metric/current_value/condition/threshold) ✅
- Modify: `apps/flutter/lib/features/report/report_page.dart` (`/reports` → `/reports/custom`, parameters date_start/date_end/dimensions[]/metrics[], parse `data.list`, field cost) ✅
- Verify: response fields match actual returns of `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` ✅

### Acceptance
- [x] Three path modifications complete, query parameters preserved (report page date params → date_start/date_end + dimensions/metrics) ✅
- [x] Response parsing aligned with actual backend JSON structure (overview / paginated list / custom list) ✅
- [x] No errors after modification — Flutter SDK cache is read-only in this environment and cannot run, so the SDK's built-in `dart analyze` was used on the whole project: **0 errors** (the 15 pre-existing warnings all existed before the changes; no new issues introduced) ✅

---

## Task 2: Fix Admin `admin.ts` double-prefix bug

### Background
- `admin/public/web/src/api/admin.ts` paths are written as `/api/admin/...`, while the axios baseURL is already `/api` (`src/api/index.ts`), so the actual URL becomes `/api/api/admin/...`; the 5 calls in UserManage.vue / AuditLog.vue are likely to 404.
- **Deeper architectural issue (confirmed in vue-dev final report)**: the admin backend (8789) itself provides 12 local routes (`/api/admin/login`, `me`, `logout`, `users` CRUD, `roles`, `audit-logs`, `/api/install/*`), but:
  - `docker/nginx/admin.conf`'s `location /api/` **proxies everything** to `service_api` (php:8788);
  - the `upstream admin_backend` (admin-php:8789) is defined, but **no location references it** → in production `/api/admin/*` can never reach 8789;
  - the Vite dev proxy likewise points all `/api` at 8788.
  - Conclusion: even with the double prefix fixed, `/api/admin/*` would still 404 — the admin backend's local routes are not wired into the production chain.

### Decision point (needs confirmation from backend-dev + vue-dev + devops)
- Option A (recommended): vue-dev changes `admin.ts` paths to relative `/admin/users`, `/admin/audit-logs`, and **devops adds `location /api/admin/` → `proxy_pass http://admin_backend` in Nginx** (placed before `location /api/`; exact prefix wins), so admin-specific routes are served directly by 8789 while business routes still go through 8788
- Option B: backend-dev adds `/api/admin/*` routes to the service (overlaps with Admin-side responsibility, not recommended)
- Option C: business queries also switch to ServiceProxy (requires wiring, largest change; only consider if unified Admin-side auth is needed)

### Files:
- Modify: `admin/public/web/src/api/admin.ts` (remove `/api` prefix)
- Modify: `docker/nginx/admin.conf` (add `location /api/admin/` → admin_backend upstream)
- Modify: `admin/public/web/vite.config.ts` (add `/api/admin` → 8789 rule to dev proxy, before `/api`)
- Verify: admin backend routes in `admin/config/route.php` (/api/admin/users etc.) match frontend calls

### Acceptance
- [x] Frontend request paths match actually existing backend routes (no 404) — all 9 methods in admin.ts cross-checked against route.php ✅, vue-tsc passes
- [x] Both Nginx and Vite correctly route `/api/admin/*` to 8789 and the rest of `/api/*` to 8788 — Nginx gained `location /api/admin/`, Vite gained `/api/admin` proxy (before `/api`) ✅
- [x] UserManage / AuditLog pages functional — paths aligned (including the listRoles → `/admin/users/roles` decision) ✅

---

## Task 3: `/system/info` without a route + ServiceProxy decision

### Background
- `SystemInfo.vue` / `stores/admin.ts` call `GET /api/system/info`; the service has no such route (only /health, /ping), the 404 is swallowed by try/catch
- `admin/app/controller/ServiceProxy.php` is defined but has 0 active callers across the repo ("defined but not wired up")

### Decision point
- `/system/info`: Option A — frontend switches to calling `/health` (already exists in the service); Option B — backend-dev adds a `/api/system/info` endpoint to the service (returns version/environment info, also useful for HarmonyOS/Flutter; recommended)
- ServiceProxy: Option A — wire it to the admin-specific APIs the admin needs (e.g. audit log forwarding); Option B — delete the class and update docs to state "Admin connects directly to the service" (the current actual architecture)

### Executed (2026-08-16)
- **`/system/info` → Option A (frontend switches to `/health`)**: SystemInfo.vue now calls `GET /health` with native axios, judging `checks.database === 'ok'`; the `/health` route on the service side has no `/api` prefix, Vite already gained a `/health` proxy, and Nginx already had `location /health`; dead code in `stores/admin.ts` synced to `/health` ✅
- **ServiceProxy → Option B (keep + document)**: the class is kept as reserved infrastructure (`ServiceProxy::init()` self-initializes harmlessly), `admin/config/app.php` comment updated to "reserved infrastructure, no active callers currently" ✅

### Acceptance
- [x] `/system/info` decision landed: frontend call removed (switched to /health), no 404 ghost requests ✅
- [x] ServiceProxy decision landed: class kept, current state documented in the config comment ✅

---

## Task 4: Documentation backfill and unified wording

### Background
- README "14 controllers / 45+ endpoints" is outdated (actual: 17 controllers / 61 endpoints)
- `docs/superpowers/plans/` phase checkboxes not backfilled (code implemented but docs not checked)
- HarmonyOS status "UI in planning" outdated (actual: 6 pages + ApiClient ready)
- install.html / InstallController default `.../api/v1` inconsistent with config default `/api` (X-API-Version header)
- CacheService comment says two-level cache, but it is actually three-level (L1 memory / APCu / Redis)

### Files:
- Modify: `README.md` / `README.en.md` (controller count, endpoint count, HarmonyOS status, cache levels)
- Modify: `admin/public/install.html` / `admin/app/controller/InstallController.php` (unify version prefix wording)
- Modify: `service/support/CacheService.php` (fix comment)
- Optional: backfill `docs/superpowers/plans/*.md` checkboxes

### Executed (2026-08-16)
- README.md / README.en.md: 17 controllers / 61 endpoints / HarmonyOS 6 pages / 19 Vue pages / SPA direct-connection wording all updated ✅
- install.html / InstallController: `/api/v1` default value → `/api` (X-API-Version header mechanism) ✅
- All 8 phase plan checkboxes backfilled ✅ (phase7 excepted, pending execution)

### Acceptance
- [x] README data consistent with code (17 controllers / 61 endpoints / HarmonyOS 6 pages) ✅
- [x] Install wizard version prefix consistent with X-API-Version mechanism ✅

---

## Subsequent Phase Planning (Phase 8-10, outside this plan)

| Phase | Content | Status |
|---|---|---|
| Phase 8 | Alert multi-channel implementation: ads-alert adds channel/ (Email SMTP, Webhook, SMS gateway placeholder) — fills the Phase 5 leftover gap | Not started |
| Phase 9 | HarmonyOS real integration: 6 pages connect to ApiClient (currently 0 real calls, all mock data) | Not started |
| Phase 10 | Deepening and commercialization: 29-platform real integration, sync status visualization, conversion data loop, Flutter/HarmonyOS CI packaging, multi-tenant SaaS quotas | Not started |
