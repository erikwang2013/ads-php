# وثيقة تصميم الوظائف

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> جميع تعريفات واجهات API (الطلبات/الاستجابات/المعاملات) في [api.ar.md](api.ar.md).

---

## نظرة عامة على الوحدات

| # | الوحدة | وحدة التحكم/الخدمة | عدد مسارات API | صفحات Vue |
|---|------|--------|-----------|----------|
| 1 | المصادقة والتفويض | AuthController | 3 | LoginPage |
| 2 | إدارة المنصات | PlatformController | 3 | — |
| 3 | إدارة الحسابات | AccountController | 5 | AccountList, AccountBind |
| 4 | خطط الإعلانات | CampaignController | 6 | CampaignList |
| 5 | المجموعات الإعلانية | AdGroupController | 5 | AdGroupList |
| 6 | المواد الإبداعية | CreativeController | 2 | CreativeList |
| 7 | تقارير البيانات | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | مراقبة التنبيهات | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | مركز الإشعارات | NotificationController | 4 | NotificationList |
| 10 | المزايدة التلقائية | BidRuleController | 5 | BidRuleList |
| 11 | قوالب الاستهداف | TargetingTemplateController | 5 | — |
| 12 | إدارة النظام | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | مزامنة البيانات | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | مكتبة المواد | AssetController | 4 | AssetGallery |
| 15 | تنبيه الميزانية | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | تقويم النشر | CalendarService | 1 | CampaignCalendar |
| 17 | الإسناد عبر المنصات | AttributionEngine | 2 | AttributionReport |
| 18 | فحص الصحة | HealthController | 2 | — |
| 19 | رمز التحقق | CaptchaController | 2 | — |
| 20 | وثائق API | DocController | 1 | — |

**الإجمالي**: 20 وحدة، 65+ مسارًا، 18 صفحة Vue

---

## الوحدة 1: المصادقة والتفويض

- فحص رمز التحقق (اختياري)
- الاستعلام عن جدول `admin_users`
- تحقق bcrypt عبر `password_verify()`
- توليد JWT Token (صلاحية 24 ساعة)
- إضافة الرموز القديمة تلقائيًا إلى قائمة الحظر
- استخراج `uid` من Token والاستعلام عن معلومات المستخدم

الواجهات: تسجيل الدخول / تحديث Token / المستخدم الحالي ← [api.ar.md الوحدة 2](api.ar.md#模块-2-认证)

---

## الوحدتان 2-3: إدارة المنصات والحسابات

- تخزين قائمة المنصات مؤقتًا ساعة واحدة (Redis)، مع تكامل رموز أعلام Season
- تدفق OAuth: توليد state عشوائي ← بناء URL التفويض ← معالجة الاستدعاء ← تخزين Token
- تخزين قائمة/تفاصيل الحسابات مؤقتًا 5 دقائق

الواجهات: قائمة المنصات / OAuth / CRUD الحسابات + المزامنة ← [api.ar.md الوحدة 3](api.ar.md#模块-3-平台--账户)

---

## الوحدات 4-6: مستويات نشر الإعلانات

### بنية البيانات

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- إنشاء الخطط عبر محولات المنصات + الكتابة محليًا
- دعم التصفية حسب المنصة/الحالة/الكلمة المفتاحية، والقائمة تشمل ملخص اليوم
- يدعم إنشاء المجموعات الإعلانية تحميل قالب استهداف عبر `targeting_template_id`

الواجهات: الخطط / المجموعات الإعلانية / المواد ← [api.ar.md الوحدات 4-6](api.ar.md#模块-4-广告计划)

---

## الوحدة 7: تقارير البيانات

- تخزين ملخص لوحة المعلومات مؤقتًا 5 دقائق: 8 بطاقات مقاييس KPI + مخطط خطي لاتجاه اليوم + مخطط أعمدة حسب المنصة
- أبعاد التقارير المخصصة: date, platform, campaign
- المقاييس: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- صيغ التصدير: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (طباعة HTML)

الواجهات: الملخص / المخصص / التصدير ← [api.ar.md الوحدة 7](api.ar.md#模块-7-报表)

---

## الوحدة 8: مراقبة التنبيهات

### تدفق تقييم AlertEngine

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### قنوات الإشعارات

| القناة | الحالة | التنفيذ |
|------|------|------|
| web | ✅ | الكتابة في erik_notifications |
| email | عنصر نائب | echo stub |
| sms | عنصر نائب | echo stub |
| Redis pub/sub | ✅ | دفع JSON لقناة `alert:new` |

الواجهات: CRUD القواعد / سجلات التنبيه / التأكيد / عدد غير المقروء ← [api.ar.md الوحدة 8](api.ar.md#模块-8-告警)

---

## الوحدة 9: مركز الإشعارات

- استطلاع دوري 30 ثانية عبر Pinia store في الواجهة الأمامية
- أيقونة الجرس في الشريط الجانبي + شارة رقم غير المقروء

الواجهات: القائمة / عدد غير المقروء / تعليم كمقروء / تعليم الكل كمقروء ← [api.ar.md الوحدة 9](api.ar.md#模块-9-通知)

---

## الوحدة 10: محرك المزايدة التلقائية

### تدفق تقييم BidEngine

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

### حقول القاعدة

| الحقل | النوع | الوصف |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | المقياس المراقب |
| condition | gt/gte/lt/lte | شرط التشغيل |
| threshold | DECIMAL(12,2) | العتبة |
| scope | tenant/platform/campaign | نطاق التأثير |
| action_type | adjust_budget/toggle_pause/toggle_enable | الإجراء |
| adjust_step | INT (فن) | خطوة تعديل الميزانية (موجب=زيادة، سالب=نقصان) |
| budget_min, budget_max | BIGINT | حدود الميزانية |
| cooldown_minutes | INT | فترة التهدئة |

الواجهات: CRUD القواعد / سجل المزايدة ← [api.ar.md الوحدة 10](api.ar.md#模块-10-自动出价)

---

## الوحدة 11: قوالب استهداف الجمهور

### التكامل مع المجموعات الإعلانية

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### مخطط JSON العام

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

الواجهات: CRUD القوالب ← [api.ar.md الوحدة 11](api.ar.md#模块-11-定向模板)

---

## الوحدة 12: إدارة النظام (Admin)

- ترميز معرفات قائمة المستخدمين عبر hashids
- تجزئة كلمات مرور المستخدمين الجدد عبر bcrypt
- تعطيل المستخدم تعطيلًا ناعمًا (status=0)

حقول سجل التدقيق: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

الواجهات: إدارة المستخدمين / سجل التدقيق / الأدوار ← [api.ar.md نقطة نهاية Admin](api.ar.md#admin-端点端口-8789)

---

## الوحدة 13: مزامنة البيانات

### تدفق DataSyncTask (كل 10 دقائق)

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

## تنسيق الاستجابة

### النجاح
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### الترقيم
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### الأخطاء
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## الوحدة 14: مكتبة المواد الإعلانية

- الأنواع المدعومة: image/jpeg, image/png, image/gif, image/webp, video/mp4
- تخزين الملفات: `public/uploads/assets/`
- الواجهة الأمامية: معرض شبكي + رفع بالسحب والإفلات + معاينة الصور + تشغيل الفيديو + نسخ URL

الواجهات: الرفع / القائمة / التفاصيل / الحذف ← [api.ar.md الوحدة 12](api.ar.md#模块-12-素材库)

---

## الوحدة 15: تنبيه الميزانية

- تنبيه ثلاثي المراحل: yellow (≥50%), orange (≥80%), red (≥100%)
- تنفيذ BudgetCheckTask كل 15 دقيقة
- إزالة التكرار: إشعار واحد فقط يوميًا لنفس الخطة ونفس المستوى
- الكتابة في جدول `erik_notifications`

الواجهات: تنبيه الميزانية ← [api.ar.md الوحدة 7](api.ar.md#模块-7-报表)

---

## الوحدة 16: تقويم النشر

- تجميع جداول الحملات حسب التاريخ
- مخطط Gantt في الواجهة الأمامية: محور x التواريخ، محور y الخطط، تمييز الألوان حسب المنصة
- دعم التبديل بين العرض الشهري/الأسبوعي

الواجهات: تقويم النشر ← [api.ar.md الوحدة 7](api.ar.md#模块-7-报表)

---

## الوحدة 17: الإسناد عبر المنصات

### نماذج الإسناد

| النموذج | الخوارزمية |
|------|------|
| first_touch | أول نقطة اتصال 100% |
| last_touch | آخر نقطة اتصال 100% |
| linear | توزيع متساوٍ على جميع نقاط الاتصال (1/N) |
| time_decay | e^(-λ×Δt)، نصف عمر 7 أيام |
| position_based | أول 40% + آخر 40% + الوسط 20% |

- نافذة الرجوع: 30 يومًا
- مصدر نقاط الاتصال: `erik_report_metrics` (نقرات > 0)
- كتابة النتائج في `erik_attribution_results`
- الواجهة الأمامية: تبديل النماذج في AttributionReport.vue + بطاقات إحصائية + مخطط أعمدة ECharts + جدول تفاصيل

### جدول البيانات

| الجدول | الحقول |
|----|------|
| `erik_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `erik_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

الواجهات: تحليل الإسناد / قائمة النماذج ← [api.ar.md الوحدة 7](api.ar.md#模块-7-报表)

### فحص الصحة
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```
