# ভার্সন তুলনা

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| ভার্সন | লাইসেন্স | প্রাপ্তির উপায় |
|------|------|----------|
| **সিম্পলিফায়েড (Lite)** | ওপেন সোর্স (MIT) | GitHub পাবলিক রিপোজিটরি |
| **স্ট্যান্ডার্ড (Standard)** | কমার্শিয়াল লাইসেন্স | erik@erik.xyz-এ যোগাযোগ করুন |
| **ফুল (Full)** | কমার্শিয়াল লাইসেন্স | erik@erik.xyz-এ যোগাযোগ করুন |

---

## ফিচার তুলনা

### বেসিক ফিচার

| ফিচার | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| অথেনটিকেশন (লগইন/Token রিফ্রেশ/বর্তমান ইউজার) | ✅ | ✅ | ✅ |
| প্ল্যাটফর্ম ম্যানেজমেন্ট (29 প্ল্যাটফর্ম লিস্ট + OAuth) | ✅ | ✅ | ✅ |
| অ্যাকাউন্ট ম্যানেজমেন্ট (CRUD + সিঙ্ক) | ✅ | ✅ | ✅ |
| বিজ্ঞাপন প্ল্যান (CRUD + স্টার্ট/স্টপ + বাল্ক) | ✅ | ✅ | ✅ |
| রিপোর্ট (ড্যাশবোর্ড + কাস্টম + CSV/Excel/PDF এক্সপোর্ট) | ✅ | ✅ | ✅ |
| হেলথ চেক + API ডকুমেন্টেশন + ক্যাপচা | ✅ | ✅ | ✅ |
| ডেটা সিঙ্ক (Campaign + Report) | ✅ | ✅ | ✅ |

### ডেলিভারি ম্যানেজমেন্ট

| ফিচার | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| বিজ্ঞাপন গ্রুপ (CRUD + স্টার্ট/স্টপ) | — | ✅ | ✅ |
| বিজ্ঞাপন ক্রিয়েটিভ (লিস্ট + ডিটেইল) | — | ✅ | ✅ |
| অ্যাড গ্রুপ/ক্রিয়েটিভ ডেটা সিঙ্ক | — | ✅ | ✅ |

### মনিটরিং ও নোটিফিকেশন

| ফিচার | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| অ্যালার্ট রুল ইঞ্জিন (7 মেট্রিক/4 কন্ডিশন/3 স্কোপ) | — | ✅ | ✅ |
| অ্যালার্ট রেকর্ড + কনফার্ম + আনরিড কাউন্ট | — | ✅ | ✅ |
| নোটিফিকেশন সেন্টার (লিস্ট/রিড/সব রিড) | — | ✅ | ✅ |

### অ্যাডভান্সড ফিচার

| ফিচার | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| অটো বিডিং রুল ইঞ্জিন (3 অ্যাকশন/কুলডাউন) | — | — | ✅ |
| অডিয়েন্স টার্গেটিং টেমপ্লেট (সাধারণ JSON Schema) | — | — | ✅ |
| বিজ্ঞাপন অ্যাসেট লাইব্রেরি (আপলোড/গ্যালারি/প্রিভিউ) | — | — | ✅ |
| বাজেট অ্যালার্ট (থ্রি-লেভেল 50/80/100%) | — | — | ✅ |
| ক্যাম্পেইন ক্যালেন্ডার (Gantt ভিজুয়ালাইজেশন) | — | — | ✅ |
| ক্রস-প্ল্যাটফর্ম অ্যাট্রিবিউশন (5 মডেল/30 দিন লুকব্যাক) | — | — | ✅ |

---

## সিকিউরিটি প্রোটেকশন তুলনা

| প্রোটেকশন আইটেম | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| CORS হোয়াইটলিস্ট | ✅ | ✅ | ✅ |
| সিকিউরিটি রেসপন্স হেডার (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| ভার্সন রাউটিং (X-API-Version) | ✅ | ✅ | ✅ |
| API রেট লিমিট (স্লাইডিং উইন্ডো) | ✅ | ✅ | ✅ |
| SQL ইনজেকশন ডিটেকশন (প্যাটার্ন ম্যাচিং) | ✅ | ✅ | ✅ |
| ইনপুট ফিল্টারিং (strip_tags + trim) | ✅ | ✅ | ✅ |
| ট্রান্সমিশন এনক্রিপশন/ডিক্রিপশন (X-Encrypted) | ✅ | ✅ | ✅ |
| JWT Bearer অথেনটিকেশন | ✅ | ✅ | ✅ |
| XSS অ্যাটাক ডিটেকশন (11 প্যাটার্ন) | — | ✅ | ✅ |
| পাথ ট্রাভার্সাল ডিটেকশন (7 প্যাটার্ন) | — | ✅ | ✅ |
| Header ইনজেকশন ডিটেকশন | — | ✅ | ✅ |
| Body সাইজ লিমিট (10 MiB) | — | ✅ | ✅ |
| Content-Type হোয়াইটলিস্ট | — | ✅ | ✅ |
| ক্লায়েন্ট উৎস আইডেন্টিফিকেশন (8 এন্ড) | — | ✅ | ✅ |
| লগইন থ্রটলিং (5 বার→15 মিনিট) | — | ✅ | ✅ |
| রেসপন্স টাইম মনিটরিং (X-Response-Time) | — | ✅ | ✅ |
| Origin/Referer ভ্যালিডেশন | — | — | ✅ |
| রিপ্লে অ্যাটাক প্রোটেকশন (Nonce+Timestamp) | — | — | ✅ |
| কনকারেন্ট সেশন লিমিট (সর্বোচ্চ 3টি) | — | — | ✅ |
| CSRF Token (Admin এন্ড) | — | — | ✅ |
| SSRF প্রোটেকশন (OAuth হোয়াইটলিস্ট) | — | — | ✅ |
| লগ ডেটা ডিসেনসিটাইজেশন | — | — | ✅ |
| JWT IP/UA বাইন্ডিং | — | — | ✅ |

---

## মিডলওয়্যার চেইন তুলনা

### Service এন্ড

| Lite (7 লেয়ার) | Standard (11 লেয়ার) | Full (15 লেয়ার) |
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

### Admin এন্ড

| Lite (1 লেয়ার) | Standard (4 লেয়ার) | Full (5 লেয়ার) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## ক্রন টাস্ক তুলনা

| টাস্ক | ফ্রিকোয়েন্সি | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55min | ✅ | ✅ | ✅ |
| DataSyncTask | 10min | ✅ (শুধু Campaign+Report) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5min | — | ✅ | ✅ |
| BidCheckTask | 10min | — | — | ✅ |
| BudgetCheckTask | 15min | — | — | ✅ |

---

## ডেটাবেস টেবিল তুলনা

| ক্যাটাগরি | টেবিল নাম | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| বেসিক | erik_tenants | ✅ | ✅ | ✅ |
| অ্যাকাউন্ট | erik_platform_accounts | ✅ | ✅ | ✅ |
| | erik_auth_tokens | ✅ | ✅ | ✅ |
| ডেলিভারি | erik_campaigns | ✅ | ✅ | ✅ |
| | erik_report_metrics | ✅ | ✅ | ✅ |
| | erik_report_extras | ✅ | ✅ | ✅ |
| | erik_ad_groups | — | ✅ | ✅ |
| | erik_creatives | — | ✅ | ✅ |
| অ্যালার্ট | erik_alert_rules | — | ✅ | ✅ |
| | erik_alert_logs | — | ✅ | ✅ |
| নোটিফিকেশন | erik_notifications | — | ✅ | ✅ |
| বিডিং | erik_bid_rules | — | — | ✅ |
| | erik_bid_logs | — | — | ✅ |
| টার্গেটিং | erik_targeting_templates | — | — | ✅ |
| অ্যাসেট | erik_assets | — | — | ✅ |
| অ্যাট্রিবিউশন | erik_conversions | — | — | ✅ |
| | erik_attribution_results | — | — | ✅ |
| সিস্টেম | erik_sync_errors | ✅ | ✅ | ✅ |
| ম্যানেজমেন্ট | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **মোট** | | **8** | **13** | **18** |

---

## ফ্রন্টএন্ড পেজ তুলনা

### Vue Admin SPA

| পেজ | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| লগইন | ✅ | ✅ | ✅ |
| ড্যাশবোর্ড | ✅ | ✅ | ✅ |
| অ্যাকাউন্ট লিস্ট + বাইন্ডিং | ✅ | ✅ | ✅ |
| বিজ্ঞাপন প্ল্যান | ✅ | ✅ | ✅ |
| রিপোর্ট এক্সপোর্ট | ✅ | ✅ | ✅ |
| ইউজার ম্যানেজমেন্ট | ✅ | ✅ | ✅ |
| অডিট লগ | ✅ | ✅ | ✅ |
| বিজ্ঞাপন গ্রুপ | — | ✅ | ✅ |
| বিজ্ঞাপন ক্রিয়েটিভ | — | ✅ | ✅ |
| রিপোর্ট অ্যানালাইসিস (ECharts) | — | ✅ | ✅ |
| অ্যালার্ট রুল | — | ✅ | ✅ |
| অ্যালার্ট রেকর্ড | — | ✅ | ✅ |
| নোটিফিকেশন সেন্টার | — | ✅ | ✅ |
| অটো বিডিং | — | — | ✅ |
| অ্যাসেট লাইব্রেরি | — | — | ✅ |
| ক্যাম্পেইন ক্যালেন্ডার | — | — | ✅ |
| অ্যাট্রিবিউশন অ্যানালাইসিস | — | — | ✅ |
| **মোট** | **7** | **13** | **17** |

### Flutter

| পেজ | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| লগইন | ✅ | ✅ | ✅ |
| ড্যাশবোর্ড | ✅ | ✅ | ✅ |
| বিজ্ঞাপন প্ল্যান (লিস্ট+ডিটেইল) | ✅ | ✅ | ✅ |
| ডেটা রিপোর্ট | ✅ | ✅ | ✅ |
| প্ল্যাটফর্ম অ্যাকাউন্ট | ✅ | ✅ | ✅ |
| অ্যালার্ট ম্যানেজমেন্ট | ✅ | ✅ | ✅ |
| বিজ্ঞাপন গ্রুপ | — | ✅ | ✅ |
| বিজ্ঞাপন ক্রিয়েটিভ | — | ✅ | ✅ |
| রিপোর্ট অ্যানালাইসিস | — | ✅ | ✅ |
| নোটিফিকেশন সেন্টার | — | ✅ | ✅ |
| অটো বিডিং | — | — | ✅ |
| **মোট** | **6** | **10** | **11** |

---

## API এন্ডপয়েন্ট তুলনা

| মডিউল | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| সিস্টেম (health/ping/docs/captcha) | 6 | 6 | 6 |
| অথেনটিকেশন (login/me/refresh) | 3 | 3 | 3 |
| প্ল্যাটফর্ম (list/oauthUrl/callback) | 3 | 3 | 3 |
| অ্যাকাউন্ট (index/show/destroy/sync) | 4 | 4 | 4 |
| বিজ্ঞাপন প্ল্যান (CRUD/toggle/batch) | 6 | 6 | 6 |
| বিজ্ঞাপন গ্রুপ (CRUD/toggle) | — | 5 | 5 |
| ক্রিয়েটিভ (index/show) | — | 2 | 2 |
| রিপোর্ট (summary/custom/export×2) | 4 | 4 | 4 |
| রিপোর্ট (calendar/budget/attribution/models) | — | — | 4 |
| অ্যালার্ট (rules CRUD + logs + acknowledge + unread) | — | 7 | 7 |
| নোটিফিকেশন (index/unread/read/readAll) | — | 4 | 4 |
| অটো বিডিং (CRUD + logs) | — | — | 5 |
| টার্গেটিং টেমপ্লেট (CRUD) | — | — | 5 |
| অ্যাসেট লাইব্রেরি (index/upload/show/destroy) | — | — | 4 |
| **মোট** | **26** | **44** | **62** |

---

## টেক স্ট্যাক

তিন ভার্সন শেয়ার্ড ইউনিফাইড টেক স্ট্যাক ব্যবহার করে:

| লেয়ার | প্রযুক্তি |
|----|------|
| ব্যাকএন্ড ফ্রেমওয়ার্ক | webman v2, PHP 8.2+ |
| ডেটাবেস | MySQL 8.0 (InnoDB, utf8mb4) |
| ক্যাশ | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| অথেনটিকেশন | erikwang2013/jwt-webman |
| ID জেনারেশন | erikwang2013/snowflake-php |
| ID এনকোডিং | erikwang2013/hashids |
| ফ্রন্টএন্ড | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| ডিপ্লয়মেন্ট | Docker + Nginx + Docker Compose |

---

## আপগ্রেড পাথ

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
