# Phase 9: HarmonyOS বাস্তব ইন্টিগ্রেশন টেস্টিং Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**Goal:** HarmonyOS ক্লায়েন্টের 6টি পৃষ্ঠাকে মক ডেটা থেকে বাস্তব API কল-এ (service :8788) স্থানান্তর, ApiClient-এর baseUrl হার্ডকোডিং সমস্যা ঠিক করা, লগইন বাস্তবায়ন, যাতে HarmonyOS ক্লায়েন্ট তৃতীয় কার্যকর ক্লায়েন্ট হয়।

**উৎস:** Phase 7 টিম অডিট (mobile-dev সমীক্ষা: HarmonyOS 6 পৃষ্ঠা সব মক ডেটা, 0টি বাস্তব কল, ApiClient baseUrl হার্ডকোডেড `http://127.0.0.1:8788/api`)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## বর্তমান অবস্থা (যাচাই করা হয়েছে)

| কম্পোনেন্ট | অবস্থা |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login সম্পূর্ণ; baseUrl হার্ডকোডেড `http://127.0.0.1:8788/api` (Flutter একই-অরিজিন আপেক্ষিক `/api` ব্যবহার করে); login()-এর কোনো কলার নেই |
| `pages/LoginPage.ets` | মক লগইন (setTimeout 1s জাম্প), কমেন্ট "replace with actual API call" |
| `pages/DashboardPage.ets` | `@State` হার্ডকোডেড মেট্রিক (totalCost=1250000 ইত্যাদি) |
| `pages/CampaignListPage.ets` | L187 কমেন্ট প্লেসহোল্ডার `/campaigns` |
| `pages/AccountPage.ets` | L138 কমেন্ট প্লেসহোল্ডার `/accounts` |
| `pages/AlertPage.ets` | L146 কমেন্ট প্লেসহোল্ডার `/alerts` |
| `pages/ReportPage.ets` | L242 কমেন্ট প্লেসহোল্ডার `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric ইতিমধ্যে আছে |
| i18n | StringResources.ets (15+ keys) |

## Task 1: ApiClient উন্নতকরণ

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### ডিজাইন পয়েন্ট
- **baseUrl কনফিগারযোগ্য করুন**: setBaseUrl রাখুন, ডিফল্ট `http://127.0.0.1:8788/api` (রিয়েল ডিভাইস/সিমুলেটরে LAN ঠিকানা নির্দেশ করতে হবে, কমেন্টে ব্যাখ্যা); Flutter-স্টাইল একই-অরিজিন আপেক্ষিক পাথ এড়িয়ে চলুন (ArkTS-এ অবশ্যই পরম URL)
- **ডুপ্লিকেট replayHeaders বাগ ঠিক করুন**: `{ ...this.replayHeaders(), ...this.replayHeaders() }` ডাবল স্প্রেড (get মেথডে) → একবার
- **login() রিটার্ন ভ্যালু অ্যাডাপ্ট**: service `POST /api/auth/login` রিটার্ন করে `{access_token, token_type, expires_in, user}` (`service/plugin/ads-api/controller/v1/AuthController.php`-এর প্রকৃত ফিল্ডের সাথে মিলিয়ে — access_token, token নয়, যাচাই করে `data.token` চেক ঠিক করুন)
- **এরর হ্যান্ডলিং**: resp.responseCode 2xx না হলে থ্রো/স্পষ্ট এরর মেসেজ; JSON.parse ব্যর্থতা প্রোটেকশন
- get/post/put/delete-এর `data.data` রিটার্ন (ApiResponse আনর্যাপ) বিদ্যমান কনভেনশন বজায় রাখুন

## Task 2: LoginPage বাস্তব লগইন

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### ডিজাইন পয়েন্ট
- `handleLogin()` → `ApiClient.login(username, password)` কল; সফল → setToken + Dashboard-এ জাম্প; ব্যর্থ → toast এরর মেসেজ
- লোডিং স্টেট isLoading ইতিমধ্যে আছে, পুনঃব্যবহার
- এরর মেসেজে service-এর message (ApiResponse envelope) অগ্রাধিকার, না থাকলে সাধারণ টেক্সট

## Task 3: পাঁচটি ব্যবসায়িক পৃষ্ঠা বাস্তবায়ন

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`, `CampaignListPage.ets`, `AccountPage.ets`, `AlertPage.ets`, `ReportPage.ets`

### এন্ডপয়েন্ট তুলনা (Phase 7 অডিটে নিশ্চিত, Flutter ফিক্সের সাথে সামঞ্জস্যপূর্ণ)
| পৃষ্ঠা | কল | পার্স |
|---|---|---|
| DashboardPage | `GET /reports/summary` (আজকের রেঞ্জ) | `data.overview` → totalCost/total_impressions/avg_ctr ইত্যাদি (টাকা ফেন, formatFen আছে) |
| CampaignListPage | `GET /campaigns` | `data.list` (পেজিনেশন) → Campaign model |
| AccountPage | `GET /accounts` | `data.list` → PlatformAccount model |
| AlertPage | `GET /alerts/logs` | `data.list` → AlertLog ফিল্ড (metric/rule_name/current_value/condition/threshold/status) |
| ReportPage | `GET /reports/custom` (date_start/date_end/dimensions[]/metrics[]) | `data.list` → ReportMetric |

### ডিজাইন পয়েন্ট
- পেজ লোড (aboutToAppear) এ রিকোয়েস্ট ট্রিগার; @State ডেটা খালি/0 দিয়ে ইনিশিয়ালাইজ, মক ভ্যালু না রাখা
- লোড ব্যর্থ হলে এরর + রিট্রাই দেখানো (Flutter পেজের এরর/রিট্রাই প্যাটার্ন অনুযায়ী)
- টাকার একক: service ফেন সংখ্যা রিটার্ন করে, formatFen হ্যান্ডেল করে
- **নতুন ফাইল নেই**, পৃষ্ঠাগুলোর বিদ্যমান UI স্ট্রাকচার ও i18n বজায়

## Task 4: ভ্যালিডেশন

### গ্রহণযোগ্যতা
- [ ] ApiClient-এ ডুপ্লিকেট replayHeaders নেই, login রিটার্ন ফিল্ড AuthController-এর সাথে সামঞ্জস্যপূর্ণ
- [ ] 6 পৃষ্ঠায় হার্ডকোডেড মক ব্যবসায়িক ডেটা অবশিষ্ট নেই (grep যাচাই)
- [ ] 5টি ব্যবসায়িক পৃষ্ঠার কল পাথ service রাউটের সাথে এক-এক মিল (`service/plugin/ads-api/config/route.php` এর সাথে মিলিয়ে)
- [ ] ArkTS সিনট্যাক্স চেক (এই পরিবেশে hvigor/DevEco টুলচেইন থাকলে চালান; না থাকলে ব্যাখ্যা ও ম্যানুয়াল যাচাই)
- [ ] রিগ্রেশন: service PHPUnit প্রভাবিত নয়
