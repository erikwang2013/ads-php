# Phase 9: HarmonyOS Real Integration Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**Goal:** Switch the HarmonyOS client's 6 pages from mock data to real API calls (service :8788), fix the ApiClient baseUrl hardcoding issue, make login real, and turn the HarmonyOS client into a usable third client.

**Source:** Phase 7 team audit (mobile-dev inventory: all 6 HarmonyOS pages use mock data, 0 real calls, ApiClient baseUrl hardcoded to `http://127.0.0.1:8788/api`)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## Current State (verified)

| Component | Status |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login complete; baseUrl hardcoded `http://127.0.0.1:8788/api` (Flutter uses same-origin relative `/api`); login() has no callers |
| `pages/LoginPage.ets` | Mock login (setTimeout 1s then navigate), comment "replace with actual API call" |
| `pages/DashboardPage.ets` | `@State` hardcoded metrics (totalCost=1250000 etc.) |
| `pages/CampaignListPage.ets` | L187 comment placeholder `/campaigns` |
| `pages/AccountPage.ets` | L138 comment placeholder `/accounts` |
| `pages/AlertPage.ets` | L146 comment placeholder `/alerts` |
| `pages/ReportPage.ets` | L242 comment placeholder `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric already exist |
| i18n | StringResources.ets (15+ keys) |

## Task 1: ApiClient enhancement

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### Design points
- **Make baseUrl configurable**: keep setBaseUrl, default value remains `http://127.0.0.1:8788/api` (real devices/emulators need a LAN address, note in comments); avoid Flutter-style same-origin relative paths (ArkTS requires absolute URLs)
- **Fix duplicate replayHeaders bug**: `{ ...this.replayHeaders(), ...this.replayHeaders() }` duplicate spread (inside the get method) → single spread
- **Adapt login() return value**: service `POST /api/auth/login` returns `{access_token, token_type, expires_in, user}` (check against actual fields of `service/plugin/ads-api/controller/v1/AuthController.php` — it is access_token, not token; verify and fix the `data.token` check)
- **Error handling**: throw/return a clear error message when resp.responseCode is not 2xx; guard JSON.parse failures
- Keep the existing convention of get/post/put/delete returning `data.data` (ApiResponse unwrap)

## Task 2: LoginPage real login

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### Design points
- `handleLogin()` calls `ApiClient.login(username, password)`; on success → setToken + navigate to Dashboard; on failure → toast error message
- The loading state isLoading already exists, reuse it
- Prefer the service's returned message for errors (ApiResponse envelope), fall back to generic copy

## Task 3: Realify five business pages

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`, `CampaignListPage.ets`, `AccountPage.ets`, `AlertPage.ets`, `ReportPage.ets`

### Endpoint mapping (confirmed by the Phase 7 audit, consistent with the fixed Flutter)
| Page | Call | Parsing |
|---|---|---|
| DashboardPage | `GET /reports/summary` (today range) | `data.overview` → totalCost/total_impressions/avg_ctr etc. (amounts in fen, formatFen already exists) |
| CampaignListPage | `GET /campaigns` | `data.list` (paginated) → Campaign model |
| AccountPage | `GET /accounts` | `data.list` → PlatformAccount model |
| AlertPage | `GET /alerts/logs` | `data.list` → AlertLog fields (metric/rule_name/current_value/condition/threshold/status) |
| ReportPage | `GET /reports/custom` (date_start/date_end/dimensions[]/metrics[]) | `data.list` → ReportMetric |

### Design points
- Page load (aboutToAppear) triggers the request; @State data initialized to empty/0 to avoid leftover mock values
- Loading failures show an error + retry (reference the Flutter pages' error/retry pattern)
- Money unit: service returns numbers in fen, formatFen already handles it
- **No new files**, keep each page's existing UI structure and i18n

## Task 4: Verification

### Acceptance
- [ ] ApiClient has no duplicate replayHeaders, login return fields consistent with AuthController
- [ ] No hardcoded mock business data remains in the 6 pages (grep verification)
- [ ] The 5 business pages' call paths map one-to-one to service routes (check against `service/plugin/ads-api/config/route.php`)
- [ ] ArkTS syntax check (run if this environment has the hvigor/DevEco toolchain; otherwise note it and manually verify)
- [ ] Regression: service PHPUnit unaffected
