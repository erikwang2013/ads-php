# Feature Design Document

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> All API definitions (requests/responses/parameters) are in [api.en.md](api.en.md).

---

## Module Overview

| # | Module | Controller/Service | API Routes | Vue Pages |
|---|--------|--------------------|------------|-----------|
| 1 | Auth | AuthController | 3 | LoginPage |
| 2 | Platform Management | PlatformController | 3 | — |
| 3 | Account Management | AccountController | 5 | AccountList, AccountBind |
| 4 | Campaigns | CampaignController | 6 | CampaignList |
| 5 | Ad Groups | AdGroupController | 5 | AdGroupList |
| 6 | Creatives | CreativeController | 2 | CreativeList |
| 7 | Reporting | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | Alert Monitoring | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | Notification Center | NotificationController | 4 | NotificationList |
| 10 | Auto-Bidding | BidRuleController | 5 | BidRuleList |
| 11 | Targeting Templates | TargetingTemplateController | 5 | — |
| 12 | System Management | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | Data Sync | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | Asset Library | AssetController | 4 | AssetGallery |
| 15 | Budget Alerts | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | Campaign Calendar | CalendarService | 1 | CampaignCalendar |
| 17 | Cross-Platform Attribution | AttributionEngine | 2 | AttributionReport |
| 18 | Health Check | HealthController | 2 | — |
| 19 | CAPTCHA | CaptchaController | 2 | — |
| 20 | API Docs | DocController | 1 | — |

**Total**: 20 modules, 65+ routes, 18 Vue pages

---

## Module 1: Authentication

- CAPTCHA check (optional)
- Query the `admin_users` table
- bcrypt `password_verify()` verification
- JWT token generation (24h TTL)
- Old tokens automatically blacklisted
- Extract `uid` from token to query user info

API: Login / Token Refresh / Current User → [api.md Module 2](api.en.md#模块-2-认证)

---

## Modules 2-3: Platform & Account Management

- Platform list cached for 1 hour (Redis), integrated with Season flag emoji
- OAuth flow: generate random state → build authorization URL → callback handling → store token
- Account list/detail cached for 5 minutes

API: Platform list / OAuth / Account CRUD + sync → [api.md Module 3](api.en.md#模块-3-平台--账户)

---

## Modules 4-6: Ad Delivery Hierarchy

### Data Structure

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- Campaign creation via platform adapter + local write
- Filtering by platform/status/keyword, list includes today's summary
- Ad group creation supports `targeting_template_id` to load targeting templates

API: Campaigns / Ad Groups / Creatives → [api.md Modules 4-6](api.en.md#模块-4-广告计划)

---

## Module 7: Reporting

- Dashboard summary cached 5 min: 8 KPI metric cards + daily trend line chart + platform bar chart
- Custom report dimensions: date, platform, campaign
- Metrics: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Export formats: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (HTML print)

API: Summary / Custom / Export → [api.md Module 7](api.en.md#模块-7-报表)

---

## Module 8: Alert Monitoring

### AlertEngine Evaluation Flow

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### Notification Channels

| Channel | Status | Implementation |
|---------|--------|----------------|
| web | ✅ | Write to `erik_notifications` |
| email | placeholder | echo stub |
| sms | placeholder | echo stub |
| Redis pub/sub | ✅ | JSON push on `alert:new` channel |

API: Rule CRUD / alert logs / acknowledge / unread count → [api.md Module 8](api.en.md#模块-8-告警)

---

## Module 9: Notification Center

- Frontend Pinia store polls every 30s
- Sidebar bell icon + unread count badge

API: List / unread count / mark read / mark all read → [api.md Module 9](api.en.md#模块-9-通知)

---

## Module 10: Auto-Bidding Engine

### BidEngine Evaluation Flow

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 冷却检查 (cooldown_minutes 内是否有过操作)
  → 执行动作:
    - adjust_budget: 新预算 = current + adjust_step, 限制 [budget_min, budget_max]
    - toggle_pause: 暂停计划
    - toggle_enable: 启用计划
  → 通过 AdapterRegistry → PlatformAdapter 调用平台 API
  → 更新本地 DB + 写入 BidLog
```

### Rule Fields

| Field | Type | Description |
|-------|------|-------------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Monitored metric |
| condition | gt/gte/lt/lte | Trigger condition |
| threshold | DECIMAL(12,2) | Threshold |
| scope | tenant/platform/campaign | Scope |
| action_type | adjust_budget/toggle_pause/toggle_enable | Action |
| adjust_step | INT (cents) | Budget adjustment step (positive = increase, negative = decrease) |
| budget_min, budget_max | BIGINT | Budget bounds |
| cooldown_minutes | INT | Cooldown period |

API: Rule CRUD / bid history → [api.md Module 10](api.en.md#模块-10-自动出价)

---

## Module 11: Audience Targeting Templates

### Integration into Ad Groups

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### Common JSON Schema

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

API: Template CRUD → [api.md Module 11](api.en.md#模块-11-定向模板)

---

## Module 12: System Management (Admin)

- User list IDs encoded with hashids
- User creation hashes passwords with bcrypt
- Disabled users are soft-disabled (status=0)

Audit log fields: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

API: User management / audit logs / roles → [api.md Admin Endpoints](api.en.md#admin-端点端口-8789)

---

## Module 13: Data Sync

### DataSyncTask Flow (every 10 minutes)

```
遍历 sync_enabled=1 的账户
  → 获取平台适配器
  → 同步 Campaigns (fetchCampaigns → updateOrInsert)
  → 同步 AdGroups (fetchAdGroups → 遍历每 campaign)
  → 同步 Creatives (fetchCreatives → 遍历每 ad_group)
  → 同步 Reports (fetchReports → 过去 2 天 daily, 9 个指标)
  → 清除 Dashboard 缓存
  → 更新 last_sync_at
```

---

## Response Format

### Success
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### Pagination
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### Error
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## Module 14: Ad Asset Library

- Supported types: image/jpeg, image/png, image/gif, image/webp, video/mp4
- File storage: `public/uploads/assets/`
- Frontend: grid gallery + drag-and-drop upload + image preview + video playback + copy URL

API: Upload / list / detail / delete → [api.md Module 12](api.en.md#模块-12-素材库)

---

## Module 15: Budget Alerts

- 3-tier alerts: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask runs every 15 minutes
- Deduplication: same campaign, same level, only notified once per day
- Written to the `erik_notifications` table

API: Budget alerts → [api.md Module 7](api.en.md#模块-7-报表)

---

## Module 16: Campaign Calendar

- Aggregates campaign schedules by date
- Frontend Gantt chart: x-axis dates, y-axis campaigns, color-coded by platform
- Month/week view switching

API: Campaign calendar → [api.md Module 7](api.en.md#模块-7-报表)

---

## Module 17: Cross-Platform Attribution

### Attribution Models

| Model | Algorithm |
|-------|-----------|
| first_touch | First touchpoint gets 100% |
| last_touch | Last touchpoint gets 100% |
| linear | All touchpoints share equally (1/N) |
| time_decay | e^(-λ×Δt), 7-day half-life |
| position_based | First 40% + last 40% + middle 20% |

- Lookback window: 30 days
- Touchpoint source: `erik_report_metrics` (clicks > 0)
- Results written to `erik_attribution_results`
- Frontend: AttributionReport.vue model switcher + stat cards + ECharts bar chart + detail table

### Data Tables

| Table | Fields |
|-------|--------|
| `erik_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `erik_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

API: Attribution analysis / model list → [api.md Module 7](api.en.md#模块-7-报表)

### Health Check
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```
