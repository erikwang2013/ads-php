# Phase 8: মাল্টি-চ্যানেল অ্যালার্ট বাস্তবায়ন Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**Goal:** Phase 5-এর অবশিষ্ট ঘাটতি পূরণ — `NotificationService`-এর email/sms চ্যানেল echo স্টাব থেকে বাস্তব বাস্তবায়নে (SMTP মেইল + সাধারণ Webhook) আপগ্রেড এবং চ্যানেল কনফিগারেশন সাপোর্ট। web চ্যানেল এবং Redis pub/sub ইতিমধ্যে বাস্তবায়িত, অপরিবর্তিত থাকবে।

**উৎস:** Phase 7 টিম অডিট সিদ্ধান্ত (researcher পরিকল্পনা তুলনা: একমাত্র স্পষ্ট "আংশিকভাবে সম্পন্ন" আইটেম = Phase 5 মাল্টি-চ্যানেল অ্যালার্ট, `ads-alert`-এ `channel/` ডিরেক্টরি নেই)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## বর্তমান অবস্থা (যাচাই করা হয়েছে)

| কম্পোনেন্ট | অবস্থা |
|---|---|
| `NotificationService::send()` | `match ($channel)` দিয়ে web/email/sms ডিসপ্যাচ; web বাস্তবে `ads_notifications`-এ লেখে, email/sms echo স্টাব |
| `AlertRule.channels` | JSON ফিল্ড + Eloquent cast array, ফ্রন্টএন্ড `['web','email','sms']` সাবমিট করে |
| Admin AlertRuleList.vue | চ্যানেল চেকবক্স UI আছে (web লকড, email/sms ঐচ্ছিক) |
| Redis pub/sub | `alert:new` চ্যানেলে পুশ বাস্তবায়িত |
| SMTP/মেইল কনফিগ | নেই (service/config-এ mail কনফিগ নেই) |

## Task 1: মেইল চ্যানেল (SMTP)

### Files:
- Create: `service/config/mail.php` (smtp host/port/user/pass/from/from_name/encryption, env-চালিত)
- Create: `service/plugin/ads-alert/service/channel/EmailChannel.php` (send(AlertLog, AlertRule) বাস্তবায়ন করে)
- Modify: `service/plugin/ads-alert/service/NotificationService.php` (email ব্রাঞ্চ EmailChannel কল করে, echo স্টাব সরায়)
- Modify: `service/composer.json` (PHPMailer বেছে নিলে ডিপেন্ডেন্সি যোগ; হালকা রাখতে ডিপেন্ডেন্সি-মুক্ত `mail()`/socket বাস্তবায়ন অগ্রাধিকার দিন, বাস্তবায়নকারী মূল্যায়ন করবে)

### ডিজাইন পয়েন্ট
- প্রাপক: AlertRule কনফিগ বা টেন্যান্ট কনফিগ থেকে পড়া (না থাকলে `email` ফিল্ড বা কনফিগ ডিফল্ট)
- সাবজেক্ট/বডি: sendWeb-এর টেক্সট টেমপ্লেট পুনঃব্যবহার ("অ্যালার্ট ট্রিগারড: {rule.name}" + মেট্রিক/বর্তমান মান/শর্ত/থ্রেশহোল্ড)
- ব্যর্থতা হ্যান্ডলিং: এক্সেপশন ক্যাচ করে লগ, অন্যান্য চ্যানেল ও মূল প্রক্রিয়া প্রভাবিত হয় না
- কনফিগ না থাকলে গ্রেসফুল ডিগ্রেডেশন (log নোটিশ, এক্সেপশন ছুঁড়ে প্রক্রিয়া বন্ধ করে না)

## Task 2: Webhook চ্যানেল

### Files:
- Create: `service/plugin/ads-alert/service/channel/WebhookChannel.php` (কনফিগার করা URL-এ POST JSON)
- Modify: `NotificationService::send()` match-এ `'webhook'` ব্রাঞ্চ যোগ

### ডিজাইন পয়েন্ট
- কনফিগ উৎস: AlertRule-এ `webhook_url` ফিল্ড সম্প্রসারণ (migration) বা channels কনফিগ; সর্বনিম্ন পরিবর্তনের জন্য AlertRule-এ `webhook_url` কলাম (nullable) অগ্রাধিকার
- পেলোড: `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`, অ্যালার্ট লেভেল/মেট্রিক/মান/থ্রেশহোল্ড/সময় সহ
- টাইমআউট ও রিট্রাই: কানেকশন টাইমআউট 5s, মোট টাইমআউট 10s, ব্যর্থ হলে লগ (রিট্রাই নেই, সহজ রাখুন)
- নিরাপত্তা: শুধুমাত্র http/https অনুমোদিত, ইন্ট্রানেট ঠিকানা ভ্যালিডেশন নেই (SSRF ঝুঁকি পরিচিত সীমাবদ্ধতা হিসেবে রেকর্ড করা, অথবা নন-ইন্ট্রানেট ভ্যালিডেশন — বাস্তবায়নকারী মূল্যায়ন করে রেকর্ড করবে)

## Task 3: SMS চ্যানেল (গেটওয়ে প্লেসহোল্ডার)

### Files:
- Modify: `NotificationService::sendSms` (প্লেসহোল্ডার রাখুন, ইন্টিগ্রেশন পয়েন্ট স্পষ্ট কমেন্ট করুন; বাস্তবায়নকারী লাইটওয়েট সমাধান পেলে তা করতে পারেন)

### ডিজাইন পয়েন্ট
- SMS গেটওয়ে (Aliyun/Tencent Cloud)-এর AK/SK ও পেমেন্ট প্রয়োজন, এই পর্যায়ে প্লেসহোল্ডার বাস্তবায়ন রাখা হয়, কমেন্টে ইন্টিগ্রেশন ধাপ উল্লেখ
- ফ্রন্টএন্ড UI-এর sms অপশন ঐচ্ছিক থাকবে কিন্তু ব্যাকএন্ড শুধু লগ করে (ব্যবহারকারীকে স্পষ্ট জানানো হয় গেটওয়ে কনফিগার করা হয়নি)

## Task 4: চ্যানেল কনফিগারেশন ও ফ্রন্টএন্ড

### Files:
- Modify: `admin/public/web/src/views/alert/AlertRuleList.vue` (webhook অপশন ও URL ইনপুট যোগ হলে)
- Modify: `service/plugin/ads-api/controller/v1/AlertController.php` (রুল তৈরি/আপডেটে webhook_url গ্রহণ)
- Modify: `service/plugin/ads-alert/model/AlertRule.php` (fillable/casts-এ webhook_url যোগ)
- Modify: `service/plugin/ads-alert/migration/create_alerts.sql` (ALTER বা ইনক্রিমেন্টাল স্ক্রিপ্ট ব্যাখ্যা)

### গ্রহণযোগ্যতা
- [ ] email চ্যানেল: SMTP কনফিগার করে অ্যালার্ট ট্রিগারে মেইল পাওয়া যায়; কনফিগ না থাকলে গ্রেসফুল ডিগ্রেডেশন
- [ ] webhook চ্যানেল: অ্যালার্ট ট্রিগারে কনফিগার করা URL-এ POST JSON, পেলোড ফিল্ড সম্পূর্ণ
- [ ] sms চ্যানেল: প্লেসহোল্ডার রাখা, লগ করা
- [ ] web চ্যানেল ও Redis pub/sub রিগ্রেশন প্রভাবিত নয়
- [ ] Admin রুল ফর্মে নতুন চ্যানেল ফিল্ড কনফিগারযোগ্য
- [ ] `php vendor/bin/phpunit --no-coverage` সব পাস
- [ ] নতুন/আপডেটেড টেস্ট: AlertEngine/NotificationService চ্যানেল ডিসপ্যাচ টেস্ট
