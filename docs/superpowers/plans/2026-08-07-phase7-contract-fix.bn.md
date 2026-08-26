# Phase 7: 跨端契约修复 Implementation Plan

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **স্ট্যাটাস আপডেট (2026-08-16):** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ সব সম্পন্ন, tester রিগ্রেশন ভেরিফিকেশন পাস করেছে (35 tests OK, কনট্র্যাক্ট ক্রস-চেক-এ কোনো ঘোস্ট এন্ডপয়েন্ট নেই, Phase 7 গ্রহণযোগ্য)।

**Goal:** টিম অডিটে পাওয়া ক্রস-এন্ড API কনট্র্যাক্ট সমস্যা ফিক্স করা: Flutter ৩টি ঘোস্ট এন্ডপয়েন্ট (404)、Admin `admin.ts` ডাবল-প্রিফিক্স bug、`/system/info`-এর কোনো রুট নেই、ServiceProxy সংযুক্ত নেই、ডকুমেন্টেশন তথ্য পুরনো। তিনটি এন্ড (Admin/Flutter/HarmonyOS) থেকে service API-এর সামঞ্জস্যপূর্ণ ব্যবহার পুনরুদ্ধার।

**উৎস:** 2026-08-07 টিম প্যারালাল অডিট (backend-dev রুট ইনভেন্টরি ৬১ এন্ডপয়েন্ট、vue-dev Admin কল ইনভেন্টরি ৫০ কল পয়েন্ট、mobile-dev মোবাইল ইনভেন্টরি、researcher বাস্তবায়িত/পরিকল্পিত ইনভেন্টরি ক্রস-কম্পারিজন)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1: Flutter ঘোস্ট এন্ডপয়েন্ট ফিক্স (🔴 সর্বোচ্চ অগ্রাধিকার)

### পটভূমি
Flutter-এর ৩টি পেজ service-এ নেই এমন রুট কল করেছে, সব 404:

| Flutter কল | service প্রকৃত রুট | ফিক্স পদ্ধতি |
|---|---|---|
| `GET /dashboard` | নেই (ড্যাশবোর্ড সামারি `/reports/summary`-এ) | `GET /reports/summary`-এ পরিবর্তন করুন |
| `GET /alerts` | নেই (অ্যালার্ট `/alerts/rules`、`/alerts/logs`、`/alerts/unread-count`-এ) | `GET /alerts/logs`-এ পরিবর্তন করুন (অ্যালার্ট লিস্ট সেমান্টিক্স) |
| `GET /reports` | নেই (রিপোর্ট `/reports/summary`、`/reports/custom`-এ) | `GET /reports/custom`-এ পরিবর্তন করুন (তারিখ/ডাইমেনশন/মেট্রিক প্যারামিটারসহ, ReportBuilder::buildCustom-এর সাথে ম্যাচ) |

### ফাইল:
- Modify: `apps/flutter/lib/features/dashboard/dashboard_page.dart` (`/dashboard` → `/reports/summary` ×2 জায়গা, রেসপন্স স্ট্রাকচার `data.overview`/`by_platform`/`daily` অনুযায়ী অ্যাডাপ্ট) ✅
- Modify: `apps/flutter/lib/features/alert/alert_page.dart` (`/alerts` → `/alerts/logs`, পেজিনেশন স্ট্রাকচার `data.list` অনুযায়ী অ্যাডাপ্ট, AlertLog ফিল্ড rule_name/metric/current_value/condition/threshold) ✅
- Modify: `apps/flutter/lib/features/report/report_page.dart` (`/reports` → `/reports/custom`, প্যারামিটার date_start/date_end/dimensions[]/metrics[], `data.list` পার্স, ফিল্ড cost) ✅
- Verify: রেসপন্স ফিল্ড `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php`-এর প্রকৃত রিটার্নের সাথে সামঞ্জস্যপূর্ণ ✅

### গ্রহণযোগ্যতা
- [x] তিনটি পাথ সংশোধন সম্পন্ন, কোয়েরি প্যারামিটার সংরক্ষিত (report পেজের তারিখ প্যারামিটার → date_start/date_end + dimensions/metrics) ✅
- [x] রেসপন্স পার্সিং ব্যাকএন্ডের প্রকৃত JSON স্ট্রাকচারের সাথে অ্যালাইন (overview / paginated list / custom list) ✅
- [x] সংশোধনের পর `flutter analyze`-এ কোনো error নেই — এই পরিবেশে Flutter SDK ক্যাশ রিড-অনলি, চালানো যায়নি, তাই SDK-এর বিল্ট-ইন `dart analyze` দিয়ে পুরো প্রজেক্ট **0 errors** (১৫টি পুরনো warning সবই পরিবর্তনের আগে থেকে ছিল, এবার কোনো নতুন সমস্যা যোগ হয়নি) ✅

---

## Task 2: Admin `admin.ts` ডাবল-প্রিফিক্স bug ফিক্স

### পটভূমি
- `admin/public/web/src/api/admin.ts`-এ পাথ `/api/admin/...` লেখা, কিন্তু axios baseURL ইতিমধ্যে `/api` (`src/api/index.ts`), ফলে প্রকৃতপক্ষে `/api/api/admin/...` হয়, UserManage.vue / AuditLog.vue-এর ৫টি কল সম্ভবত 404।
- **গভীর আর্কিটেকচার সমস্যা (vue-dev চূড়ান্ত রিপোর্টে নিশ্চিত)**: admin ব্যাকএন্ড (8789) নিজেই ১২টি লোকাল রুট দেয় (`/api/admin/login`、`me`、`logout`、`users` CRUD、`roles`、`audit-logs`、`/api/install/*`)，কিন্তু：
  - `docker/nginx/admin.conf`-এর `location /api/` **সব** proxy_pass করে `service_api`-তে (php:8788)；
  - `upstream admin_backend` (admin-php:8789) সংজ্ঞায়িত থাকলেও **কোনো location-ই এটি ব্যবহার করে না** → প্রোডাকশনে `/api/admin/*` কখনো 8789-এ পৌঁছাবে না；
  - Vite dev প্রক্সিও `/api` সব 8788-তে পাঠায়।
  - উপসংহার: ডাবল-প্রিফিক্স ঠিক করলেও `/api/admin/*` এখনও 404—admin ব্যাকএন্ডের লোকাল রুট প্রোডাকশন চেইনে সংযুক্ত নয়।

### সিদ্ধান্ত পয়েন্ট (backend-dev + vue-dev + devops নিশ্চিতকরণ প্রয়োজন)
- স্কিম A (প্রস্তাবিত): vue-dev `admin.ts` পাথ রিলেটিভ `/admin/users`、`/admin/audit-logs`-এ পরিবর্তন করবে, সাথে **devops Nginx-এ `location /api/admin/` → `proxy_pass http://admin_backend` যোগ করবে** (`location /api/`-এর আগে বসিয়ে, নির্ভুল প্রিফিক্স প্রায়োরিটি ম্যাচিং), যাতে admin-নির্দিষ্ট রুট 8789 সরাসরি সার্ভ করে, বিজনেস রুট এখনও 8788-তে যায়
- স্কিম B: backend-dev service-এ `/api/admin/*` রুট যোগ করবে (Admin এন্ডের দায়িত্বের সাথে ওভারল্যাপ, প্রস্তাবিত নয়)
- স্কিম C: বিজনেস কোয়েরিও ServiceProxy দিয়ে যায় (সংযোগ লাগবে, সবচেয়ে বড় পরিবর্তন, শুধুমাত্র admin এন্ডে ইউনিফাইড অথেনটিকেশন প্রয়োজন হলে বিবেচনা)

### ফাইল:
- Modify: `admin/public/web/src/api/admin.ts` (`/api` প্রিফিক্স অপসারণ)
- Modify: `docker/nginx/admin.conf` (নতুন `location /api/admin/` → admin_backend upstream)
- Modify: `admin/public/web/vite.config.ts` (dev প্রক্সিতে `/api/admin` → 8789 রুল যোগ, `/api`-এর আগে)
- Verify: `admin/config/route.php`-এ admin ব্যাকএন্ড রুট (/api/admin/users ইত্যাদি) ফ্রন্টএন্ড কলের সাথে ম্যাচ

### গ্রহণযোগ্যতা
- [x] ফ্রন্টএন্ড রিকোয়েস্ট পাথ প্রকৃতপক্ষে থাকা ব্যাকএন্ড রুটের সাথে সামঞ্জস্যপূর্ণ (কোনো 404 নেই) — admin.ts-এর ৯টি মেথড সব route.php-এর সাথে যাচাই ✅, vue-tsc পাস
- [x] Nginx / Vite দুটোই `/api/admin/*` সঠিকভাবে 8789-এ分流 করে, `/api/*` বাকি 8788-এ — Nginx-এ নতুন `location /api/admin/` যোগ, Vite-এ নতুন `/api/admin` প্রক্সি (`/api`-এর আগে) ✅
- [x] UserManage / AuditLog পেজ ফাংশনাল — পাথ অ্যালাইন হয়েছে (listRoles → `/admin/users/roles` সিদ্ধান্তসহ) ✅

---

## Task 3: `/system/info`-এর রুট নেই + ServiceProxy সিদ্ধান্ত

### পটভূমি
- `SystemInfo.vue` / `stores/admin.ts` `GET /api/system/info` কল করে, service-এ এই রুট নেই (শুধু /health、/ping), 404 try/catch-এ গিলে ফেলা হয়
- `admin/app/controller/ServiceProxy.php` সংজ্ঞায়িত কিন্তু পুরো রিপোতে ০টি সক্রিয় কলার ("সংজ্ঞায়িত কিন্তু সংযুক্ত নয়")

### সিদ্ধান্ত পয়েন্ট
- `/system/info`：স্কিম A — ফ্রন্টএন্ড `/health` কল করবে (service-এ ইতিমধ্যে আছে)；স্কিম B — backend-dev service-এ `/api/system/info` এন্ডপয়েন্ট যোগ করবে (ভার্সন/এনভায়রনমেন্ট তথ্য রিটার্ন করে, HarmonyOS/Flutter-এর জন্যও কাজে লাগবে, প্রস্তাবিত)
- ServiceProxy：স্কিম A — admin-এর প্রয়োজনীয় admin-নির্দিষ্ট API-তে সংযোগ (যেমন অডিট লগ ফরওয়ার্ডিং)；স্কিম B — ক্লাস ডিলিট করে ডকুমেন্টে "Admin সরাসরি service-এ সংযুক্ত" ঘোষণা (বর্তমান প্রকৃত আর্কিটেকচার)

### কার্যকর করা হয়েছে (2026-08-16)
- **`/system/info` → স্কিম A (ফ্রন্টএন্ড `/health` কল করবে)**: SystemInfo.vue নেটিভ axios দিয়ে `GET /health` কল করে, `checks.database === 'ok'` চেক করে；`/health` রুট service পাশে `/api` প্রিফিক্স ছাড়া, Vite-এ নতুন `/health` প্রক্সি যোগ হয়েছে, Nginx-এ আগের `location /health` ইতিমধ্যে আছে；`stores/admin.ts`-এর ডেড কোডও `/health`-এ পরিবর্তন ✅
- **ServiceProxy → স্কিম B (রাখা + ডকুমেন্টেশন ব্যাখ্যা)**: ক্লাসটি রিজার্ভড ইনফ্রাস্ট্রাকচার হিসেবে রাখা হয়েছে (`ServiceProxy::init()` সেলফ-ইনিশিয়ালাইজেশন নিরীহ), `admin/config/app.php` কমেন্ট আপডেট করে "রিজার্ভড ইনফ্রাস্ট্রাকচার, বর্তমানে কোনো সক্রিয় কলার নেই" ✅

### গ্রহণযোগ্যতা
- [x] `/system/info` সিদ্ধান্ত বাস্তবায়িত: ফ্রন্টএন্ড থেকে কল সরানো হয়েছে (পরিবর্তে /health), কোনো 404 ঘোস্ট রিকোয়েস্ট নেই ✅
- [x] ServiceProxy সিদ্ধান্ত বাস্তবায়িত: ক্লাস রাখা হয়েছে এবং config কমেন্টে বর্তমান অবস্থা ব্যাখ্যা করা হয়েছে ✅

---

## Task 4: ডকুমেন্টেশন ব্যাকফিল ও তথ্যের সমন্বয়

### পটভূমি
- README-এর "14 控制器 / 45+ 端点" পুরনো (প্রকৃত 17 控制器 / 61 端点)
- `docs/superpowers/plans/`-এর প্রতিটি phase checkbox ব্যাকফিল হয়নি (কোড বাস্তবায়িত কিন্তু ডকুমেন্টে টিক নেই)
- HarmonyOS স্ট্যাটাস "UI 规划中" পুরনো (প্রকৃত ৬ পেজ + ApiClient প্রস্তুত)
- install.html / InstallController-এর ডিফল্ট `.../api/v1` config-এর ডিফল্ট `/api` (X-API-Version হেডার)-এর সাথে অসামঞ্জস্যপূর্ণ
- CacheService কমেন্টে দুই-স্তর ক্যাশ বলা, প্রকৃতপক্ষে তিন-স্তর (L1 মেমরি / APCu / Redis)

### ফাইল:
- Modify: `README.md` / `README.en.md` (কন্ট্রোলার সংখ্যা、এন্ডপয়েন্ট সংখ্যা、HarmonyOS স্ট্যাটাস、ক্যাশ লেয়ার)
- Modify: `admin/public/install.html` / `admin/app/controller/InstallController.php` (ভার্সন প্রিফিক্স তথ্যের সমন্বয়)
- Modify: `service/support/CacheService.php` (কমেন্ট সংশোধন)
- Optional: `docs/superpowers/plans/*.md` checkbox ব্যাকফিল

### কার্যকর করা হয়েছে (2026-08-16)
- README.md / README.en.md: 17 控制器 / 61 端点 / HarmonyOS 6 পেজ / 19 Vue পেজ / SPA সরাসরি সংযোগ — সব তথ্য আপডেট ✅
- install.html / InstallController: `/api/v1` ডিফল্ট → `/api` (X-API-Version হেডার মেকানিজম) ✅
- ৮টি phase plan-এর checkbox সব ব্যাকফিল ✅ (phase7 ছাড়া, সেটি অপেক্ষমাণ)

### গ্রহণযোগ্যতা
- [x] README ডেটা কোডের সাথে সামঞ্জস্যপূর্ণ (17 控制器 / 61 端点 / HarmonyOS 6 পেজ) ✅
- [x] ইনস্টল উইজার্ডের ভার্সন প্রিফিক্স X-API-Version মেকানিজমের সাথে সামঞ্জস্যপূর্ণ ✅

---

## পরবর্তী ফেজ পরিকল্পনা (Phase 8-10, এই প্ল্যানের বাইরে)

| Phase | বিষয়বস্তু | স্ট্যাটাস |
|---|---|---|
| Phase 8 | অ্যালার্ট মাল্টি-চ্যানেল বাস্তবায়ন: ads-alert-এ নতুন channel/ (Email SMTP、Webhook、SMS গেটওয়ে প্লেসহোল্ডার) —— Phase 5-এর বাকি ফাঁক পূরণ | শুরু করা বাকি |
| Phase 9 | HarmonyOS প্রকৃত ইন্টিগ্রেশন: ৬ পেজে ApiClient সংযোগ (বর্তমানে ০টি প্রকৃত কল, সব মক ডেটা) | শুরু করা বাকি |
| Phase 10 | গভীরকরণ ও商业化: 29 প্ল্যাটফর্ম প্রকৃত ইন্টিগ্রেশন、সিঙ্ক স্ট্যাটাস ভিজ্যুয়ালাইজেশন、কনভার্সন ডেটা ক্লোজড লুপ、Flutter/HarmonyOS CI প্যাকেজিং、মাল্টি-টেন্যান্ট SaaS কোটা | শুরু করা বাকি |
