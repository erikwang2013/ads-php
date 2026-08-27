# المرحلة 10: خطة تنفيذ التعميق والتجارة

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**الهدف:** بناءً على عقود المراحل 7-9 والقنوات المتعددة، تنفيذ أربع قدرات تعميق: تصور حالة المزامنة، إغلاق حلقة بيانات التحويل، تعبئة CI للجوال، وحصص SaaS متعددة المستأجرين.

**المصدر:** الاتجاهات التي استنتجها تدقيق فريق المرحلة 7 (researcher: ES/فصل القراءة والكتابة/تنفيذ قوائم الانتظار، CI لـ Flutter/HarmonyOS، ربط فعلي لـ 29 منصة، حصص فوترة SaaS، إغلاق حلقة بيانات التحويل، تصور حالة المزامنة، عروض أسعار AI)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## الوضع الحالي (تم التحقق منه)

| البند الفرعي المرشح | الوضع الحالي |
|---|---|
| تصور حالة المزامنة | جدول `ads_sync_errors` + `RetrySyncTask` (إعادة محاولة 3 مرات، تراجع 5^n دقيقة) موجودان؛ **لا توجد صفحة أمامية/API تعرض معدل فشل المزامنة والتأخير** |
| إغلاق حلقة بيانات التحويل | جدولا `ads_conversions` + `ads_attribution_results` موجودان ومحرك الإسناد منفذ؛ **لا يوجد مدخل لجمع بيانات التحويل** (API للإرجاع/التتبع) |
| CI للجوال | `ci.yml` فقط PHP syntax→PHPUnit→vue-tsc→Docker؛ **لا بناء/تعبئة Flutter أو HarmonyOS** |
| SaaS متعدد المستأجرين | جدول `ads_tenants` + وسيط TenantIdentify موجودان؛ **لا فوترة/حصص/إحصاءات استخدام** |
| تنفيذ ES | scout.php مُهيأ + تبعية webman-scout مضافة؛ **لا خدمة ES في docker-compose** |
| الربط الفعلي لـ 29 منصة | أكواد المحولات الـ 29 كاملة؛ **لا سجل ربط ببيئات تجريبية/بيانات اعتماد** (يتطلب بيانات اعتماد خارجية، يُعلَّم كبند يدوي) |

## المهمة 1: تصور حالة المزامنة

### الملفات:
- تعديل: `service/plugin/ads-api/controller/v1/DashboardController.php` أو إضافة `service/plugin/ads-api/controller/v1/SyncController.php` + مسار
- إنشاء: `admin/public/web/src/api/sync.ts`
- إنشاء: `admin/public/web/src/views/sync/SyncStatus.vue` (أو دمجه في صفحة النظام)

### نقاط التصميم
- نقطة النهاية: `GET /api/sync/status` (ببعد الحساب: last_sync_at، معدل النجاح، عدد فشل اليوم، عدد إعادة المحاولة المعلقة) + `GET /api/sync/errors` (قائمة أخطاء بترقيم الصفحات، مع last_error/retry_count/next_retry_at)
- الواجهة الأمامية: صفحة حالة المزامنة (جدول + بطاقات ملخص)، في خطي إصدار Full/Standard فقط
- مصدر البيانات: ads_platform_accounts (last_sync_at) + ads_sync_errors

## المهمة 2: API جمع بيانات التحويل

### الملفات:
- تعديل: `service/plugin/ads-api/controller/v1/` (إضافة ConversionController + مسار)
- إنشاء: `service/plugin/ads-report/service/ConversionService.php`

### نقاط التصميم
- نقطة النهاية: `POST /api/conversions` (إرجاع التحويلات من جهة الأعمال: platform/campaign_id/order_id/conversion_time/value/currency/channel) + `GET /api/conversions` (استعلام)
- التحقق: وجود campaign_id، مبلغ غير سالب، تنسيق الوقت؛ الكتابة إلى ads_conversions
- الترابط مع الإسناد: بعد الإرجاع يمكن تشغيل إعادة حساب الإسناد (أو توضيح إعادة الحساب الدورية/اليدوية بواسطة AttributionEngine الحالي)
- الواجهة الأمامية: إضافة شرح/عرض "إرجاع التحويلات" في صفحة تقرير الإسناد (اختياري)

## المهمة 3: تعبئة CI للجوال

### الملفات:
- تعديل: `.github/workflows/ci.yml` (إضافة job: بناء Flutter (web + linux أو apk) + فحص ثابت لـ HarmonyOS)

### نقاط التصميم
- Flutter: `flutter pub get && flutter analyze && flutter build web` (أو apk، اختيار الهدف القابل للبناء حسب حالة المستودع؛ إذا كانت بيئة flutter مقيدة فاستخدم dart analyze)
- HarmonyOS: لا توجد سلسلة أدوات CI قياسية على Linux، إجراء فحص ثابت أو تخطي (مع تعليم)
- بالتوازي مع job php-tests الحالي، دون حجب التدفق الرئيسي

## المهمة 4: حصص SaaS متعددة المستأجرين (MVP)

### الملفات:
- تعديل: `service/plugin/ads-tenant/` (إضافة QuotaService)
- تعديل: `service/plugin/ads-api/config/route.php` + وحدة تحكم

### نقاط التصميم
- البيانات: إضافة حقل quota إلى ads_tenants أو جدول جديد ads_tenant_quotas (plan/account_limit/campaign_limit/sync_quota)
- نقاط التحقق: عدد الحسابات المرتبطة، عدد الخطط المنشأة، عدد المزامنات اليومية (الفحص عند مداخل AccountController/CampaignController/DataSyncTask)
- نقطة النهاية: `GET /api/tenant/quota` (الاستخدام + الحصة)
- الواجهة الأمامية: عرض استخدام الحصة في صفحة النظام (اختياري، MVP قد يكتفي بـ API)
- خط الإصدار: القيم الافتراضية للحصة تختلف حسب lite/standard/full (ثوابت config)

## القبول (حسب المهمة)
- [ ] المهمة 1: نقطة نهاية API للمزامنة قابلة للاستخدام، صفحة أمامية تعرض، تغطية اختبارات
- [ ] المهمة 2: API إرجاع conversions قابل للكتابة والاستعلام، التحقق فعال، تغطية اختبارات
- [ ] المهمة 3: job CI الجديد ناجح (أو تعليم واضح للبنود المتخطاة)
- [ ] المهمة 4: API الحصص يُرجع بشكل صحيح، اعتراض تجاوز الحدود فعال، تغطية اختبارات
- [ ] الكل: `php vendor/bin/phpunit --no-coverage` ناجح بالكامل، vue-tsc ناجح

## خارج نطاق هذه المرحلة (يتطلب موارد خارجية)
- الربط الفعلي لـ 29 منصة (يتطلب بيانات اعتماد/بيئات تجريبية لكل منصة)
- تنفيذ خدمة ES (يتطلب إضافة خدمة ES وتهيئة الفهرس في docker-compose)
- اقتراحات عروض أسعار AI (تحضير النماذج/البيانات)
