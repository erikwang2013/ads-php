# ফিচার ডিজাইন ডকুমেন্ট

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> সব API ইন্টারফেস ডেফিনিশন (রিকোয়েস্ট/রেসপন্স/প্যারামিটার) দেখুন [api.bn.md](api.bn.md)。

---

## মডিউল ওভারভিউ

| # | মডিউল | কন্ট্রোলার/সার্ভিস | API রাউট সংখ্যা | Vue পেজ |
|---|------|--------|-----------|----------|
| 1 | অথেনটিকেশন ও অথরাইজেশন | AuthController | 3 | LoginPage |
| 2 | প্ল্যাটফর্ম ম্যানেজমেন্ট | PlatformController | 3 | — |
| 3 | অ্যাকাউন্ট ম্যানেজমেন্ট | AccountController | 5 | AccountList, AccountBind |
| 4 | বিজ্ঞাপন প্ল্যান | CampaignController | 6 | CampaignList |
| 5 | বিজ্ঞাপন গ্রুপ | AdGroupController | 5 | AdGroupList |
| 6 | বিজ্ঞাপন ক্রিয়েটিভ | CreativeController | 2 | CreativeList |
| 7 | ডেটা রিপোর্ট | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | অ্যালার্ট মনিটরিং | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | নোটিফিকেশন সেন্টার | NotificationController | 4 | NotificationList |
| 10 | অটো বিডিং | BidRuleController | 5 | BidRuleList |
| 11 | টার্গেটিং টেমপ্লেট | TargetingTemplateController | 5 | — |
| 12 | সিস্টেম ম্যানেজমেন্ট | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | ডেটা সিঙ্ক | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | অ্যাসেট লাইব্রেরি | AssetController | 4 | AssetGallery |
| 15 | বাজেট অ্যালার্ট | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | ক্যাম্পেইন ক্যালেন্ডার | CalendarService | 1 | CampaignCalendar |
| 17 | ক্রস-প্ল্যাটফর্ম অ্যাট্রিবিউশন | AttributionEngine | 2 | AttributionReport |
| 18 | হেলথ চেক | HealthController | 2 | — |
| 19 | ক্যাপচা | CaptchaController | 2 | — |
| 20 | API ডকুমেন্টেশন | DocController | 1 | — |

**মোট**: 20 মডিউল, 65+ রাউট, 18টি Vue পেজ

---

## মডিউল 1: অথেনটিকেশন ও অথরাইজেশন

- ক্যাপচা চেক (ঐচ্ছিক)
- `admin_users` টেবিল কোয়েরি
- bcrypt `password_verify()` ভ্যালিডেশন
- JWT Token জেনারেশন (24h TTL)
- পুরনো Token অটো ব্ল্যাকলিস্ট
- Token থেকে `uid` এক্সট্রাক্ট করে ইউজার তথ্য কোয়েরি

ইন্টারফেস: লগইন / Token রিফ্রেশ / বর্তমান ইউজার → [api.bn.md মডিউল 2](api.bn.md#模块-2-认证)

---

## মডিউল 2-3: প্ল্যাটফর্ম ও অ্যাকাউন্ট ম্যানেজমেন্ট

- প্ল্যাটফর্ম লিস্ট 1 ঘণ্টা ক্যাশ (Redis), Season ফ্ল্যাগ emoji ইন্টিগ্রেশন
- OAuth ফ্লো: র্যান্ডম state জেনারেশন → অথরাইজেশন URL বিল্ড → কলব্যাক হ্যান্ডলিং → Token স্টোরেজ
- অ্যাকাউন্ট লিস্ট/ডিটেইল 5 মিনিট ক্যাশ

ইন্টারফেস: প্ল্যাটফর্ম লিস্ট / OAuth / অ্যাকাউন্ট CRUD + সিঙ্ক → [api.bn.md মডিউল 3](api.bn.md#模块-3-平台--账户)

---

## মডিউল 4-6: বিজ্ঞাপন ডেলিভারি হায়ারার্কি

### ডেটা স্ট্রাকচার

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- প্ল্যান তৈরি প্ল্যাটফর্ম অ্যাডাপ্টার + লোকাল রাইট
- প্ল্যাটফর্ম/স্ট্যাটাস/কীওয়ার্ড ফিল্টার সাপোর্ট, লিস্টে আজকের সামারি
- অ্যাড গ্রুপ তৈরি `targeting_template_id` দিয়ে টার্গেটিং টেমপ্লেট লোড

ইন্টারফেস: প্ল্যান / অ্যাড গ্রুপ / ক্রিয়েটিভ → [api.bn.md মডিউল 4-6](api.bn.md#模块-4-广告计划)

---

## মডিউল 7: ডেটা রিপোর্ট

- ড্যাশবোর্ড সামারি 5 মিনিট ক্যাশ: 8টি KPI মেট্রিক কার্ড + দৈনিক ট্রেন্ড লাইন চার্ট + প্ল্যাটফর্ম বার চার্ট
- কাস্টম রিপোর্ট ডাইমেনশন: date, platform, campaign
- মেট্রিক: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- এক্সপোর্ট ফরম্যাট: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (HTML প্রিন্ট)

ইন্টারফেস: সামারি / কাস্টম / এক্সপোর্ট → [api.bn.md মডিউল 7](api.bn.md#模块-7-报表)

---

## মডিউল 8: অ্যালার্ট মনিটরিং

### AlertEngine ইভালুয়েশন ফ্লো

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### নোটিফিকেশন চ্যানেল

| চ্যানেল | স্ট্যাটাস | বাস্তবায়ন |
|------|------|------|
| web | ✅ | erik_notifications-এ লেখা |
| email | প্লেসহোল্ডার | echo স্টাব |
| sms | প্লেসহোল্ডার | echo স্টাব |
| Redis pub/sub | ✅ | `alert:new` চ্যানেলে JSON পুশ |

ইন্টারফেস: রুল CRUD / অ্যালার্ট রেকর্ড / কনফার্ম / আনরিড কাউন্ট → [api.bn.md মডিউল 8](api.bn.md#模块-8-告警)

---

## মডিউল 9: নোটিফিকেশন সেন্টার

- ফ্রন্টএন্ড Pinia store 30s পোলিং
- সাইডবার বেল আইকন + আনরিড নম্বর ব্যাজ

ইন্টারফেস: লিস্ট / আনরিড কাউন্ট / রিড মার্ক / সব রিড → [api.bn.md মডিউল 9](api.bn.md#模块-9-通知)

---

## মডিউল 10: অটো বিডিং ইঞ্জিন

### BidEngine ইভালুয়েশন ফ্লো

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

### রুল ফিল্ড

| ফিল্ড | টাইপ | বিবরণ |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | মনিটরিং মেট্রিক |
| condition | gt/gte/lt/lte | ট্রিগার কন্ডিশন |
| threshold | DECIMAL(12,2) | থ্রেশহোল্ড |
| scope | tenant/platform/campaign | প্রযোজ্য সুযোগ |
| action_type | adjust_budget/toggle_pause/toggle_enable | অ্যাকশন |
| adjust_step | INT (ফেন) | বাজেট অ্যাডজাস্টমেন্ট স্টেপ (পজিটিভ=বাড়ে, নেগেটিভ=কমে) |
| budget_min, budget_max | BIGINT | বাজেট সীমা |
| cooldown_minutes | INT | কুলডাউন পিরিয়ড |

ইন্টারফেস: রুল CRUD / বিডিং হিস্টোরি → [api.bn.md মডিউল 10](api.bn.md#模块-10-自动出价)

---

## মডিউল 11: অডিয়েন্স টার্গেটিং টেমপ্লেট

### অ্যাড গ্রুপে ইন্টিগ্রেশন

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### সাধারণ JSON Schema

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

ইন্টারফেস: টেমপ্লেট CRUD → [api.bn.md মডিউল 11](api.bn.md#模块-11-定向模板)

---

## মডিউল 12: সিস্টেম ম্যানেজমেন্ট (Admin)

- ইউজার লিস্টে ID hashids এনকোডিং
- ইউজার তৈরি bcrypt হ্যাশ পাসওয়ার্ড
- ডিসেবলড ইউজার সফট ডিসেবল (status=0)

অডিট লগ ফিল্ড: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

ইন্টারফেস: ইউজার ম্যানেজমেন্ট / অডিট লগ / রোল → [api.bn.md Admin এন্ডপয়েন্ট](api.bn.md#admin-端点端口-8789)

---

## মডিউল 13: ডেটা সিঙ্ক

### DataSyncTask ফ্লো (প্রতি 10 মিনিট)

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

## রেসপন্স ফরম্যাট

### সফল
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### পেজিনেশন
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### এরর
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## মডিউল 14: বিজ্ঞাপন অ্যাসেট লাইব্রেরি

- সাপোর্টেড টাইপ: image/jpeg, image/png, image/gif, image/webp, video/mp4
- ফাইল স্টোরেজ: `public/uploads/assets/`
- ফ্রন্টএন্ড: গ্রিড গ্যালারি + ড্র্যাগ-ড্রপ আপলোড + ইমেজ প্রিভিউ + ভিডিও প্লে + URL কপি

ইন্টারফেস: আপলোড / লিস্ট / ডিটেইল / ডিলিট → [api.bn.md মডিউল 12](api.bn.md#模块-12-素材库)

---

## মডিউল 15: বাজেট অ্যালার্ট

- থ্রি-লেভেল অ্যালার্ট: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask প্রতি 15 মিনিটে এক্সিকিউট
- ডিডুপ্লিকেশন: একই প্ল্যান একই লেভেল দিনে একবারই নোটিফাই
- `erik_notifications` টেবিলে লেখা

ইন্টারফেস: বাজেট অ্যালার্ট → [api.bn.md মডিউল 7](api.bn.md#模块-7-报表)

---

## মডিউল 16: ক্যাম্পেইন ক্যালেন্ডার

- তারিখ অনুযায়ী campaign শিডিউল অ্যাগ্রিগেশন
- ফ্রন্টএন্ড Gantt চার্ট: x-অক্ষ তারিখ, y-অক্ষ প্ল্যান, প্ল্যাটফর্ম অনুযায়ী রঙ
- মাস/সপ্তাহ ভিউ সুইচ সাপোর্ট

ইন্টারফেস: ক্যাম্পেইন ক্যালেন্ডার → [api.bn.md মডিউল 7](api.bn.md#模块-7-报表)

---

## মডিউল 17: ক্রস-প্ল্যাটফর্ম অ্যাট্রিবিউশন

### অ্যাট্রিবিউশন মডেল

| মডেল | অ্যালগরিদম |
|------|------|
| first_touch | প্রথম টাচপয়েন্ট 100% |
| last_touch | শেষ টাচপয়েন্ট 100% |
| linear | সব টাচপয়েন্ট সমান ভাগ (1/N) |
| time_decay | e^(-λ×Δt), 7 দিন হাফ-লাইফ |
| position_based | প্রথম 40% + শেষ 40% + মাঝের 20% |

- লুকব্যাক উইন্ডো: 30 দিন
- টাচপয়েন্ট উৎস: `erik_report_metrics` (ক্লিক > 0)
- ফলাফল `erik_attribution_results`-এ লেখা
- ফ্রন্টএন্ড: AttributionReport.vue মডেল সুইচ + স্ট্যাট কার্ড + ECharts বার চার্ট + ডিটেইল টেবিল

### ডেটা টেবিল

| টেবিল | ফিল্ড |
|----|------|
| `erik_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `erik_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

ইন্টারফেস: অ্যাট্রিবিউশন অ্যানালাইসিস / মডেল লিস্ট → [api.bn.md মডিউল 7](api.bn.md#模块-7-报表)

### হেলথ চেক
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```
