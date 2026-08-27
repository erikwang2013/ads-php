# Phase 10: গভীরকরণ ও কমার্শিয়ালাইজেশন Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**Goal:** Phase 7-9 কন্ট্র্যাক্ট ও মাল্টি-চ্যানেল ভিত্তিতে সিঙ্ক স্ট্যাটাস ভিজুয়ালাইজেশন, কনভার্সন ডেটা ক্লোজড-লুপ, মোবাইল CI প্যাকেজিং, মাল্টি-টেন্যান্ট SaaS কোটা — চারটি গভীরকরণ ক্ষমতা বাস্তবায়ন।

**উৎস:** Phase 7 টিম অডিটের অনুমিত দিকনির্দেশ (researcher: ES/রিড-রাইট সেপারেশন/কিউ বাস্তবায়ন, Flutter/HarmonyOS CI, 29 প্ল্যাটফর্ম বাস্তব ইন্টিগ্রেশন, SaaS বিলিং কোটা, কনভার্সন ডেটা ক্লোজড-লুপ, সিঙ্ক স্ট্যাটাস ভিজুয়ালাইজেশন, AI বিডিং)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## বর্তমান অবস্থা (যাচাই করা হয়েছে)

| প্রার্থী সাব-আইটেম | বর্তমান অবস্থা |
|---|---|
| সিঙ্ক স্ট্যাটাস ভিজুয়ালাইজেশন | `ads_sync_errors` টেবিল + `RetrySyncTask` (3 বার রিট্রাই, 5^n মিনিট ব্যাকঅফ) আছে; **সিঙ্ক ফেইলিউর রেট ও লেটেন্সি দেখানোর ফ্রন্টএন্ড পেজ/API নেই** |
| কনভার্সন ডেটা ক্লোজড-লুপ | `ads_conversions` + `ads_attribution_results` টেবিল আছে, অ্যাট্রিবিউশন ইঞ্জিন বাস্তবায়িত; **কনভার্সন ডেটা সংগ্রহ এন্ট্রি নেই** (রিটার্ন/ট্র্যাকিং API) |
| মোবাইল CI | `ci.yml` শুধু PHP সিনট্যাক্স→PHPUnit→vue-tsc→Docker; **Flutter/HarmonyOS বিল্ড প্যাকেজিং নেই** |
| মাল্টি-টেন্যান্ট SaaS | `ads_tenants` টেবিল + TenantIdentify মিডলওয়্যার আছে; **বিলিং/কোটা/ইউসেজ স্ট্যাটস নেই** |
| ES বাস্তবায়ন | scout.php কনফিগারড + webman-scout ডিপেন্ডেন্সি যুক্ত; **docker-compose-এ ES সার্ভিস নেই** |
| 29 প্ল্যাটফর্ম বাস্তব ইন্টিগ্রেশন | 29টি অ্যাডাপ্টার কোড সম্পূর্ণ; **স্যান্ডবক্স/ক্রেডেনশিয়াল ইন্টিগ্রেশন রেকর্ড নেই** (বহিরাগত ক্রেডেনশিয়াল প্রয়োজন, ম্যানুয়াল আইটেম হিসেবে চিহ্নিত) |

## Task 1: সিঙ্ক স্ট্যাটাস ভিজুয়ালাইজেশন

### Files:
- Modify: `service/plugin/ads-api/controller/v1/DashboardController.php` বা নতুন `service/plugin/ads-api/controller/v1/SyncController.php` + route
- Create: `admin/public/web/src/api/sync.ts`
- Create: `admin/public/web/src/views/sync/SyncStatus.vue` (বা সিস্টেম পেজে যুক্ত)

### ডিজাইন পয়েন্ট
- এন্ডপয়েন্ট: `GET /api/sync/status` (অ্যাকাউন্ট মাত্রা: last_sync_at, সফলতার হার, আজকের ব্যর্থ সংখ্যা, pending রিট্রাই সংখ্যা) + `GET /api/sync/errors` (পেজিনেটেড এরর লিস্ট, last_error/retry_count/next_retry_at সহ)
- ফ্রন্টএন্ড: সিঙ্ক স্ট্যাটাস পেজ (টেবিল + সামারি কার্ড), শুধুমাত্র Full/Standard ভার্সন লাইন
- ডেটা উৎস: ads_platform_accounts (last_sync_at) + ads_sync_errors

## Task 2: কনভার্সন ডেটা সংগ্রহ API

### Files:
- Modify: `service/plugin/ads-api/controller/v1/` (নতুন ConversionController + route)
- Create: `service/plugin/ads-report/service/ConversionService.php`

### ডিজাইন পয়েন্ট
- এন্ডপয়েন্ট: `POST /api/conversions` (ব্যবসায়িক পক্ষ কনভার্সন রিটার্ন: platform/campaign_id/order_id/conversion_time/value/currency/channel) + `GET /api/conversions` (কোয়েরি)
- ভ্যালিডেশন: campaign_id অস্তিত্ব, টাকা অ-নেগেটিভ, সময় ফরম্যাট; ads_conversions-এ লেখা
- অ্যাট্রিবিউশন লিংকেজ: রিটার্নের পর অ্যাট্রিবিউশন রিক্যালকুলেশন ট্রিগার করা যায় (বা বিদ্যমান AttributionEngine-এর সময়সূচি/ম্যানুয়াল রিক্যালকুলেশন উল্লেখ)
- ফ্রন্টএন্ড: অ্যাট্রিবিউশন রিপোর্ট পেজে "কনভার্সন রিটার্ন" ব্যাখ্যা/ডেমো (ঐচ্ছিক)

## Task 3: মোবাইল CI প্যাকেজিং

### Files:
- Modify: `.github/workflows/ci.yml` (নতুন job: Flutter build (web + linux বা apk) + HarmonyOS স্ট্যাটিক চেক)

### ডিজাইন পয়েন্ট
- Flutter: `flutter pub get && flutter analyze && flutter build web` (বা apk, রিপোজিটরি বর্তমান অবস্থা অনুযায়ী বিল্ডযোগ্য টার্গেট বেছে নিন; flutter এনভায়রনমেন্ট সীমিত হলে dart analyze)
- HarmonyOS: স্ট্যান্ডার্ড Linux CI টুলচেইন নেই, স্ট্যাটিক চেক ব্যাখ্যা বা স্কিপ (মার্ক)
- বিদ্যমান php-tests job-এর সাথে প্যারালাল, মূল প্রক্রিয়া ব্লক করে না

## Task 4: মাল্টি-টেন্যান্ট SaaS কোটা (MVP)

### Files:
- Modify: `service/plugin/ads-tenant/` (নতুন QuotaService)
- Modify: `service/plugin/ads-api/config/route.php` + controller

### ডিজাইন পয়েন্ট
- ডেটা: ads_tenants-এ quota ফিল্ড বা নতুন টেবিল ads_tenant_quotas (plan/account_limit/campaign_limit/sync_quota)
- ভ্যালিডেশন পয়েন্ট: অ্যাকাউন্ট বাইন্ডিং সংখ্যা, প্ল্যান তৈরি সংখ্যা, দৈনিক সিঙ্ক সংখ্যা (AccountController/CampaignController/DataSyncTask এন্ট্রিতে চেক)
- এন্ডপয়েন্ট: `GET /api/tenant/quota` (ইউসেজ + কোটা)
- ফ্রন্টএন্ড: সিস্টেম পেজে কোটা ইউসেজ দেখানো (ঐচ্ছিক, MVP-তে শুধু API হতে পারে)
- ভার্সন লাইন: কোটা ডিফল্ট lite/standard/full অনুযায়ী ভিন্ন (config কনস্ট্যান্ট)

## গ্রহণযোগ্যতা (Task অনুযায়ী)
- [ ] Task 1: sync API এন্ডপয়েন্ট ব্যবহারযোগ্য, ফ্রন্টএন্ড পেজ দেখায়, টেস্ট কভারেজ
- [ ] Task 2: conversions রিটার্ন API লেখা/পড়া যায়, ভ্যালিডেশন কার্যকর, টেস্ট কভারেজ
- [ ] Task 3: CI নতুন job পাস (বা স্কিপ আইটেম স্পষ্ট মার্ক)
- [ ] Task 4: quota API সঠিক রিটার্ন, লিমিট ওভার ইন্টারসেপ্ট কার্যকর, টেস্ট কভারেজ
- [ ] সব: `php vendor/bin/phpunit --no-coverage` সব পাস, vue-tsc পাস

## এই পর্বের বাইরে (বহিরাগত সম্পদ প্রয়োজন)
- 29 প্ল্যাটফর্ম বাস্তব ইন্টিগ্রেশন (প্রতিটি প্ল্যাটফর্মের ক্রেডেনশিয়াল/স্যান্ডবক্স প্রয়োজন)
- ES সার্ভিস বাস্তবায়ন (docker-compose-এ ES সার্ভিস ও ইন্ডেক্স ইনিশিয়ালাইজেশন প্রয়োজন)
- AI বিডিং পরামর্শ (মডেল/ডেটা প্রস্তুতি)
