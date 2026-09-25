# Ads Platform — মাল্টি-প্ল্যাটফর্ম বিজ্ঞাপন ব্যবস্থাপনা সিস্টেম

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## প্রজেক্ট মাসকট「阿章」

<p align="center"><img src="docs/diagrams/svg/pet.svg" width="160" alt="প্রকল্প মাসকট 阿章"></p>

**阿章** (zhāng, «অক্টোপাস») এই প্রকল্পের মাসকট: একটি অক্টোপাস। তার আটটি বাহু একসাথে ছড়িয়ে পড়ে — প্রতিটি বাহু নিজের কাজ সামলায় আর একই সময়ে 29টি প্ল্যাটফর্মের দিকে পৌঁছে যায়, ঠিক যা এই সিস্টেম করে।

| রূপ | তাৎপর্য | সংশ্লিষ্ট বাস্তবায়ন |
|------|------|---------|
| আটটি বাহু | আটটি প্লাগইন, প্রত্যেকে নিজের কাজ সামলায়, সবই সমান্তরালে | ads-account / ads-alert / ads-api / ads-platform / ads-report / ads-storage / ads-task / ads-tenant |
| বাহুর সাকার | ডেটা পথেই আটকে জমা হতে থাকে | 29টি প্ল্যাটফর্ম → একটিই `/api/v1` |
| দুই চোখ সামনের দিকে | একসাথে একাধিক এন্ডে নজর | Vue Admin / Flutter / HarmonyOS তিন এন্ডে একটিই API |
| চোখের পলক + শ্বাস | পর্যায়ক্রমিক পোলিং ও নিরবচ্ছিন্ন প্রহরা | 6টি নির্ধারিত টাস্ক (3/5/10/10/15/55 মিনিট) চক্রাকারে ডেটা সংগ্রহ; 22টি সুরক্ষা নীরবে চলে |
| বাইরের বলয়ে 29টি নোড | 29টি প্ল্যাটফর্ম সবসময় অনলাইনে | 16টি চীন + 13টি আন্তর্জাতিক, প্রত্যেকের দিকে নজর |

阿章 কোডে একীভূত হয়েছে: অ্যাডমিন প্যানেলের **লগইন পেজ** ও **favicon**, Service এন্ডের **`/docs` API ডকুমেন্টেশন পেজ**, এবং সব ডকুমেন্টেশন পেজ।

> SVG সোর্স [docs/diagrams/svg/pet.svg](docs/diagrams/svg/pet.svg) (টেক্সট নেই, 13 ভাষার ডকুমেন্টেশন একই ফাইল শেয়ার করে; SMIL চোখের পলক ও শ্বাস অ্যানিমেশন সহ)

---

## সংক্ষিপ্ত বিবরণ

**Ads Platform** একটি মাল্টি-প্ল্যাটফর্ম বিজ্ঞাপন ব্যবস্থাপনা সিস্টেম যা **29টি বিজ্ঞাপন প্ল্যাটফর্ম** (16টি দেশীয় + 13টি আন্তর্জাতিক) সংযুক্ত করে, বিজ্ঞাপন ডেলিভারি এবং ক্রস-প্ল্যাটফর্ম ডেটা রিপোর্টের একীভূত ব্যবস্থাপনা সহ।

- **ক্যাম্পেইন ব্যবস্থাপনা** — OAuth অ্যাকাউন্ট অনুমোদন, ক্যাম্পেইন/অ্যাড গ্রুপ/ক্রিয়েটিভের প্ল্যাটফর্ম জুড়ে একীভূত ব্যবস্থাপনা
- **রিপোর্ট** — ক্রস-প্ল্যাটফর্ম মেট্রিক সমষ্টি, CSV/Excel/PDF রপ্তানি, 5 মডেল অ্যাট্রিবিউশন
- **স্মার্ট ডেলিভারি** — অটো-বিডিং, বাজেট অ্যালার্ট, ক্যাম্পেইন ক্যালেন্ডার (Gantt), অ্যাসেট লাইব্রেরি
- **গ্লোবাল অ্যাক্সিলারেশন** — CDN-এর মাধ্যমে অ্যাসেট ডেলিভারি (মাল্টি-ড্রাইভার: লোকাল / Alibaba Cloud OSS / Tencent Cloud COS / S3-কমপ্যাটিবল, অ্যাডমিন থেকে মাল্টি-প্রোভাইডার কনফিগ)
- **মনিটরিং ও অ্যালার্ট** — অ্যালার্ট রুল ইঞ্জিন, মাল্টি-চ্যানেল পুশ, নির্ধারিত অটো-সিঙ্ক
- **মাল্টি-এন্ড অ্যাক্সেস** — ওয়েব অ্যাডমিন (Vue 3), Flutter PC/Mobile, HarmonyOS
- **স্থিতিশীলতা ও নির্ভরযোগ্যতা** — প্ল্যাটফর্ম কল সার্কিট ব্রেকার/ডিগ্রেডেশন/টাইমআউট, 3-স্তর ক্যাশ, উচ্চ সমবর্তী অপ্টিমাইজেশন, 22টি নিরাপত্তা সুরক্ষা
- **আন্তর্জাতিককরণ** — 12 ভাষায় ডকুমেন্টেশন, দ্বিভাষিক UI (ZH/EN)
- **প্রজেক্ট মাসকট** — অক্টোপাস「阿章」, আটটি বাহু সমান্তরালে, 24 ঘন্টা 29টি প্ল্যাটফর্ম পাহারা দেয় ([pet.svg](docs/diagrams/svg/pet.svg))

> আর্কিটেকচার ডিজাইন → [docs/architecture.bn.md](docs/architecture.bn.md)  
> ফিচার মডিউল → [docs/features.bn.md](docs/features.bn.md)  
> API ডকুমেন্টেশন → [docs/api.bn.md](docs/api.bn.md) | hg/apidoc: `http://127.0.0.1:8788/apidoc`  
> ভার্সন তুলনা → [docs/versions.bn.md](docs/versions.bn.md)（Lite ওপেন সোর্স / Standard ও Full-এর জন্য erik@erik.xyz-এ যোগাযোগ করুন）

### সাপোর্টেড প্ল্যাটফর্ম

#### চীন (16)
| প্ল্যাটফর্ম | অ্যাডাপ্টার | প্রমাণীকরণ |
|------|--------|------|
| 巨量引擎 | Juliang | OAuth2 Access-Token |
| 百度营销 | Baidu | OAuth2 + 信封签名 |
| 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5 |
| 腾讯广告 | Tencent | OAuth2 + nonce |
| 快手磁力引擎 | Kuaishou | OAuth2 URL参数 |
| 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer |
| 微博粉丝通 | Weibo | OAuth2 Bearer |
| B站花火 | Bilibili | OAuth2 Bearer |
| 优酷广告 | Youku | OAuth2 + MD5 |
| 美团广告 | Meituan | OAuth2 Bearer |
| 知乎广告 | Zhihu | OAuth2 Bearer |
| 360推广 | Qihoo360 | API Key + Sign |
| 搜狗推广 | Sogou | API Key + Sign |
| 友盟 | Umeng | API Key + MD5 |
| 京东京准通 | Jingdong | OAuth2 + MD5 |
| 拼多多广告 | Pinduoduo | OAuth2 + 自定义Sign |

#### আন্তর্জাতিক (13)
| প্ল্যাটফর্ম | অ্যাডাপ্টার | প্রমাণীকরণ |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 URL参数 |
| TikTok Ads | Tiktok | OAuth2 Access-Token |
| LinkedIn Ads | Linkedin | OAuth2 Bearer |
| Snapchat Ads | Snapchat | OAuth2 Bearer |
| Pinterest Ads | Pinterest | OAuth2 Bearer |
| Twitter/X Ads | Twitter | OAuth2 Bearer |
| Amazon Ads | Amazon | OAuth2 + Profile |
| The Trade Desk | TheTradeDesk | HMAC-SHA256 |
| Spotify Ads | Spotify | OAuth2 Bearer |
| Twitch Ads | Twitch | OAuth2 Bearer + ClientId |
| Netflix Ads | Netflix | OAuth2 client_credentials |

---

## টেক স্ট্যাক

| স্তর | প্রযুক্তি | বিবরণ |
|----|------|------|
| সার্ভার | webman v2 + PHP 8.4+ | 8টি প্লাগইন, 76 API এন্ডপয়েন্ট |
| ডেটাবেস | MySQL 8.0 | 29টি টেবিল, ads_ প্রিফিক্স, Snowflake BIGINT প্রাইমারি কী |
| ক্যাশ | Redis 7 | থ্রি-লেভেল ক্যাশ (L1 মেমরি/L2 APCu/L3 Redis)、রেট লিমিট কাউন্টার、Pub/Sub、মেসেজ কিউ |
| সার্চ | Elasticsearch | webman-scout অটো ইনডেক্স সিঙ্ক (কনফিগারড) |
| অ্যাডমিন প্যানেল | webman-admin v2 + Vue 3 + TypeScript + Element Plus | PHP ব্যাকএন্ড (পোর্ট 8789)、SPA সরাসরি বিজনেস API (পোর্ট 8788) সংযোগ, 21টি পেজ, ECharts ভিজুয়ালাইজেশন |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | PC/Mobile রেসপনসিভ, Desktop Shell লেআউট, 12টি পেজ |
| HarmonyOS | ArkTS + ArkUI | 6টি পেজ বাস্তবায়িত, HTTP ক্লায়েন্ট প্রস্তুত |
| ডিপ্লয়মেন্ট | Docker + Nginx + GHCR | Docker Compose ওয়ান-ক্লিক স্টার্ট, GitHub Actions অটো বিল্ড ও পুশ |

## আর্কিটেকচার ডায়াগ্রাম

![সিস্টেম আর্কিটেকচার ডায়াগ্রাম](docs/diagrams/svg/architecture.bn.svg)

### রিকোয়েস্ট ফ্লো ডায়াগ্রাম

![রিকোয়েস্ট ফ্লো ডায়াগ্রাম](docs/diagrams/svg/request-flow.bn.svg)

### ফাংশনাল মডিউল ডায়াগ্রাম

![ফাংশনাল মডিউল ডায়াগ্রাম](docs/diagrams/svg/functional-modules.bn.svg)

### ডেটা লাইফসাইকেল ডায়াগ্রাম

![ডেটা লাইফসাইকেল ডায়াগ্রাম](docs/diagrams/svg/data-lifecycle.bn.svg)

> সম্পূর্ণ সংস্করণে সব বিস্তারিত নোট, Admin পাইপলাইন, ক্রন গ্যান্ট চার্ট, ক্যাশ স্টেট মেশিন → [docs/diagrams/](docs/diagrams/) |

> বিস্তারিত আর্কিটেকচার ব্যাখ্যা, সিকিউরিটি আর্কিটেকচার, হাই-কনকারেন্সি ডিজাইন দেখুন [আর্কিটেকচার ডিজাইন ডকুমেন্ট](docs/architecture.bn.md) | ঐতিহাসিক ডিজাইন স্পেক দেখুন [design.bn.md](docs/superpowers/specs/design.bn.md)

## আর্কিটেকচার ব্যাখ্যা

- **`service/`** — webman v2 ইউজার-সাইড বিজনেস API সার্ভিস, পোর্ট **8788**। বিজ্ঞাপন প্ল্যাটফর্ম ইন্টিগ্রেশন, OAuth অনুমোদন, ডেটা সিঙ্ক, রিপোর্ট ইঞ্জিন, অ্যালার্ট মনিটরিং ইত্যাদি ব্যবসায়িক লজিক হ্যান্ডেল করে।
- **`admin/`** — webman-admin v2 আলাদা অ্যাডমিন প্যানেল, পোর্ট **8789**। PHP ব্যাকএন্ড (অথেনটিকেশন/অথরাইজেশন, ইউজার ম্যানেজমেন্ট, সিস্টেম কনফিগ) এবং Vue 3 SPA ফ্রন্টএন্ড রয়েছে।
- **অ্যাডমিন প্যানেল ও বিজনেস সার্ভিসের যোগাযোগ** — Vue SPA axios (baseURL `/api`) দিয়ে সরাসরি service API-তে সংযোগ করে; admin-নির্দিষ্ট রুট (`/api/admin/*`) admin PHP ব্যাকএন্ড (8789) সরবরাহ করে, Nginx পাথ অনুযায়ী বিভক্ত করে।
- **ডেভেলপমেন্ট মোড** — Vite dev server (পোর্ট 5173) `/api`-কে service:8788-এ প্রক্সি করে; admin PHP ব্যাকএন্ড 8789-এ session অথেনটিকেশন ও SPA স্ট্যাটিক সার্ভিস দেয়।
- **প্রোডাকশন মোড** — Nginx `/`-কে admin:8789 (অ্যাডমিন প্যানেল SPA)-তে, `/api/`-কে service:8788 (বিজনেস API)-তে রাউট করে।

## Erik Stack ইন্টিগ্রেশন

| প্যাকেজ | ব্যবহার |
|----|------|
| `erikwang2013/snowflake-php` | ডিস্ট্রিবিউটেড Snowflake ID জেনারেশন |
| `erikwang2013/hashids` | API ID প্যারামিটার এনক্রিপশন/ডিক্রিপশন |
| `erikwang2013/jwt-webman` | JWT অথেনটিকেশন টোকেন |
| `erikwang2013/encryption` | API লেয়ারে সেনসিটিভ ডেটা এনক্রিপশন/ডিক্রিপশন |
| `erikwang2013/encryptable` | DB ফিল্ড-লেভেল অটো এনক্রিপশন/ডিক্রিপশন |
| `erikwang2013/webman-scout` | Elasticsearch ডেটা সিঙ্ক |
| `erikwang2013/season` | দেশের ফ্ল্যাগ আইডেন্টিফিকেশন |
| `erikwang2013/poster-php` | স্লাইডার ক্যাপচা (লগইন প্রোটেকশন) |
| `hg/apidoc` | API ডকুমেন্টেশন অটো জেনারেশন (অ্যানোটেশন + Web UI) |

## আন্তর্জাতিকীকরণ

সব ইন্টারফেস **中文 (zh-CN)** / **English (en)** দ্বিভাষিক সুইচ সাপোর্ট করে:

| এন্ড | প্রযুক্তি | সুইচ পদ্ধতি |
|----|------|---------|
| Admin | vue-i18n v9 | TopBar ভাষা ড্রপডাউন মেনু, localStorage পার্সিস্টেন্স |
| Service API | `erik\support\I18n` | Accept-Language রিকোয়েস্ট হেডার / `?lang=` প্যারামিটার |
| Flutter | AppLocalizations + Delegate | সিস্টেম ভাষা অটো ডিটেকশন |
| HarmonyOS | StringResources | `setLang()` সুইচ |

## নিরাপত্তা

### Service এন্ড (14 লেয়ার গ্লোবাল + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware（রুট লেয়ার）

### Admin এন্ড (10 লেয়ার গ্লোবাল + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck（রুট লেয়ার）

### প্রোটেকশন ক্যাপাবিলিটি ওভারভিউ (22টি)

| ক্যাটাগরি | প্রোটেকশন আইটেম | বিবরণ |
|------|--------|------|
| ইনপুট ডিটেকশন | XSS (11 প্যাটার্ন) | script/iframe/event handler/javascript:/data: |
| | পাথ ট্রাভার্সাল (7 প্যাটার্ন) | ../ / null byte / /etc/passwd / .env / .git |
| | Header ইনজেকশন | CRLF ডিটেকশন |
| | Body সাইজ লিমিট | 10 MiB |
| | Content-Type হোয়াইটলিস্ট | JSON/Form/Multipart/Plain |
| | SQL ইনজেকশন | UNION/DROP/ALTER প্যাটার্ন ডিটেকশন |
| অথেনটিকেশন | JWT Token বাইন্ডিং | IP + User-Agent hash ভ্যালিডেশন |
| | Token রিফ্রেশ + ব্ল্যাকলিস্ট | পুরনো Token অটো ইনভ্যালিড |
| | লগইন থ্রটলিং | 5 বার ব্যর্থ → 15 মিনিট লক (Redis) |
| | কনকারেন্ট সেশন লিমিট | প্রতি ইউজার সর্বোচ্চ 3টি অ্যাক্টিভ Token |
| | ক্যাপচা | স্লাইডার ক্যাপচা (5 মিনিট ভ্যালিড, 5px টলারেন্স) |
| রিকোয়েস্ট ভ্যালিডেশন | CORS হোয়াইটলিস্ট | প্রোডাকশন ডোমেইন হোয়াইটলিস্ট |
| | Origin/Referer ভ্যালিডেশন | ক্রস-অরিজিন উৎস ভ্যালিডেশন |
| | CSRF Token | Admin এন্ড session token ভ্যালিডেশন |
| | রিপ্লে অ্যাটাক প্রোটেকশন | Nonce + Timestamp ±5min (নন-ব্রাউজার এন্ড) |
| | API রেট লিমিট | স্লাইডিং উইন্ডো 60 বার/60s |
| | SSRF প্রোটেকশন | OAuth redirect_uri হোয়াইটলিস্ট |
| রেসপন্স হেডার | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | ক্লিকজ্যাকিং প্রোটেকশন + HTTPS ফোর্স |
| | X-Content-Type-Options | nosniff |
| ডেটা প্রোটেকশন | ট্রান্সমিশন এনক্রিপশন | EncryptionMiddleware (X-Encrypted) |
| | স্টোরেজ এনক্রিপশন | Encryptable (DB ফিল্ড-লেভেল) |
| | লগ ডিসেনসিটাইজেশন | password/token/secret → \*\*\* |

### সিকিউরিটি আর্কিটেকচার ডায়াগ্রাম

![সিকিউরিটি আর্কিটেকচার ডায়াগ্রাম](docs/diagrams/svg/security.bn.svg)

**ডিফেন্স ইন ডেপথ**：বাইরের লেয়ার (Nginx) → এন্ট্রি গার্ড (5 লেয়ার মিডলওয়্যার) → আইডেন্টিটি অথেনটিকেশন (7টি) → ইনপুট ভ্যালিডেশন (4টি) → ফ্রিকোয়েন্সি কন্ট্রোল → ডেটা এনক্রিপশন → অডিট ট্রেসেবিলিটি

**অথেনটিকেশন**：সার্ভার ও admin উভয়েই `admin_users` টেবিল + bcrypt হ্যাশ ব্যবহার করে, JWT 24h + refresh রোটেশন

**অডিট**：সব অপারেশন IP / User-Agent / Client-Platform / অপারেশন ডিটেইল রেকর্ড করে

**সেকেন্ডারি কনফার্মেশন**：ডিলিট/আনবাইন্ড/বাল্ক অপারেশনে "ইনপুট কনফার্মেশন ওয়ার্ড" প্যাটার্ন ব্যবহার হয় (`GlobalConfirm` + `useConfirmStore`)

---

## অ্যাডভান্সড ফিচার

| ফিচার | বিবরণ | প্রযুক্তি |
|------|------|------|
| অ্যাসেট লাইব্রেরি | ইমেজ/ভিডিও আপলোড ম্যানেজমেন্ট, গ্যালারি প্রিভিউ, URL কপি | AssetController + Vue গ্যালারি |
| বাজেট অ্যালার্ট | দৈনিক বাজেট খরচ রিয়েল-টাইম ট্র্যাকিং, থ্রি-লেভেল অ্যালার্ট (50/80/100%) | BudgetAlertService + 15min Cron |
| ক্যাম্পেইন ক্যালেন্ডার | ক্রস-প্ল্যাটফর্ম Gantt চার্ট, মাস/সপ্তাহ ভিউ, প্ল্যাটফর্ম অনুযায়ী কালার | CalendarService + Vue Gantt |
| ক্রস-প্ল্যাটফর্ম অ্যাট্রিবিউশন | 5 মডেল অ্যাট্রিবিউশন (first/last/linear/time_decay/position_based)、30 দিন লুকব্যাক | AttributionEngine + ECharts |
| প্ল্যাটফর্ম কল রেজিলিয়েন্স | প্ল্যাটফর্ম-ভিত্তিক সার্কিট ব্রেকার স্টেট মশিন (5 ব্যর্থতা → OPEN → 30s হাফ-ওপন প্রোব), ডিগ্রেডেশন fast-fail, 29 অ্যাডাপ্টার টাইমআউট অডিট | CircuitBreaker + GuardedAdapter |
| CDN অ্যাসেট অ্যাক্সিলারেশন | অবজেক্ট স্টোরেজ মাল্টি-ড্রাইভার (local/oss/cos/s3), অ্যাডমিনে CDN প্রোভাইডার ম্যানেজমেন্ট, প্রিসাইন ডাইরেক্ট আপলোড, ডিলিটে অটো ক্যাশ পার্জ | ads-storage প্লাগইন + CdnProviderController |

---

## হাই কনকারেন্সি

| অপটিমাইজেশন | সমাধান | ফাইল |
|------|------|------|
| DB রিড/রাইট সেপারেশন | প্রাইমারি `shared` + রিড-অনলি রেপ্লিকা `read_replica`、SELECT অটো রেপ্লিকায় রাউট | `config/database.php` |
| DB কানেকশন পুল | `PDO::ATTR_PERSISTENT` পার্সিস্টেন্ট কানেকশন + টাইমজোন ইনিশিয়ালাইজেশন প্রিহিট | `config/database.php` |
| Redis কানেকশন পুল | `persistent` পার্সিস্টেন্ট কানেকশন + রিড/রাইট সেপারেশন `readonly` কনফিগ | `config/redis.php` |
| থ্রি-লেভেল ক্যাশ | L1 প্রসেস মেমরি → L2 APCu শেয়ার্ড মেমরি → L3 Redis | `support/CacheService.php` |
| মেসেজ কিউ অ্যাসিঙ্ক | Redis List 4 চ্যানেল (sync/report/export/notification) | `support/AsyncJobService.php` |
| Nginx গ্রেডেড রেট লিমিট | 30r/s + burst 20 + 20 কনকারেন্ট কানেকশন + keepalive 32 | `docker/nginx/admin.conf` |
| হরাইজন্টাল স্কেলিং | upstream মাল্টি-ইনস্ট্যান্স + ফেইলওভার + sticky session | `docker/nginx/admin.conf` |
| CDN অ্যাক্সিলারেশন | স্ট্যাটিক রিসোর্স `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## কুইক স্টার্ট

### ওয়ান-ক্লিক ওয়েব ইনস্টলেশন (প্রস্তাবিত)

সার্ভিস চালু করার পর ব্রাউজারে `/install` খুলে ইনস্টলেশন উইজার্ডে যান:

```bash
# 启动管理后台 (端口 8789)
cd admin && composer install && php start.php start

# 打开浏览器访问 http://localhost:8789/install
# 在安装向导中填写数据库信息、管理员账户，点击「开始安装」
```

ইনস্টলেশন উইজার্ড আপনাকে ওয়েবপেজে ধাপে ধাপে গাইড করবে:
1. **ডেটাবেস কানেকশন** — MySQL হোস্ট、পোর্ট、ডেটাবেস নাম、ইউজারনেম/পাসওয়ার্ড填、কানেকশন টেস্ট সাপোর্ট
2. **Redis কনফিগ** — Redis কানেকশন তথ্য填 (ঐচ্ছিক)
3. **অ্যাডমিন অ্যাকাউন্ট** — ব্যাকএন্ড লগইন ইউজারনেম、পাসওয়ার্ড、ডিসপ্লে নাম সেট
4. **ওয়ান-ক্লিক ইনস্টল** — অটো ডেটাবেস তৈরি、`install.sql` এক্সিকিউট করে 29টি টেবিল তৈরি ও সিড ডেটা লেখা、অ্যাডমিন পাসওয়ার্ড আপডেট

ইনস্টলেশন শেষে `/` খুলে অ্যাডমিন প্যানেলে যান, সেট করা ইউজারনেম ও পাসওয়ার্ড দিয়ে লগইন করুন।

### Docker (প্রোডাকশনের জন্য প্রস্তাবিত)

```bash
# 启动全部服务 (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# 初始化数据库（创建表 + 种子数据）
make db-init

# 访问
# 管理后台: http://localhost
# 安装向导: http://localhost/install
# API: http://localhost/api/v1（সংস্করণটি URL পাথে স্থির, Header-এ পাঠানো হয় না）
```

### লোকাল ডেভেলপমেন্ট

```bash
# 服务端 (端口 8788)
cd service && composer install && php start.php start

# 管理后台 (端口 5173)
cd admin/public/web && npm install && npm run dev

# Flutter App
cd apps/flutter && flutter run -d chrome  # Web PC
# HarmonyOS App
# 使用 DevEco Studio 打开 apps/harmonyos 目录
cd apps/flutter && flutter run -d android # Mobile

# TypeScript 检查
cd admin/public/web && npx vue-tsc --noEmit   # 零错误
```

ব্যবহার নির্দেশিকা → [docs/usage.bn.md](docs/usage.bn.md)
---

## প্রজেক্ট স্ট্রাকচার

```
ads-php/
├── service/                           # ইউজার-সাইড বিজনেস সার্ভিস (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (76 এন্ডপয়েন্ট, ভার্সনড রুট /api/v1)
│   │   │   ├── controller/v1/         # 21টি কন্ট্রোলার (admin/ সাবডিরেক্টরি সহ)
│   │   │   ├── middleware/            # 15টি মিডলওয়্যার
│   │   │   ├── config/route.php       # রুট ডেফিনিশন
│   │   ├── ads-platform/              # প্ল্যাটফর্ম অ্যাডাপ্টার কোর
│   │   │   ├── adapter/               # 29টি প্ল্যাটফর্ম অ্যাডাপ্টার
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # SQL মাইগ্রেশন + পারফরম্যান্স ইনডেক্স
│   │   ├── ads-account/               # OAuth অ্যাকাউন্ট ম্যানেজমেন্ট
│   │   ├── ads-task/                  # শিডিউলড টাস্ক (6 cron)
│   │   ├── ads-alert/                 # অ্যালার্ট মনিটরিং ইঞ্জিন + বাজেট সতর্কতা
│   │   ├── ads-report/                # রিপোর্ট ইঞ্জিন (CSV/Excel/PDF) + অ্যাট্রিবিউশন ইঞ্জিন + ক্যাম্পেইন ক্যালেন্ডার
│   │   ├── ads-tenant/                # মাল্টি-টেন্যান্ট ম্যানেজমেন্ট
│   │   └── ads-storage/               # স্টোরেজ অ্যাবস্ট্রাকশন লেয়ার (local/OSS/COS/S3) + CDN প্রোভাইডার
│   ├── public/                        # স্ট্যাটিক রিসোর্স (webman বিল্ট-ইন স্ট্যাটিক হ্যান্ডলিং)
│   │   └── img/pet.svg                # প্রজেক্ট মাসকট「阿章」, /docs পেজে প্রদর্শিত
│   ├── scripts/backfill-assets.php    # বিদ্যমান অ্যাসেট অবজেক্ট স্টোরেজে ব্যাকফিল
│   ├── support/                       # Erik Stack ইউটিলিটি ক্লাস
│   │   ├── ControllerTrait.php        # কন্ট্রোলারের কমন trait
│   │   ├── JwtService.php             # JWT র‍্যাপার ক্লাস
│   │   ├── CacheService.php           # Redis ক্যাশ সার্ভিস
│   │   ├── ExceptionHandler.php       # API এক্সেপশন হ্যান্ডলার
│   │   └── ApiResponse.php            # ইউনিফাইড রেসপন্স ফরম্যাট
│   ├── config/                        # গ্লোবাল কনফিগ (DB/Redis/Log/Middleware)
│   ├── tests/                         # PHPUnit টেস্ট (288 tests)
│   │   ├── Unit/                      # ইউনিট টেস্ট (Middleware, Task, Engines)
│   │   └── Integration/               # ইন্টিগ্রেশন টেস্ট (Auth, Health, API)
│   └── start.php                      # সার্ভিস এন্ট্রি
├── admin/                             # আলাদা অ্যাডমিন প্যানেল (webman-admin v2 :8789)
│   ├── public/web/
│   │   ├── public/pet.svg             # প্রজেক্ট মাসকট「阿章」, favicon + লগইন পেজ
│   │   ├── src/
│   │   │   ├── views/                 # 21টি Vue পেজ
│   │   │   │   ├── dashboard/         # ড্যাশবোর্ড (ECharts)
│   │   │   │   ├── campaign/          # বিজ্ঞাপন প্ল্যান
│   │   │   │   ├── adgroup/           # অ্যাড গ্রুপ
│   │   │   │   ├── creative/          # বিজ্ঞাপন ক্রিয়েটিভ
│   │   │   │   ├── account/           # অ্যাকাউন্ট ম্যানেজমেন্ট + বাইন্ডিং
│   │   │   │   ├── asset/             # অ্যাসেট লাইব্রেরি
│   │   │   │   ├── report/            # রিপোর্ট অ্যানালাইসিস + রপ্তানি + অ্যাট্রিবিউশন + ক্যাম্পেইন ক্যালেন্ডার
│   │   │   │   ├── alert/             # অ্যালার্ট রুল + রেকর্ড
│   │   │   │   ├── notification/      # নোটিফিকেশন সেন্টার
│   │   │   │   ├── sync/              # সিঙ্ক স্ট্যাটাস
│   │   │   │   ├── bid/               # অটো বিডিং রুল
│   │   │   │   ├── cdn/               # CDN প্রোভাইডার
│   │   │   │   ├── login/             # লগইন পেজ (阿章)
│   │   │   │   └── system/            # ইউজার ম্যানেজমেন্ট + অডিট লগ + সিস্টেম ইনফো
│   │   │   ├── api/                   # 15টি API ক্লায়েন্ট
│   │   │   ├── stores/                # 5টি Pinia Store
│   │   │   └── components/            # শেয়ার্ড কম্পোনেন্ট (ListPageLayout সহ 8টি)
│   │   └── dist/                      # বিল্ড আউটপুট (কমিট হয় না, CI বিল্ড করে)
│   ├── app/                           # PHP ব্যাকএন্ড (controller/middleware)
│   └── config/                        # Admin কনফিগ
├── apps/
│   ├── flutter/                       # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/              # 12টি ফিচার পেজ + Shell লেআউট
│   │       ├── config/menu_config.dart # দুই-স্তরের মেনু কনফিগ
│   │       ├── router.dart            # GoRouter (ShellRoute + রুট গার্ড)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API Client প্রস্তুত)
├── docker/                            # Docker ও Nginx কনফিগ
├── .github/workflows/                 # CI (সিনট্যাক্স→টেস্ট→TS→Docker) + CD (বিল্ড ও পুশ)
├── docs/                              # ডিজাইন ডকুমেন্ট, ইমপ্লিমেন্টেশন প্ল্যান, Skills
│   ├── architecture.md                # আর্কিটেকচার ডিজাইন (আর্কিটেকচার ডায়াগ্রাম/রিকোয়েস্ট ফ্লো ডায়াগ্রাম/সিকিউরিটি আর্কিটেকচার ডায়াগ্রাম সহ)
│   ├── features.md                    # ফিচার ডিজাইন (21টি মডিউল, ফাংশনাল মডিউল ডায়াগ্রাম সহ)
│   ├── usage.md                       # ব্যবহার নির্দেশিকা (ডেটা লাইফসাইকেল ডায়াগ্রাম সহ)
│   ├── diagrams/svg/                  # 5 ধরনের ডায়াগ্রাম × 13 ভাষা + pet.svg প্রজেক্ট মাসকট
│   └── skills/                        # 143টি পুনঃব্যবহারযোগ্য প্রজেক্ট স্কিল
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## API এন্ডপয়েন্ট

> সব API এন্ডপয়েন্ট ডেফিনিশন দেখুন [docs/api.bn.md](docs/api.bn.md)（রিকোয়েস্ট/রেসপন্স উদাহরণ、এরর কোড、রেট লিমিট পলিসি সহ）。
> hg/apidoc অনলাইন ডকুমেন্ট: সার্ভিস চালু করার পর `http://127.0.0.1:8788/apidoc` খুলুন

## ডেটাবেস

**নেমিং কনভেনশন**: টেবিল প্রিফিক্স `ads_`，প্রাইমারি কী `BIGINT UNSIGNED PRIMARY KEY`（নো অটো-ইনক্রিমেন্ট，Snowflake ID）、ইঞ্জিন InnoDB、ক্যারেক্টার সেট utf8mb4

| ক্যাটাগরি | টেবিল নাম | ব্যবহার |
|------|------|------|
| বেসিক | `ads_tenants` | মাল্টি-টেন্যান্সি |
| অ্যাকাউন্ট | `ads_platform_accounts`, `ads_auth_tokens` | OAuth প্ল্যাটফর্ম অ্যাকাউন্ট |
| ডেলিভারি | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | বিজ্ঞাপন ডেলিভারি হায়ারার্কি |
| রিপোর্ট | `ads_report_metrics`, `ads_report_extras` | ইউনিফাইড রিপোর্ট মেট্রিক |
| অ্যাসেট | `ads_assets` | ক্রিয়েটিভ অ্যাসেট লাইব্রেরি |
| CDN | `ads_cdn_providers` | CDN প্রোভাইডার কনফিগ (ক্রেডেনশিয়াল এনক্রিপ্টেড) |
| টার্গেটিং | `ads_targeting_templates` | অডিয়েন্স টার্গেটিং টেমপ্লেট |
| অ্যাট্রিবিউশন | `ads_conversions`, `ads_attribution_results` | কনভার্সন ট্র্যাকিং + অ্যাট্রিবিউশন ফলাফল |
| বিডিং | `ads_bid_rules`, `ads_bid_logs` | অটো বিডিং রুল + হিস্টোরি |
| অ্যালার্ট | `ads_alert_rules`, `ads_alert_logs` | অ্যালার্ট মনিটরিং |
| নোটিফিকেশন | `ads_notifications` | সাইট-ইন নোটিফিকেশন |
| সিস্টেম | `ads_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | সিঙ্ক এরর、RBAC、অডিট |

---

## ক্রন টাস্ক

| টাস্ক | ফ্রিকোয়েন্সি | ফাংশন |
|------|------|------|
| TokenRefreshTask | প্রতি 55 মিনিট | এক্সপায়ারড OAuth Token স্ক্যান করে অটো রিফ্রেশ |
| DataSyncTask | প্রতি 10 মিনিট | প্রতিটি প্ল্যাটফর্মের প্ল্যান+অ্যাডগ্রুপ+ক্রিয়েটিভ+রিপোর্ট পুল করে ইউনিফাইড টেবিলে লেখে, ক্যাশ ক্লিয়ার করে |
| AlertCheckTask | প্রতি 5 মিনিট | অ্যাক্টিভ অ্যালার্ট রুল স্ক্যান করে থ্রেশহোল্ড মূল্যায়ন করে পুশ ট্রিগার করে |
| BidCheckTask | প্রতি 10 মিনিট | অটো বিডিং রুল স্ক্যান করে মেট্রিক কোয়েরি করে বাজেট অ্যাডজাস্টমেন্ট/স্টার্ট-স্টপ এক্সিকিউট করে |
| BudgetCheckTask | প্রতি 15 মিনিট | ডেলিভারিতে থাকা প্ল্যান স্ক্যান করে দৈনিক বাজেট খরচ ট্র্যাক করে থ্রি-লেভেল অ্যালার্ট (50/80/100%) |
| RetrySyncTask | প্রতি 3 মিনিট | ব্যর্থ সিঙ্ক টাস্ক রিট্রাই (সর্বোচ্চ 3 বার, এক্সপোনেনশিয়াল ব্যাকঅফ) |

---

## টেস্টিং

```bash
cd service && ./vendor/bin/phpunit
# 288 测试 / 862 断言
```

**কভারেজ রেঞ্জ**: 14 মিডলওয়্যার · 8 প্লাগইন বিজনেস লেয়ার (অ্যাকাউন্ট/অ্যালার্ট/প্ল্যাটফর্ম/রিপোর্ট/টাস্ক/টেন্যান্ট/স্টোরেজ) · ইঞ্জিন (Bid/Alert/Attribution/Report) · API ইন্টিগ্রেশন টেস্ট (76 রুট) · UI E2E (18 পেজ)

```bash
# TypeScript 检查
cd admin/public/web && npx vue-tsc --noEmit   # 零错误

# Dart 分析
cd apps/flutter && dart analyze   # 零错误
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): অটো পাইপলাইন — **PHP Syntax → PHPUnit → TypeScript → Docker Build**

**CD** (`.github/workflows/deploy.yml`): ম্যানুয়াল ট্রিগার — **Docker Buildx → GHCR পুশ (service/admin/admin-php) → ডিপ্লয় নোটিফিকেশন**

`.github/dependabot.yml` প্রতি সপ্তাহে Composer + npm + Docker ডিপেন্ডেন্সি অটো আপডেট করে।

---

## Skills

`docs/skills/` — 11টি পুনঃব্যবহারযোগ্য প্রজেক্ট স্কিল:

| Skill | বিবরণ |
|------|------|
| `adapter-generator` | নতুন বিজ্ঞাপন প্ল্যাটফর্ম অ্যাডাপ্টার তৈরি (14 মেথড টেমপ্লেট) |
| `migration-generator` | SQL মাইগ্রেশন ফাইল তৈরি (ads_ প্রিফিক্স + BIGINT PK) |
| `erik-stack` | Erik Stack 8 প্যাকেজ ইন্টিগ্রেশন ব্যবহার গাইড |
| `admin-page-generator` | Vue3 অ্যাডমিন প্যানেল পেজ তৈরি |
| `api-endpoint` | RESTful API এন্ডপয়েন্ট যোগ |
| `tdd-workflow` | TDD ভ্যালিডেশন ফ্লো (টেস্ট→ইমপ্লিমেন্ট→সিনট্যাক্স→TypeScript→কমিট) |
| `security-middleware` | সিকিউরিটি মিডলওয়্যার লেয়ার যোগ (ইন্টারফেস স্পেক + রেজিস্ট্রেশন + বিদ্যমান চেইন রেফারেন্স) |
| `version-split` | Lite/Standard/Full তিন ভার্সন স্প্লিট (অপারেশন স্টেপ + কনফিগ আপডেট) |
| `cache-strategy` | থ্রি-লেভেল ক্যাশ স্ট্র্যাটেজি (L1 মেমরি/L2 APCu/L3 Redis + TTL পরামর্শ) |
| `attribution-setup` | ক্রস-প্ল্যাটফর্ম অ্যাট্রিবিউশন ইঞ্জিন (5 মডেল + API কল + ডেটা প্রস্তুতি) |
| `high-concurrency` | হাই কনকারেন্সি 8টি অপটিমাইজেশন (রিড/রাইট সেপারেশন/কানেকশন পুল/মেসেজ কিউ/হরাইজন্টাল স্কেলিং/CDN) |


## ওপেন সোর্স কঠিন, সাপোর্ট স্বাগতম

| WeChat | Alipay |
|:---:|:---:|
| ![微信](./docs/weixinpay.png "微信") | ![支付宝](./docs/alipay.png "支付宝") |

### 全球转账打赏 (Global Transfer Donation)

**收款人信息 (Beneficiary)**

| 字段 | 值 |
|------|-----|
| 收款人姓名 (Name) | WANG KEXUN |
| 收款账户号码 (Account No.) | 881015918251 |

**收款银行 (Receiving Bank) — ZA Bank**

| 字段 | 值 |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| 银行名称 (Bank Name) | ZA Bank Limited |
| 银行编号 (Bank Code) | 387 |
| 银行地址 (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **跨境汇款代理银行（如需，Correspondent Bank）**：此为代理（中转）银行信息，非收款银行信息，请向汇款银行查询是否需要提供。
>
> - **港元、人民币及美元**：Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · 银行编号 006 · Hong Kong Branch（分行编号 391）· Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **其他币种**：THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

### ক্রিপ্টো দান (Crypto Donation)

এই প্রকল্পটি আপনার কাজে লাগলে, দান করতে QR কোড স্ক্যান করুন, ধন্যবাদ!

| নেটওয়ার্ক (Network) | QR কোড (QR Code) | ওয়ালেট ঠিকানা (Wallet Address) |
|---|---|---|
| BNB Smart Chain (BEP20) | [<img src="./coin/1.jpg" width="150" alt="BNB Smart Chain (BEP20)">](./coin/1.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Tron (TRC20) | [<img src="./coin/2.jpg" width="150" alt="Tron (TRC20)">](./coin/2.jpg) | `TEdDHWLajt1XvqtPDWmQctdrJaC3pzZZzz` |
| Ethereum (ERC20) | [<img src="./coin/3.jpg" width="150" alt="Ethereum (ERC20)">](./coin/3.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Aptos | [<img src="./coin/4.jpg" width="150" alt="Aptos">](./coin/4.jpg) | `0x836e3780edfc3f7b2372b39e2a1a3a5d7adfaccd96c726f21cfde1b50dd68030` |
| Plasma | [<img src="./coin/5.jpg" width="150" alt="Plasma">](./coin/5.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Polygon POS | [<img src="./coin/6.jpg" width="150" alt="Polygon POS">](./coin/6.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Solana | [<img src="./coin/7.jpg" width="150" alt="Solana">](./coin/7.jpg) | `2hfhboHdmdrYsY25XfQSsEWxq5ip4EQsR7f4AzSRMUyr` |
| The Open Network (TON) | [<img src="./coin/8.jpg" width="150" alt="The Open Network (TON)">](./coin/8.jpg) | `UQB9kFQohzmXUir9QSSZq01iwl9aQZIDdBpNmDklljRtCoGK` |
| Arbitrum One | [<img src="./coin/9.jpg" width="150" alt="Arbitrum One">](./coin/9.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| AVAX C-Chain | [<img src="./coin/10.jpg" width="150" alt="AVAX C-Chain">](./coin/10.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |

---

## 许可证

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
