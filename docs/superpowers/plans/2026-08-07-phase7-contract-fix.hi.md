# Phase 7: क्रॉस-एंड कॉन्ट्रैक्ट फिक्स Implementation Plan

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **स्थिति अपडेट (2026-08-16):** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ सभी पूर्ण, tester रिग्रेशन सत्यापन पास (35 tests OK, कॉन्ट्रैक्ट क्रॉस-चेक में कोई घोस्ट एंडपॉइंट नहीं, Phase 7 स्वीकार्य)।

**लक्ष्य:** टीम ऑडिट में मिली क्रॉस-एंड API कॉन्ट्रैक्ट समस्याएँ ठीक करें: Flutter के 3 घोस्ट एंडपॉइंट (404), Admin `admin.ts` डबल-प्रीफ़िक्स बग, `/system/info` बिना रूट, ServiceProxy अनकनेक्टेड, दस्तावेज़ अप्रचलित। तीनों एंड (Admin/Flutter/HarmonyOS) द्वारा service API की एकसमान खपत बहाल करें।

**स्रोत:** 2026-08-07 टीम समानांतर ऑडिट (backend-dev रूट इन्वेंट्री 61 एंडपॉइंट, vue-dev Admin कॉल इन्वेंट्री 50 कॉल पॉइंट, mobile-dev मोबाइल इन्वेंट्री, researcher कार्यान्वित/योजनाबद्ध इन्वेंट्री क्रॉस-कम्पेयर)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1: Flutter घोस्ट एंडपॉइंट ठीक करें (🔴 उच्चतम प्राथमिकता)

### पृष्ठभूमि
Flutter के 3 पेज ऐसे रूट कॉल कर रहे हैं जो service में मौजूद नहीं, सभी 404:

| Flutter कॉल | service वास्तविक रूट | फिक्स समाधान |
|---|---|---|
| `GET /dashboard` | कोई नहीं (डैशबोर्ड सारांश `/reports/summary` पर) | `GET /reports/summary` में बदलें |
| `GET /alerts` | कोई नहीं (अलर्ट `/alerts/rules`、`/alerts/logs`、`/alerts/unread-count` पर) | `GET /alerts/logs` में बदलें (अलर्ट सूची अर्थ) |
| `GET /reports` | कोई नहीं (रिपोर्ट `/reports/summary`、`/reports/custom` पर) | `GET /reports/custom` में बदलें (दिनांक/आयाम/मेट्रिक पैरामीटर के साथ, ReportBuilder::buildCustom से मेल) |

### Files:
- Modify: `apps/flutter/lib/features/dashboard/dashboard_page.dart` (`/dashboard` → `/reports/summary` ×2 区间, रिस्पॉन्स संरचना `data.overview`/`by_platform`/`daily` अनुकूलित करें) ✅
- Modify: `apps/flutter/lib/features/alert/alert_page.dart` (`/alerts` → `/alerts/logs`, पेजिनेशन संरचना `data.list` अनुकूलित करें, AlertLog फ़ील्ड rule_name/metric/current_value/condition/threshold) ✅
- Modify: `apps/flutter/lib/features/report/report_page.dart` (`/reports` → `/reports/custom`, पैरामीटर date_start/date_end/dimensions[]/metrics[], `data.list` पार्स करें, फ़ील्ड cost) ✅
- Verify: रिस्पॉन्स फ़ील्ड `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` के वास्तविक रिटर्न से मेल खाते हैं ✅

### स्वीकृति
- [x] तीनों पाथ संशोधन पूर्ण, क्वेरी पैरामीटर संरक्षित (report पेज के दिनांक पैरामीटर → date_start/date_end + dimensions/metrics) ✅
- [x] रिस्पॉन्स पार्सिंग बैकएंड की वास्तविक JSON संरचना के अनुरूप (overview / paginated list / custom list) ✅
- [x] संशोधन के बाद `flutter analyze` में कोई त्रुटि नहीं — इस वातावरण में Flutter SDK कैश रीड-ओनली है इसलिए नहीं चल सका, SDK बिल्ट-इन `dart analyze` से पूरे प्रोजेक्ट में **0 errors** (15 मौजूदा चेतावनियाँ संशोधन से पहले की हैं, इस बार कोई नई समस्या नहीं) ✅

---

## Task 2: Admin `admin.ts` डबल-प्रीफ़िक्स बग ठीक करें

### पृष्ठभूमि
- `admin/public/web/src/api/admin.ts` में पाथ `/api/admin/...` लिखा है, जबकि axios baseURL पहले से `/api` है (`src/api/index.ts`), वास्तव में `/api/api/admin/...` बनता है, UserManage.vue / AuditLog.vue के 5 कॉल शायद 404 होंगे।
- **गहरी आर्किटेक्चर समस्या (vue-dev अंतिम रिपोर्ट पुष्टि)**: admin बैकएंड (8789) स्वयं 12 लोकल रूट प्रदान करता है (`/api/admin/login`、`me`、`logout`、`users` CRUD、`roles`、`audit-logs`、`/api/install/*`), लेकिन:
  - `docker/nginx/admin.conf` का `location /api/` **सभी** proxy_pass `service_api` (php:8788) को करता है;
  - `upstream admin_backend` (admin-php:8789) परिभाषित है, लेकिन **कोई location इसे संदर्भित नहीं करता** → प्रोडक्शन में `/api/admin/*` कभी 8789 तक नहीं पहुँचता;
  - Vite dev प्रॉक्सी भी `/api` को पूरी तरह 8788 की ओर भेजता है।
  - निष्कर्ष: डबल-प्रीफ़िक्स ठीक करने पर भी `/api/admin/*` अभी 404 रहेगा — admin बैकएंड के लोकल रूट प्रोडक्शन पाइपलाइन में जुड़े नहीं हैं।

### निर्णय बिंदु (backend-dev + vue-dev + devops पुष्टि आवश्यक)
- समाधान A (अनुशंसित): vue-dev `admin.ts` पाथ को सापेक्ष `/admin/users`、`/admin/audit-logs` बदल दे, साथ ही **devops Nginx में `location /api/admin/` → `proxy_pass http://admin_backend` जोड़े** (`location /api/` से पहले रखें, सटीक प्रीफ़िक्स प्राथमिकता मिलान), ताकि admin-विशिष्ट रूट 8789 सीधे सेव करे, बिज़नेस रूट 8788 से चलें
- समाधान B: backend-dev service में `/api/admin/*` रूट जोड़े (Admin एंड की ज़िम्मेदारी से ओवरलैप, अनुशंसित नहीं)
- समाधान C: बिज़नेस क्वेरी भी ServiceProxy से चलाएँ (वायरिंग आवश्यक, सबसे बड़ा बदलाव, केवल तभी जब admin एंड के लिए एकीकृत प्रमाणीकरण चाहिए)

### Files:
- Modify: `admin/public/web/src/api/admin.ts` (`/api` प्रीफ़िक्स हटाएँ)
- Modify: `docker/nginx/admin.conf` (नया `location /api/admin/` → admin_backend upstream)
- Modify: `admin/public/web/vite.config.ts` (dev प्रॉक्सी में `/api/admin` → 8789 नियम जोड़ें, `/api` से पहले रखें)
- Verify: `admin/config/route.php` में admin बैकएंड रूट (/api/admin/users आदि) फ़्रंटएंड कॉल से मेल खाते हैं

### स्वीकृति
- [x] फ़्रंटएंड अनुरोध पाथ वास्तव में मौजूद बैकएंड रूट से मेल खाता है (कोई 404 नहीं) — admin.ts के 9 तरीके सभी route.php से जाँचे ✅, vue-tsc पास
- [x] Nginx / Vite दोनों `/api/admin/*` को 8789 पर, बाकी `/api/*` को 8788 पर सही ढंग से भेजते हैं — Nginx में नया `location /api/admin/`, Vite में नया `/api/admin` प्रॉक्सी (`/api` से पहले) ✅
- [x] UserManage / AuditLog पेज कार्यशील — पाथ संरेखित (listRoles → `/admin/users/roles` निर्णय सहित) ✅

---

## Task 3: `/system/info` बिना रूट + ServiceProxy निर्णय

### पृष्ठभूमि
- `SystemInfo.vue` / `stores/admin.ts` कॉल `GET /api/system/info`, service में ऐसा कोई रूट नहीं (केवल /health、/ping), 404 try/catch में दब जाता है
- `admin/app/controller/ServiceProxy.php` परिभाषित है लेकिन पूरे रिपो में 0 सक्रिय कॉलर ("परिभाषित पर अनकनेक्टेड")

### निर्णय बिंदु
- `/system/info`: समाधान A — फ़्रंटएंड `/health` कॉल करे (service में पहले से है); समाधान B — backend-dev service में `/api/system/info` एंडपॉइंट जोड़े (वर्शन/पर्यावरण जानकारी लौटाता है, HarmonyOS/Flutter के लिए भी उपयोगी, अनुशंसित)
- ServiceProxy: समाधान A — admin की ज़रूरत वाले admin-विशिष्ट API से जोड़ें (जैसे ऑडिट लॉग फ़ॉरवर्डिंग); समाधान B — क्लास हटाएँ और दस्तावेज़ में "Admin सीधे service से जुड़ता है" घोषित करें (वर्तमान वास्तविक आर्किटेक्चर)

### निष्पादित (2026-08-16)
- **`/system/info` → समाधान A (फ़्रंटएंड `/health` पर स्विच)**: SystemInfo.vue नेटिव axios से `GET /health` कॉल करता है, `checks.database === 'ok'` जाँचता है; `/health` रूट service की ओर `/api` प्रीफ़िक्स के बिना है, Vite में नया `/health` प्रॉक्सी, Nginx में मौजूदा `location /health` पहले से है; `stores/admin.ts` डेड कोड भी `/health` पर बदला ✅
- **ServiceProxy → समाधान B (रखें + दस्तावेज़ स्पष्टीकरण)**: क्लास को आरक्षित इन्फ्रास्ट्रक्चर के रूप में रखा (`ServiceProxy::init()` सेल्फ-इनिशियलाइज़ेशन हानिरहित), `admin/config/app.php` टिप्पणी "आरक्षित इन्फ्रास्ट्रक्चर, वर्तमान में कोई सक्रिय कॉलर नहीं" ✅

### स्वीकृति
- [x] `/system/info` निर्णय लागू: फ़्रंटएंड कॉल हटा दी गई (/health पर बदली), कोई 404 घोस्ट अनुरोध नहीं ✅
- [x] ServiceProxy निर्णय लागू: क्लास रखी और config टिप्पणी में स्थिति स्पष्ट ✅

---

## Task 4: दस्तावेज़ भरना और मानक एकीकरण

### पृष्ठभूमि
- README "14 कंट्रोलर / 45+ एंडपॉइंट" अप्रचलित (वास्तविक 17 कंट्रोलर / 61 एंडपॉइंट)
- `docs/superpowers/plans/` के विभिन्न phase checkbox भरे नहीं (कोड लागू हो चुका लेकिन दस्तावेज़ में टिक नहीं)
- HarmonyOS स्थिति "UI योजना में" अप्रचलित (वास्तविक 6 पेज + ApiClient तैयार)
- install.html / InstallController डिफ़ॉल्ट `.../api/v1` और config डिफ़ॉल्ट `/api` (X-API-Version हेडर) असंगत
- CacheService टिप्पणी दो-स्तरीय कैश कहती है, वास्तव में तीन-स्तरीय (L1 मेमोरी / APCu / Redis)

### Files:
- Modify: `README.md` / `README.en.md` (कंट्रोलर संख्या, एंडपॉइंट संख्या, HarmonyOS स्थिति, कैश स्तर)
- Modify: `admin/public/install.html` / `admin/app/controller/InstallController.php` (वर्शन प्रीफ़िक्स मानक एकीकरण)
- Modify: `service/support/CacheService.php` (टिप्पणी सुधार)
- Optional: `docs/superpowers/plans/*.md` checkbox भरें

### निष्पादित (2026-08-16)
- README.md / README.en.md: 17 कंट्रोलर / 61 एंडपॉइंट / HarmonyOS 6 पेज / 19 Vue पेज / SPA सीधा कनेक्शन मानक सभी अपडेट ✅
- install.html / InstallController: `/api/v1` डिफ़ॉल्ट मान → `/api` (X-API-Version हेडर तंत्र) ✅
- 8 phase plan checkbox सभी भरे ✅ (phase7 को छोड़कर, प्रतीक्षारत)

### स्वीकृति
- [x] README डेटा कोड के अनुरूप (17 कंट्रोलर / 61 एंडपॉइंट / HarmonyOS 6 पेज) ✅
- [x] इंस्टॉलेशन विज़ार्ड वर्शन प्रीफ़िक्स X-API-Version तंत्र के अनुरूप ✅

---

## बाद के चरणों की योजना (Phase 8-10, इस योजना के बाहर)

| Phase | सामग्री | स्थिति |
|---|---|---|
| Phase 8 | अलर्ट मल्टी-चैनल लागू करना: ads-alert में channel/ जोड़ें (Email SMTP、Webhook、SMS गेटवे प्लेसहोल्डर) —— Phase 5 का बचा हुआ अंतराल भरें | प्रतीक्षारत |
| Phase 9 | HarmonyOS वास्तविक इंटीग्रेशन: 6 पेज ApiClient से जोड़ें (वर्तमान 0 वास्तविक कॉल, सब सिम्युलेटेड डेटा) | प्रतीक्षारत |
| Phase 10 | गहरीकरण और व्यावसायीकरण: 29 प्लेटफ़ॉर्म वास्तविक इंटीग्रेशन, सिंक स्थिति विज़ुअलाइज़ेशन, रूपांतरण डेटा लूप, Flutter/HarmonyOS CI पैकेजिंग, मल्टी-टेनेंट SaaS कोटा | प्रतीक्षारत |
