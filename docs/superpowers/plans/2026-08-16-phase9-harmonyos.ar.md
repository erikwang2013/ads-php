# المرحلة 9: خطة تنفيذ الربط الفعلي لـ HarmonyOS

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**الهدف:** تحويل صفحات HarmonyOS الست من بيانات محاكاة إلى استدعاءات API حقيقية (service :8788)، وإصلاح مشكلة baseUrl الثابت في ApiClient، وجعل تسجيل الدخول حقيقيًا، ليكون طرف HarmonyOS عميلًا ثالثًا قابلاً للاستخدام.

**المصدر:** تدقيق فريق المرحلة 7 (جرد mobile-dev: صفحات HarmonyOS الست كلها بيانات محاكاة، 0 استدعاء حقيقي، baseUrl في ApiClient ثابت `http://127.0.0.1:8788/api`)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## الوضع الحالي (تم التحقق منه)

| المكوّن | الحالة |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login مكتملة؛ baseUrl ثابت `http://127.0.0.1:8788/api` (Flutter يستخدم نسبيًا متجانس الأصل `/api`)؛ لا جهات تستدعي login() |
| `pages/LoginPage.ets` | تسجيل دخول محاكى (setTimeout 1s ثم انتقال)، تعليق "replace with actual API call" |
| `pages/DashboardPage.ets` | مقاييس ثابتة في `@State` (totalCost=1250000 إلخ) |
| `pages/CampaignListPage.ets` | تعليق عنصر نائب في L187 `/campaigns` |
| `pages/AccountPage.ets` | تعليق عنصر نائب في L138 `/accounts` |
| `pages/AlertPage.ets` | تعليق عنصر نائب في L146 `/alerts` |
| `pages/ReportPage.ets` | تعليق عنصر نائب في L242 `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric موجودة |
| i18n | StringResources.ets (أكثر من 15 مفتاحًا) |

## المهمة 1: تعزيز ApiClient

### الملفات:
- تعديل: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### نقاط التصميم
- **جعل baseUrl قابلاً للتكوين**: إبقاء setBaseUrl، القيمة الافتراضية تبقى `http://127.0.0.1:8788/api` (الأجهزة الحقيقية/المحاكيات تحتاج عنوان LAN، مع تعليق توضيحي)؛ تجنب المسار النسبي المتجانس الأصل بأسلوب Flutter (ArkTS يتطلب URL مطلقًا)
- **إصلاح خطأ تكرار replayHeaders**: `{ ...this.replayHeaders(), ...this.replayHeaders() }` تكرار توسيع (داخل دالة get) → مرة واحدة
- **تكييف قيمة إرجاع login()**: `POST /api/auth/login` في service يُرجع `{access_token, token_type, expires_in, user}` (مقارنة بالحقول الفعلية في `service/plugin/ads-api/controller/v1/AuthController.php` — هي access_token وليست token، يجب التحقق ثم تصحيح حكم `data.token`)
- **معالجة الأخطاء**: عند عدم كون resp.responseCode في نطاق 2xx، إلقاء خطأ/إرجاع رسالة خطأ واضحة؛ حماية فشل JSON.parse
- الحفاظ على الاتفاقية الحالية بأن get/post/put/delete تُرجع `data.data` (فك غلاف ApiResponse)

## المهمة 2: تسجيل دخول حقيقي في LoginPage

### الملفات:
- تعديل: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### نقاط التصميم
- `handleLogin()` يستدعي `ApiClient.login(username, password)`؛ عند النجاح → setToken + الانتقال إلى Dashboard؛ عند الفشل → رسالة خطأ toast
- حالة التحميل isLoading موجودة، يُعاد استخدامها
- رسائل الخطأ تفضل message المرجعة من service (مغلف ApiResponse)، وعند غيابها نص عام

## المهمة 3: جعل الصفحات الخمس التجارية حقيقية

### الملفات:
- تعديل: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`، `CampaignListPage.ets`، `AccountPage.ets`، `AlertPage.ets`، `ReportPage.ets`

### مقابلة نقاط النهاية (أكدها تدقيق المرحلة 7، متوافقة مع إصلاح Flutter)
| الصفحة | الاستدعاء | التحليل |
|---|---|---|
| DashboardPage | `GET /reports/summary` (نطاق اليوم) | `data.overview` → totalCost/total_impressions/avg_ctr إلخ (المبالغ بالفين، formatFen موجود) |
| CampaignListPage | `GET /campaigns` | `data.list` (ترقيم) → نموذج Campaign |
| AccountPage | `GET /accounts` | `data.list` → نموذج PlatformAccount |
| AlertPage | `GET /alerts/logs` | `data.list` → حقول AlertLog (metric/rule_name/current_value/condition/threshold/status) |
| ReportPage | `GET /reports/custom` (date_start/date_end/dimensions[]/metrics[]) | `data.list` → ReportMetric |

### نقاط التصميم
- تحميل الصفحة (aboutToAppear) يطلق الطلب؛ تهيئة بيانات @State فارغة/صفرية، لتجنب بقاء قيم المحاكاة
- عند فشل التحميل عرض الخطأ + إعادة المحاولة (الرجوع إلى نمط الخطأ/إعادة المحاولة في صفحات Flutter)
- وحدة المبلغ: service يُرجع أرقامًا بالفين، formatFen يعالجها
- **عدم إضافة ملفات جديدة**، الحفاظ على بنية واجهة الصفحات الحالية وi18n

## المهمة 4: التحقق

### القبول
- [ ] لا تكرار replayHeaders في ApiClient، وحقول إرجاع login متطابقة مع AuthController
- [ ] لا بقايا بيانات محاكاة ثابتة في الصفحات الست (تحقق grep)
- [ ] مسارات استدعاء الصفحات الخمس التجارية مطابقة واحدًا لواحد لمسارات service (مقارنة `service/plugin/ads-api/config/route.php`)
- [ ] فحص صياغة ArkTS (إذا توفرت سلسلة أدوات hvigor/DevEco في هذه البيئة فشغّلها؛ وإلا وضّح وراجع يدويًا)
- [ ] الانحدار: PHPUnit في service غير متأثر
