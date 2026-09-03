# وثائق واجهات API

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **توثيق hg/apidoc عبر الإنترنت**: بعد تشغيل الخدمة افتح `http://127.0.0.1:8788/apidoc` (تبديل بين تطبيقي Service + Admin)  
> ملف الإعداد: `service/config/plugin/hg/apidoc/app.php`

---

## المواصفات العامة

### Base URL

```
http://your-domain.com/api/v1
```

> رقم إصدار API مثبَّت في مسار URL (حاليًا `v1`) ولا يُمرَّر في الترويسة؛ الإصدارات الرئيسية اللاحقة مثل `/api/v2` تتبع القاعدة نفسها.

### الترويسات الإلزامية

| الترويسة | القيمة | الوصف |
|--------|----|------|
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | طرف مصدر العملية (إلزامي) |
| `Authorization` | `Bearer <token>` | رمز مصادقة JWT (إلزامي عدا تسجيل الدخول/قائمة المنصات/فحص الصحة) |

### ترويسة منع إعادة التشغيل (لغير المتصفحات)

| الترويسة | الوصف |
|--------|------|
| `X-Nonce` | سلسلة عشوائية (فريدة لكل طلب) |
| `X-Timestamp` | طابع Unix زمني بالثواني (نافذة ±5 دقائق) |

### الترويسات الاختيارية

| الترويسة | الوصف |
|--------|------|
| `X-Tenant-Id` | معرّف المستأجر (نمط متعدد المستأجرين) |
| `X-Encrypted` | `1` = يجب فك تشفير جسم الطلب وتشفير جسم الاستجابة |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| القيمة | الوصف |
|----|------|
| `application/json` | جسم طلب JSON (موصى به) |
| `application/x-www-form-urlencoded` | طلب نموذج |
| `multipart/form-data` | رفع الملفات |

### تنسيق الاستجابة

**النجاح**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**الترقيم**:
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [ ... ],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

**الأخطاء**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**فحص الصحة**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### رموز حالة HTTP

| رمز الحالة | المعنى |
|--------|------|
| 200 | نجاح |
| 204 | نجاح الفحص المسبق OPTIONS |
| 400 | خطأ في معاملات الطلب، إصدار API غير مدعوم |
| 401 | غير مصادق، Token منتهي، عدم تطابق IP/UA الخاص بـ Token |
| 403 | وصول ممنوع (XSS/اجتياز المسار/CSRF/حقن SQL/عدم تطابق Origin) |
| 404 | المورد غير موجود |
| 429 | طلبات كثيرة (تحديد المعدل/تقييد تسجيل الدخول/حد الجلسات المتزامنة) |
| 500 | خطأ في الخادم |
| 503 | تدهور الخدمة (قاعدة البيانات أو Redis غير متاحين) |

### معاملات الترقيم

| المعامل | القيمة الافتراضية | الحد الأقصى | الوصف |
|------|--------|--------|------|
| `page` | 1 | — | رقم الصفحة |
| `per_page` | 20 | 100 | عدد العناصر لكل صفحة (يُقتطع تلقائيًا عند التجاوز) |
| `sort` | `id` | — | حقل الترتيب (يجب أن يكون ضمن القائمة المسموحة) |

### استراتيجية التخزين المؤقت

| نقطة النهاية | TTL | الطبقة |
|------|-----|-----|
| `/api/v1/platforms` | ساعة واحدة | L1 ذاكرة ← L2 APCu ← L3 Redis |
| `/api/v1/accounts` + `/api/v1/accounts/:id` | 5 دقائق | نفس ما سبق |
| `/api/v1/reports/summary` | 5 دقائق | نفس ما سبق |
| `/api/v1/alerts/rules` | دقيقتان | نفس ما سبق |
| `/api/v1/alerts/unread-count` | 30 ثانية | نفس ما سبق |

---

## الوحدة 1: النظام

### GET /health — فحص الصحة

```
GET /health
```

**الاستجابة**:
```json
{
  "status": "healthy",
  "timestamp": "2026-05-22T00:00:00+08:00",
  "checks": {
    "database": "ok",
    "redis": "ok"
  }
}
```

- `status`: `healthy` (200) أو `degraded` (503)
- لا يتطلب مصادقة، ولا يمر عبر توجيه الإصدارات

---

### GET /ping — فحص البقاء

```
GET /ping
```

**الاستجابة**: `{ "pong": true }`

---

### GET /docs — وثائق API

```
GET /docs
```

يُعيد صفحة وثائق API بصيغة HTML (بدون مصادقة).

---

### GET /api/v1/captcha/generate — توليد رمز التحقق

بدون مصادقة.

**الاستجابة**:
```json
{
  "code": 0,
  "data": {
    "captcha_token": "aes-encrypted-token",
    "background": "base64...",
    "puzzle": "base64..."
  }
}
```

- صلاحية token 5 دقائق
- تسامح الإزاحة 5px

---

### POST /api/v1/captcha/verify — التحقق من رمز التحقق

بدون مصادقة.

**الطلب**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**الاستجابة**: `{ "code": 0, "message": "تم التحقق بنجاح" }`

---

## الوحدة 2: المصادقة

### POST /api/v1/auth/login — تسجيل الدخول

بدون مصادقة.

**الطلب**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**الاستجابة**:
```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "username": "admin",
      "name": "超级管理员",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

- صلاحية JWT Token 24 ساعة
- يتضمّن Token تجزئة IP + User-Agent
- 5 محاولات فاشلة ← قفل في Redis لمدة 15 دقيقة

---

### GET /api/v1/auth/me — المستخدم الحالي

**ترويسة الطلب**: `Authorization: Bearer <token>`

**الاستجابة**:
```json
{
  "code": 0,
  "data": {
    "id": 1,
    "username": "admin",
    "name": "超级管理员",
    "email": "admin@example.com",
    "role": "admin",
    "tenant_id": 1
  }
}
```

---

### POST /api/v1/auth/refresh — تحديث Token

**ترويسة الطلب**: `Authorization: Bearer <old_token>`

**الاستجابة**:
```json
{
  "code": 0,
  "message": "Token 已刷新",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

- تُضاف الرموز القديمة تلقائيًا إلى قائمة الحظر
- بحد أقصى 3 رموز نشطة لكل مستخدم

---

## الوحدة 3: المنصات والحسابات

### GET /api/v1/platforms — قائمة المنصات

بدون مصادقة. تخزين مؤقت لمدة ساعة.

**الاستجابة**:
```json
{
  "code": 0,
  "data": [
    { "code": "juliang", "name": "巨量引擎", "flag": "🇨🇳", "capabilities": ["campaign", "report"] },
    { "code": "meta", "name": "Meta Ads", "flag": "🇺🇸", "capabilities": ["campaign", "report"] }
  ]
}
```

---

### GET /api/v1/platforms/:code/oauth-url — URL تفويض OAuth

**المعاملات**: `?redirect_uri=https://your-domain.com/callback`

**الاستجابة**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- يجب أن يجتاز `redirect_uri` التحقق من قائمة SSRF المسموحة (متغير البيئة `OAUTH_ALLOWED_REDIRECTS`)

---

### POST /api/v1/platforms/:code/callback — استدعاء OAuth

**الطلب**: `{ "state": "...", "code": "..." }`

**الاستجابة**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/v1/accounts — قائمة الحسابات

تخزين مؤقت لمدة 5 دقائق.

**المعاملات**:

| المعامل | الوصف |
|------|------|
| `platform` | تصفية برمز المنصة |
| `page` | رقم الصفحة |
| `per_page` | عدد العناصر لكل صفحة |

**الاستجابة**: تنسيق ترقيم، كل عنصر في list يتضمن `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at`

---

### GET /api/v1/accounts/:id — تفاصيل الحساب

تخزين مؤقت لمدة 5 دقائق.

---

### DELETE /api/v1/accounts/:id — إلغاء ربط الحساب

---

### POST /api/v1/accounts/:id/sync — مزامنة يدوية

---

## الوحدة 4: خطط الإعلانات

### GET /api/v1/campaigns — قائمة الخطط

**المعاملات**:

| المعامل | الوصف | القيم الممكنة |
|------|------|--------|
| `platform` | تصفية حسب المنصة | juliang, meta, google... |
| `status` | تصفية حسب الحالة | enabled, paused |
| `keyword` | بحث بالاسم | أي نص |
| `sort` | حقل الترتيب | id, name, platform, daily_budget, status, created_at |
| `page` | رقم الصفحة | — |
| `per_page` | عدد العناصر لكل صفحة | ≤100 |

**الاستجابة**: تنسيق ترقيم + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/v1/campaigns — إنشاء خطة

**الطلب**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**الاستجابة**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- وحدة `daily_budget`: الفن (20000 = ¥200.00)

---

### GET /api/v1/campaigns/:id — تفاصيل الخطة

**الاستجابة**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/v1/campaigns/:id — تحديث الخطة

**الطلب**: `{ "name": "الاسم الجديد", "daily_budget": 30000 }`

---

### POST /api/v1/campaigns/:id/toggle — تشغيل/إيقاف الخطة

**الطلب**: `{ "enabled": false }`

---

### POST /api/v1/campaigns/batch/toggle — تشغيل/إيقاف جماعي

**الطلب**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**الاستجابة**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## الوحدة 5: المجموعات الإعلانية

### GET /api/v1/ad-groups — قائمة المجموعات الإعلانية

**المعاملات**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/v1/ad-groups — إنشاء مجموعة إعلانية

**الطلب**:
```json
{
  "campaign_id": 1,
  "name": "测试广告组",
  "bid_amount": 100,
  "bid_type": "cpc",
  "targeting": { "age": { "min": 18, "max": 45 } },
  "targeting_template_id": "hashids-encoded-template-id"
}
```

- `targeting_template_id`: اختياري، يُحمَّل targeting JSON من قالب الاستهداف ويُدمج

### GET /api/v1/ad-groups/:id — تفاصيل المجموعة الإعلانية

### PUT /api/v1/ad-groups/:id — تحديث المجموعة الإعلانية

### POST /api/v1/ad-groups/:id/toggle — تشغيل/إيقاف المجموعة الإعلانية

---

## الوحدة 6: المواد الإبداعية

### GET /api/v1/creatives — قائمة المواد الإبداعية

**المعاملات**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/v1/creatives/:id — تفاصيل المادة الإبداعية

---

## الوحدة 7: التقارير

### GET /api/v1/reports/summary — ملخص لوحة المعلومات

تخزين مؤقت لمدة 5 دقائق.

**المعاملات**: `date_start`, `date_end`

**الاستجابة**:
```json
{
  "code": 0,
  "data": {
    "overview": { "cost": 123456, "impressions": 10000, ... },
    "by_platform": [ ... ],
    "daily": [ ... ]
  }
}
```

---

### GET /api/v1/reports/custom — تقرير مخصص

**المعاملات**:

| المعامل | الوصف |
|------|------|
| `dimensions[]` | الأبعاد: date, platform, campaign |
| `metrics[]` | المقاييس: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | تاريخ البداية |
| `date_end` | تاريخ النهاية |
| `platform` | تصفية حسب المنصة |

---

### GET /api/v1/reports/export — تصدير التقرير

**المعاملات**: `format=csv`, `date_start`, `date_end`, `metrics[]`

يُعيد تنزيل ملف (CSV UTF-8 BOM أو Excel .xls).

---

### GET /api/v1/reports/export-dashboard — تصدير لوحة المعلومات PDF

---

### GET /api/v1/reports/calendar — تقويم النشر

**المعاملات**: `date_start`, `date_end`, `platform`

**الاستجابة**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/v1/reports/budget-alerts — تنبيه الميزانية

**الاستجابة**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/v1/reports/attribution — تحليل الإسناد

**المعاملات**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**الاستجابة**:
```json
{
  "code": 0,
  "data": {
    "total_conversions": 42,
    "total_value": 123456.78,
    "by_campaign": [ { "campaign_id": 1, "credit": 5000.00 } ]
  }
}
```

---

### GET /api/v1/reports/attribution/models — قائمة نماذج الإسناد

**الاستجابة**: `[{ code: "last_touch", name: "آخر نقطة اتصال", description: "..." }]`

إجمالي 5 نماذج.

---

## الوحدة 8: التنبيهات

### GET /api/v1/alerts/rules — قائمة قواعد التنبيه

تخزين مؤقت لمدة دقيقتين.

**المعاملات**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/v1/alerts/rules — إنشاء قاعدة تنبيه

**الطلب**:
```json
{
  "name": "花费超限",
  "metric": "cost",
  "condition": "gt",
  "threshold": 100000,
  "scope": "tenant",
  "platform": null,
  "campaign_id": null,
  "channels": ["web"]
}
```

### PUT /api/v1/alerts/rules/:id — تحديث قاعدة تنبيه

### DELETE /api/v1/alerts/rules/:id — حذف قاعدة تنبيه

### GET /api/v1/alerts/logs — سجلات التنبيه

**المعاملات**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/v1/alerts/logs/:id/acknowledge — تأكيد التنبيه

### GET /api/v1/alerts/unread-count — عدد التنبيهات غير المقروءة

تخزين مؤقت لمدة 30 ثانية. استطلاع دوري كل 30 ثانية من الواجهة الأمامية.

---

## الوحدة 9: الإشعارات

### GET /api/v1/notifications — قائمة الإشعارات

**المعاملات**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/v1/notifications/unread-count — عدد الإشعارات غير المقروءة

### POST /api/v1/notifications/:id/read — تعليم كمقروء

### POST /api/v1/notifications/read-all — تعليم الكل كمقروء

---

## الوحدة 10: المزايدة التلقائية

### GET /api/v1/bid-rules — قائمة القواعد

### POST /api/v1/bid-rules — إنشاء قاعدة

**الطلب**:
```json
{
  "name": "ROI 达标加预算",
  "metric": "roi",
  "condition": "gte",
  "threshold": 3.0,
  "action_type": "adjust_budget",
  "adjust_step": 5000,
  "budget_min": 0,
  "budget_max": 100000,
  "cooldown_minutes": 60
}
```

**شرح الحقول**:

| الحقل | النوع | الوصف |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | المقياس المراقب |
| condition | gt/gte/lt/lte | شرط التشغيل |
| threshold | decimal | العتبة |
| action_type | adjust_budget/toggle_pause/toggle_enable | نوع الإجراء |
| adjust_step | int (فن) | خطوة تعديل الميزانية (موجب=زيادة، سالب=نقصان) |
| budget_min | int | الحد الأدنى للميزانية (فن) |
| budget_max | int | الحد الأقصى للميزانية (فن) |
| cooldown_minutes | int | فترة التهدئة (الافتراضي 60) |

### PUT /api/v1/bid-rules/:id — تحديث قاعدة

### DELETE /api/v1/bid-rules/:id — حذف قاعدة

### GET /api/v1/bid-rules/logs — سجل المزايدة

**المعاملات**: `rule_id`, `campaign_id`

---

## الوحدة 11: قوالب الاستهداف

### GET /api/v1/targeting-templates — قائمة القوالب

**المعاملات**: `platform`

### GET /api/v1/targeting-templates/:id — تفاصيل القالب

### POST /api/v1/targeting-templates — إنشاء قالب

**الطلب**:
```json
{
  "name": "核心受众",
  "platform": "",
  "targeting": {
    "age": { "min": 18, "max": 45 },
    "gender": "all",
    "interests": ["sports", "tech"],
    "devices": { "os": ["android", "ios"] }
  },
  "is_shared": 0
}
```

### PUT /api/v1/targeting-templates/:id — تحديث قالب

### DELETE /api/v1/targeting-templates/:id — حذف قالب

---

## الوحدة 12: مكتبة المواد

### GET /api/v1/assets — قائمة المواد

**المعاملات**: `type`(image/video), `page`, `per_page`

### POST /api/v1/assets/upload — رفع مادة

**الطلب**: `multipart/form-data`, الحقل `file`

- الصور: بحد أقصى 5 ميجابايت (jpeg/png/gif/webp)
- الفيديو: بحد أقصى 50 ميجابايت (mp4)

**الاستجابة**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

- عند تكوين CDN، يُجمَّع `url` مع `cdn_domain` للمزود الافتراضي ليكون عنوان HTTPS كاملًا

### POST /api/v1/assets/presign — الحصول على عنوان رفع موقّع مسبقًا

**الطلب**: `{ "filename": "demo.mp4", "mime_type": "video/mp4" }`

**الاستجابة**: `{ "code": 0, "data": { "key": "20260829/ab12...cd34.mp4", "upload_url": "https://signed-url", "expires_in": 3600, "url": "https://cdn.example.com/uploads/assets/..." } }`

- صيغة `key`: `Ymd/32hex.الامتداد`؛ يُعاد إلى `/api/v1/assets/register` بعد الرفع المباشر
- لفيديوهات حتى 50 MiB يرفع العميل مباشرة إلى تخزين الكائنات؛ غير متاح مع driver `local`

### POST /api/v1/assets/register — تسجيل مادة مرفوعة مباشرة

**الطلب**: `{ "key": "20260829/ab12...cd34.mp4", "filename": "demo.mp4", "mime_type": "video/mp4", "size": 52428800 }`

**الاستجابة**: `{ "code": 0, "message": "Created", "data": { "id": "hashids", "url": "https://cdn.example.com/uploads/assets/...", "type": "video" } }`

- تُتحقق صيغة `key` بصرامة (`^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$`) لمنع تجاوز المسار

### GET /api/v1/assets/:id — تفاصيل المادة

### DELETE /api/v1/assets/:id — حذف مادة

---

## نقطة نهاية Admin (المنفذ 8789)

### POST /api/v1/admin/login — تسجيل دخول المسؤول

**الطلب**: `{ "username": "admin", "password": "..." }`

**الاستجابة**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- يُخزَّن Token في localStorage
- يجب حمل `csrf_token` في ترويسة `X-CSRF-Token` لطلبات POST/PUT/DELETE اللاحقة

### GET /api/v1/admin/me — المسؤول الحالي

### POST /api/v1/admin/logout — تسجيل الخروج

### GET /api/v1/admin/users — قائمة المستخدمين

**المعاملات**: `keyword`, `role_id`, `page`, `per_page`

يُرمَّز `id` و`role_id` في الاستجابة عبر hashids.

### POST /api/v1/admin/users — إنشاء مستخدم

### PUT /api/v1/admin/users/:id — تحديث مستخدم

### DELETE /api/v1/admin/users/:id — تعطيل مستخدم

### GET /api/v1/admin/users/roles — قائمة الأدوار

### GET /api/v1/admin/audit-logs — سجلات التدقيق

**المعاملات**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

### إدارة مزودي CDN (للمستأجر الرئيسي فقط tenant 1، عبر AdminMiddleware)

### GET /api/v1/admin/cdn/providers — قائمة المزودين

### POST /api/v1/admin/cdn/providers — إنشاء مزود

**الطلب**: `{ "name": "Aliyun OSS", "driver": "oss", "bucket": "ads-assets", "region": "oss-cn-hangzhou", "endpoint": "https://oss-cn-hangzhou.aliyuncs.com", "access_key": "...", "secret_key": "...", "cdn_domain": "cdn.example.com", "cdn_driver": "aliyun", "cdn_token": "...", "is_default": 1 }`

- `driver`: `local` / `oss` (Alibaba Cloud OSS) / `cos` (Tencent Cloud COS، بروتوكول S3) / `s3` (متوافق S3: AWS S3 / Cloudflare R2 / MinIO)
- بيانات الاعتماد (access_key/secret_key/cdn_token) مشفرة على مستوى الحقل عبر Encryptable؛ الاستجابات بحقول مقنّعة فقط

### PUT /api/v1/admin/cdn/providers/:id — تحديث مزود

### DELETE /api/v1/admin/cdn/providers/:id — حذف (الافتراضي يُنقل تلقائيًا إلى المزود enabled التالي)

### PUT /api/v1/admin/cdn/providers/:id/default — تعيين كافتراضي

### PUT /api/v1/admin/cdn/providers/:id/toggle — تفعيل/تعطيل (تعطيل الافتراضي ينقله تلقائيًا)

### POST /api/v1/admin/cdn/providers/:id/test — اختبار الاتصال

**الاستجابة**: `{ "code": 0, "data": { "ok": true, "driver": "oss", "status": "ok" } }`

### POST /api/v1/admin/cdn/providers/:id/purge — مسح كاش CDN

**الطلب**: `{ "paths": ["/uploads/assets/20260829/xxx.mp4"] }`

- يتطلب `cdn_driver` و`cdn_domain`؛ `aliyun` مُنفَّذ فعليًا (توقيع OpenAPI)، cloudflare/cloudfront قيد التوسيع

---

## مرجع رموز الخطأ

| الرمز | HTTP | الوصف |
|------|------|------|
| 0 | 200 | نجاح |
| 1 | 200/400 | خطأ أعمال عام |
| 401 | 401 | غير مصادق / Token منتهي / عدم تطابق IP/UA |
| 403 | 403 | وصول ممنوع (اعتراض أمني) |
| 404 | 404 | المورد غير موجود |
| 422 | 422 | فشل التحقق من المعاملات |
| 429 | 429 | طلبات كثيرة / تقييد تسجيل الدخول / حد التزامن |
| 1001 | 200 | فشل المصادقة (اسم مستخدم أو كلمة مرور خاطئة) |

---

## استجابة الاعتراض الأمني

عند اعتراض الطلب من الوسيط الأمني، يُعاد 403:

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## استجابة تحديد المعدل

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

تحتوي ترويسة `Retry-After` على الثواني المتبقية للانتظار.
