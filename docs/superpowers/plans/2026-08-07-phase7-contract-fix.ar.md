# المرحلة 7: خطة تنفيذ إصلاح العقود عبر الأطراف

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **تحديث الحالة (2026-08-16):** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ اكتملت جميعها، وتحقق المختبر من الانحدار (35 اختبارًا ناجحًا، لا نقاط نهاية شبحية في التدقيق العرضي للعقود، يمكن قبول المرحلة 7).

**الهدف:** إصلاح مشكلات عقود API عبر الأطراف التي كشفها تدقيق الفريق: 3 نقاط نهاية شبحية في Flutter (404)، وخطأ البادئة المزدوجة في `admin.ts` الخاص بـ Admin، وعدم وجود مسار `/system/info`، وعدم ربط ServiceProxy، وتقادم مستندات الواجهة. استعادة الاستهلاك المتسق لـ service API عبر الأطراف الثلاثة (Admin/Flutter/HarmonyOS).

**المصدر:** تدقيق الفريق الموازي في 2026-08-07 (جرد backend-dev لـ 61 نقطة نهاية للمسارات، وجرد vue-dev لـ 50 نقطة استدعاء في Admin، وجرد mobile-dev للأطراف المتنقلة، ومقارنة researcher بين المنجز والمخطط)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## المهمة 1: إصلاح نقاط النهاية الشبحية في Flutter (🔴 الأولوية القصوى)

### الخلفية
3 صفحات في Flutter تستدعي مسارات غير موجودة في service، كلها 404:

| استدعاء Flutter | المسار الفعلي في service | خطة الإصلاح |
|---|---|---|
| `GET /dashboard` | غير موجود (ملخص لوحة التحكم في `/reports/summary`) | التغيير إلى `GET /reports/summary` |
| `GET /alerts` | غير موجود (التنبيهات في `/alerts/rules` و`/alerts/logs` و`/alerts/unread-count`) | التغيير إلى `GET /alerts/logs` (بمعنى قائمة التنبيهات) |
| `GET /reports` | غير موجود (التقارير في `/reports/summary` و`/reports/custom`) | التغيير إلى `GET /reports/custom` (مع معاملات التاريخ/الأبعاد/المقاييس، بما يطابق ReportBuilder::buildCustom) |

### الملفات:
- تعديل: `apps/flutter/lib/features/dashboard/dashboard_page.dart` (`/dashboard` → `/reports/summary` ×2 موضع، مع تكييف بنية الاستجابة `data.overview`/`by_platform`/`daily`) ✅
- تعديل: `apps/flutter/lib/features/alert/alert_page.dart` (`/alerts` → `/alerts/logs`، مع تكييف بنية الترقيم `data.list`، وحقول AlertLog rule_name/metric/current_value/condition/threshold) ✅
- تعديل: `apps/flutter/lib/features/report/report_page.dart` (`/reports` → `/reports/custom`، معاملات date_start/date_end/dimensions[]/metrics[]، تحليل `data.list`، حقل cost) ✅
- تحقق: حقول الاستجابة متطابقة مع ما يُرجعه فعليًا `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` ✅

### القبول
- [x] اكتمل تعديل المسارات الثلاثة، مع الحفاظ على معاملات الاستعلام (معاملات التاريخ لصفحة التقرير → date_start/date_end + dimensions/metrics) ✅
- [x] تحليل الاستجابة متوافق مع بنية JSON الفعلية للواجهة الخلفية (overview / paginated list / custom list) ✅
- [x] `flutter analyze` بدون أخطاء بعد التعديل — ذاكرة Flutter SDK للقراءة فقط في هذه البيئة لا يمكن تشغيلها، استُخدم بدلاً منها `dart analyze` المدمجة في SDK على المشروع كاملًا: **0 errors** (الـ 15 تحذيرًا قائمة كلها من قبل التعديل، ولم تُدخل هذه المرة مشكلات جديدة) ✅

---

## المهمة 2: إصلاح خطأ البادئة المزدوجة في `admin.ts` الخاص بـ Admin

### الخلفية
- المسار في `admin/public/web/src/api/admin.ts` مكتوب `/api/admin/...`، بينما baseURL في axios هو `/api` بالفعل (`src/api/index.ts`)، فيُكوَّن فعليًا `/api/api/admin/...`، ومن المرجح أن تكون استدعاءات UserManage.vue / AuditLog.vue الخمسة 404.
- **مشكلة بنيوية عميقة (أكدها التقرير النهائي لـ vue-dev)**: الواجهة الخلفية لـ Admin (8789) توفر بنفسها 12 مسارًا محليًا (`/api/admin/login`، `me`، `logout`، CRUD لـ `users`، `roles`، `audit-logs`، `/api/install/*`)، لكن:
  - `location /api/` في `docker/nginx/admin.conf` **كله** proxy_pass إلى `service_api` (php:8788)؛
  - `upstream admin_backend` (admin-php:8789) معرّف لكن **لا يوجد أي location يشير إليه** → في بيئة الإنتاج لا يصل `/api/admin/*` إلى 8789 أبدًا؛
  - وكيل Vite dev يوجه `/api` كله أيضًا إلى 8788.
  - الخلاصة: حتى بعد إصلاح البادئة المزدوجة، يبقى `/api/admin/*` 404 — المسارات المحلية للواجهة الخلفية لـ Admin غير مربوطة في سلسلة الإنتاج.

### نقاط القرار (تتطلب تأكيد backend-dev + vue-dev + devops)
- الخيار A (موصى به): يغيّر vue-dev مسارات `admin.ts` إلى النسبية `/admin/users` و`/admin/audit-logs`، وفي نفس الوقت **يضيف devops في Nginx `location /api/admin/` → `proxy_pass http://admin_backend`** (يوضع قبل `location /api/`، فتتقدّم مطابقة البادئة الدقيقة)، لتخدم المسارات الخاصة بـ Admin مباشرة عبر 8789، وتبقى مسارات الأعمال عبر 8788
- الخيار B: يضيف backend-dev مسارات `/api/admin/*` في service (تداخل مع مسؤولية طرف Admin، غير موصى به)
- الخيار C: توجيه استعلامات الأعمال أيضًا عبر ServiceProxy (يتطلب الربط، أكبر تغيير، يُنظر إليه فقط عند الحاجة إلى مصادقة موحدة لطرف Admin)

### الملفات:
- تعديل: `admin/public/web/src/api/admin.ts` (إزالة بادئة `/api`)
- تعديل: `docker/nginx/admin.conf` (إضافة `location /api/admin/` → upstream admin_backend)
- تعديل: `admin/public/web/vite.config.ts` (إضافة قاعدة `/api/admin` → 8789 في وكيل dev، قبل `/api`)
- تحقق: مطابقة مسارات الواجهة الخلفية لـ Admin في `admin/config/route.php` (مثل /api/admin/users) مع استدعاءات الواجهة الأمامية

### القبول
- [x] مسارات طلبات الواجهة الأمامية مطابقة للمسارات الموجودة فعليًا في الواجهة الخلفية (بدون 404) — تمت مطابقة طرق admin.ts التسعة كلها مع route.php ✅، واجتاز vue-tsc
- [x] يقوم Nginx / Vite بتوجيه `/api/admin/*` بشكل صحيح إلى 8789، وبقية `/api/*` إلى 8788 — أُضيف `location /api/admin/` في Nginx، وأُضيف وكيل `/api/admin` في Vite (قبل `/api`) ✅
- [x] صفحات UserManage / AuditLog تعمل — تمت محاذاة المسارات (بما في ذلك قرار listRoles → `/admin/users/roles`) ✅

---

## المهمة 3: `/system/info` بلا مسار + قرار ServiceProxy

### الخلفية
- يستدعي `SystemInfo.vue` / `stores/admin.ts` `GET /api/system/info`، ولا يوجد هذا المسار في service (فقط /health و/ping)، ويُبتلع 404 بواسطة try/catch
- `admin/app/controller/ServiceProxy.php` معرّف لكن لديه 0 استدعاء نشط في كامل المستودع ("معرّف غير مربوط")

### نقاط القرار
- `/system/info`: الخيار A — تغيير الواجهة الأمامية لاستدعاء `/health` (موجود في service)؛ الخيار B — إضافة backend-dev نقطة نهاية `/api/system/info` في service (تعيد معلومات الإصدار/البيئة، مفيدة أيضًا لـ HarmonyOS/Flutter، موصى به)
- ServiceProxy: الخيار A — ربطه بواجهات API الخاصة بـ Admin التي تحتاجها (مثل إعادة توجيه سجل التدقيق)؛ الخيار B — حذف الفئة وتحديث المستندات بالإعلان "Admin يتصل مباشرة بـ service" (البنية الفعلية الحالية)

### المنفذ (2026-08-16)
- **`/system/info` → الخيار A (تغيير الواجهة الأمامية لاستدعاء `/health`)** : غُيّر SystemInfo.vue لاستدعاء `GET /health` عبر axios أصلي، مع الحكم على `checks.database === 'ok'`؛ مسار `/health` في جانب service بدون بادئة `/api`، وأُضيف وكيل `/health` في Vite، و`location /health` موجود أصلًا في Nginx؛ غُيّر الكود الميت في `stores/admin.ts` إلى `/health` ✅
- **ServiceProxy → الخيار B (الإبقاء + توثيق)**: أُبقيت الفئة كبنية تحتية محجوزة (`ServiceProxy::init()` تهيئة ذاتية غير ضارة)، وحُدّث التعليق في `admin/config/app.php` إلى "بنية تحتية محجوزة، لا توجد حاليًا جهات استدعاء نشطة" ✅

### القبول
- [x] قرار `/system/info` نُفذ: أزالت الواجهة الأمامية الاستدعاء (تغيير إلى /health)، لا طلبات شبحية 404 ✅
- [x] قرار ServiceProxy نُفذ: إبقاء الفئة وتوضيح الوضع في تعليق config ✅

---

## المهمة 4: سدّ فجوات المستندات وتوحيد النصوص

### الخلفية
- "14 وحدة تحكم / 45+ نقطة نهاية" في README قديم (الفعلي 17 وحدة تحكم / 61 نقطة نهاية)
- خانات checkbox في `docs/superpowers/plans/` للمراحل المختلفة غير معبأة (الكود منفذ لكن المستندات غير محددة)
- حالة HarmonyOS "واجهة UI قيد التخطيط" قديمة (الفعلي 6 صفحات + ApiClient جاهزة)
- القيمة الافتراضية `.../api/v1` في install.html / InstallController لا تتطابق مع الافتراضي `/api` في config (ترويسة X-API-Version)
- تعليق CacheService يقول تخزين بمستويين، والفعلي ثلاثة مستويات (L1 ذاكرة / APCu / Redis)

### الملفات:
- تعديل: `README.md` / `README.en.md` (عدد وحدات التحكم، عدد نقاط النهاية، حالة HarmonyOS، مستويات التخزين)
- تعديل: `admin/public/install.html` / `admin/app/controller/InstallController.php` (توحيد بادئة الإصدار)
- تعديل: `service/support/CacheService.php` (تصحيح التعليق)
- اختياري: تعبئة خانات checkbox في `docs/superpowers/plans/*.md`

### المنفذ (2026-08-16)
- README.md / README.en.md: حُدّثت كل من 17 وحدة تحكم / 61 نقطة نهاية / 6 صفحات HarmonyOS / 19 صفحة Vue / نص الاتصال المباشر SPA ✅
- install.html / InstallController: القيمة الافتراضية `/api/v1` → `/api` (آلية ترويسة X-API-Version) ✅
- عُبئت خانات checkbox في 8 خطط مراحل ✅ (باستثناء المرحلة 7، قيد التنفيذ)

### القبول
- [x] بيانات README متطابقة مع الكود (17 وحدة تحكم / 61 نقطة نهاية / 6 صفحات HarmonyOS) ✅
- [x] بادئة الإصدار في معالج التثبيت متطابقة مع آلية X-API-Version ✅

---

## تخطيط المراحل اللاحقة (المراحل 8-10، خارج هذه الخطة)

| المرحلة | المحتوى | الحالة |
|---|---|---|
| المرحلة 8 | تنفيذ قنوات التنبيهات المتعددة: إضافة channel/ إلى ads-alert (Email SMTP، Webhook، بوابة SMS كعنصر نائب) — سدّ فجوة المرحلة 5 المتبقية | بانتظار البدء |
| المرحلة 9 | الربط الفعلي لـ HarmonyOS: 6 صفحات تتصل بـ ApiClient (حاليًا 0 استدعاءات حقيقية، كلها بيانات محاكاة) | بانتظار البدء |
| المرحلة 10 | التعميق والتجارة: ربط فعلي لـ 29 منصة، تصور حالة المزامنة، إغلاق حلقة بيانات التحويل، تعبئة CI لـ Flutter/HarmonyOS، حصص SaaS متعددة المستأجرين | بانتظار البدء |
