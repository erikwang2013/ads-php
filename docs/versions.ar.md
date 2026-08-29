# مقارنة الإصدارات

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| الإصدار | الترخيص | طريقة الحصول |
|------|------|----------|
| **الإصدار المبسط (Lite)** | مفتوح المصدر (MIT) | مستودع GitHub عام |
| **الإصدار القياسي (Standard)** | ترخيص تجاري | تواصل مع erik@erik.xyz |
| **الإصدار الكامل (Full)** | ترخيص تجاري | تواصل مع erik@erik.xyz |

---

## مقارنة الوظائف

### الوظائف الأساسية

| الوظيفة | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| المصادقة (تسجيل الدخول/تحديث Token/المستخدم الحالي) | ✅ | ✅ | ✅ |
| إدارة المنصات (قائمة 29 منصة + OAuth) | ✅ | ✅ | ✅ |
| إدارة الحسابات (CRUD + المزامنة) | ✅ | ✅ | ✅ |
| خطط الإعلانات (CRUD + تشغيل/إيقاف + دفعات) | ✅ | ✅ | ✅ |
| التقارير (لوحة المعلومات + مخصص + تصدير CSV/Excel/PDF) | ✅ | ✅ | ✅ |
| فحص الصحة + وثائق API + رمز التحقق | ✅ | ✅ | ✅ |
| مزامنة البيانات (Campaign + Report) | ✅ | ✅ | ✅ |

### إدارة النشر

| الوظيفة | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| المجموعات الإعلانية (CRUD + تشغيل/إيقاف) | — | ✅ | ✅ |
| المواد الإبداعية (قائمة + تفاصيل) | — | ✅ | ✅ |
| مزامنة بيانات المجموعات/المواد | — | ✅ | ✅ |

### المراقبة والإشعارات

| الوظيفة | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| محرك قواعد التنبيه (7 مقاييس/4 شروط/3 نطاقات) | — | ✅ | ✅ |
| سجلات التنبيه + التأكيد + عدد غير المقروء | — | ✅ | ✅ |
| مركز الإشعارات (قائمة/مقروء/تعليم الكل كمقروء) | — | ✅ | ✅ |

### الميزات المتقدمة

| الوظيفة | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| محرك قواعد المزايدة التلقائية (3 إجراءات/تهدئة) | — | — | ✅ |
| قوالب استهداف الجمهور (مخطط JSON عام) | — | — | ✅ |
| مكتبة المواد الإعلانية (رفع/معرض/معاينة) | — | — | ✅ |
| تنبيه الميزانية (تنبيه ثلاثي المراحل 50/80/100%) | — | — | ✅ |
| تقويم النشر (تصوير Gantt) | — | — | ✅ |
| الإسناد عبر المنصات (5 نماذج/رجوع 30 يومًا) | — | — | ✅ |

---

## مقارنة الحماية الأمنية

| بند الحماية | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| قائمة CORS المسموحة | ✅ | ✅ | ✅ |
| ترويسات الاستجابة الأمنية (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| توجيه الإصدارات (X-API-Version) | ✅ | ✅ | ✅ |
| تحديد معدل الواجهات (نافذة منزلقة) | ✅ | ✅ | ✅ |
| كشف حقن SQL (مطابقة الأنماط) | ✅ | ✅ | ✅ |
| تصفية الإدخال (strip_tags + trim) | ✅ | ✅ | ✅ |
| تشفير وفك تشفير النقل (X-Encrypted) | ✅ | ✅ | ✅ |
| مصادقة JWT Bearer | ✅ | ✅ | ✅ |
| كشف هجمات XSS (11 نمطًا) | — | ✅ | ✅ |
| كشف اجتياز المسار (7 أنماط) | — | ✅ | ✅ |
| كشف حقن الترويسات | — | ✅ | ✅ |
| حد حجم الجسم (10 MiB) | — | ✅ | ✅ |
| قائمة Content-Type المسموحة | — | ✅ | ✅ |
| التعرف على مصدر العميل (8 أطراف) | — | ✅ | ✅ |
| تقييد تسجيل الدخول (5 مرات←15 دقيقة) | — | ✅ | ✅ |
| مراقبة زمن الاستجابة (X-Response-Time) | — | ✅ | ✅ |
| التحقق من Origin/Referer | — | — | ✅ |
| منع إعادة التشغيل (Nonce+Timestamp) | — | — | ✅ |
| حد الجلسات المتزامنة (بحد أقصى 3) | — | — | ✅ |
| CSRF Token (طرف Admin) | — | — | ✅ |
| حماية SSRF (قائمة OAuth المسموحة) | — | — | ✅ |
| إخفاء بيانات السجلات | — | — | ✅ |
| ربط JWT بـ IP/UA | — | — | ✅ |

---

## مقارنة سلاسل الوسائط الوسطية

### طرف Service

| Lite (7 طبقات) | Standard (11 طبقة) | Full (15 طبقة) |
|-------------|-----------------|-------------|
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

### طرف Admin

| Lite (طبقة واحدة) | Standard (4 طبقات) | Full (5 طبقات) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## مقارنة المهام المجدولة

| المهمة | التكرار | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55min | ✅ | ✅ | ✅ |
| DataSyncTask | 10د | ✅ (Campaign+Report فقط) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5min | — | ✅ | ✅ |
| BidCheckTask | 10min | — | — | ✅ |
| BudgetCheckTask | 15min | — | — | ✅ |

---

## مقارنة جداول قاعدة البيانات

| التصنيف | اسم الجدول | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| أساسيات | ads_tenants | ✅ | ✅ | ✅ |
| الحسابات | ads_platform_accounts | ✅ | ✅ | ✅ |
| | ads_auth_tokens | ✅ | ✅ | ✅ |
| النشر | ads_campaigns | ✅ | ✅ | ✅ |
| | ads_report_metrics | ✅ | ✅ | ✅ |
| | ads_report_extras | ✅ | ✅ | ✅ |
| | ads_ad_groups | — | ✅ | ✅ |
| | ads_creatives | — | ✅ | ✅ |
| التنبيهات | ads_alert_rules | — | ✅ | ✅ |
| | ads_alert_logs | — | ✅ | ✅ |
| الإشعارات | ads_notifications | — | ✅ | ✅ |
| المزايدة | ads_bid_rules | — | — | ✅ |
| | ads_bid_logs | — | — | ✅ |
| الاستهداف | ads_targeting_templates | — | — | ✅ |
| المواد | ads_assets | — | — | ✅ |
| CDN | ads_cdn_providers | — | — | ✅ |
| الإسناد | ads_conversions | — | — | ✅ |
| | ads_attribution_results | — | — | ✅ |
| النظام | ads_sync_errors | ✅ | ✅ | ✅ |
| الإدارة | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **الإجمالي** | | **8** | **13** | **19** |

---

## مقارنة صفحات الواجهة الأمامية

### Vue Admin SPA

| الصفحة | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| تسجيل الدخول | ✅ | ✅ | ✅ |
| لوحة المعلومات | ✅ | ✅ | ✅ |
| قائمة الحسابات + الربط | ✅ | ✅ | ✅ |
| خطط الإعلانات | ✅ | ✅ | ✅ |
| تصدير التقارير | ✅ | ✅ | ✅ |
| إدارة المستخدمين | ✅ | ✅ | ✅ |
| سجلات التدقيق | ✅ | ✅ | ✅ |
| المجموعات الإعلانية | — | ✅ | ✅ |
| المواد الإبداعية | — | ✅ | ✅ |
| تحليل التقارير (ECharts) | — | ✅ | ✅ |
| قواعد التنبيه | — | ✅ | ✅ |
| سجلات التنبيه | — | ✅ | ✅ |
| مركز الإشعارات | — | ✅ | ✅ |
| المزايدة التلقائية | — | — | ✅ |
| مكتبة المواد | — | — | ✅ |
| مزودو CDN | — | — | ✅ |
| تقويم النشر | — | — | ✅ |
| تحليل الإسناد | — | — | ✅ |
| **الإجمالي** | **7** | **13** | **18** |

### Flutter

| الصفحة | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| تسجيل الدخول | ✅ | ✅ | ✅ |
| لوحة المعلومات | ✅ | ✅ | ✅ |
| خطط الإعلانات (قائمة + تفاصيل) | ✅ | ✅ | ✅ |
| تقارير البيانات | ✅ | ✅ | ✅ |
| حسابات المنصات | ✅ | ✅ | ✅ |
| إدارة التنبيهات | ✅ | ✅ | ✅ |
| المجموعات الإعلانية | — | ✅ | ✅ |
| المواد الإبداعية | — | ✅ | ✅ |
| تحليل التقارير | — | ✅ | ✅ |
| مركز الإشعارات | — | ✅ | ✅ |
| المزايدة التلقائية | — | — | ✅ |
| **الإجمالي** | **6** | **10** | **11** |

---

## مقارنة نقاط نهاية API

| الوحدة | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| النظام (health/ping/docs/captcha) | 6 | 6 | 6 |
| المصادقة (login/me/refresh) | 3 | 3 | 3 |
| المنصات (list/oauthUrl/callback) | 3 | 3 | 3 |
| الحسابات (index/show/destroy/sync) | 4 | 4 | 4 |
| خطط الإعلانات (CRUD/toggle/batch) | 6 | 6 | 6 |
| المجموعات الإعلانية (CRUD/toggle) | — | 5 | 5 |
| المواد (index/show) | — | 2 | 2 |
| التقارير (summary/custom/export×2) | 4 | 4 | 4 |
| التقارير (calendar/budget/attribution/models) | — | — | 4 |
| التنبيهات (rules CRUD + logs + acknowledge + unread) | — | 7 | 7 |
| الإشعارات (index/unread/read/readAll) | — | 4 | 4 |
| المزايدة التلقائية (CRUD + logs) | — | — | 5 |
| قوالب الاستهداف (CRUD) | — | — | 5 |
| مكتبة المواد (index/upload/show/destroy/presign/register) | — | — | 6 |
| مزودو CDN (list/create/update/delete/default/toggle/test/purge) | — | — | 8 |
| **الإجمالي** | **26** | **44** | **70** |

---

## حزمة التقنيات

تتقاسم الإصدارات الثلاثة حزمة تقنيات موحدة:

| الطبقة | التقنية |
|----|------|
| إطار الخلفية | webman v2, PHP 8.2+ |
| قاعدة البيانات | MySQL 8.0 (InnoDB, utf8mb4) |
| التخزين المؤقت | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| المصادقة | erikwang2013/jwt-webman |
| توليد ID | erikwang2013/snowflake-php |
| ترميز ID | erikwang2013/hashids |
| الواجهة الأمامية | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| النشر | Docker + Nginx + Docker Compose |

---

## مسار الترقية

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
