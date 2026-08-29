# API ইন্টারফেস ডকুমেন্ট

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **hg/apidoc অনলাইন ডকুমেন্ট**: সার্ভিস চালু করার পর `http://127.0.0.1:8788/apidoc` খুলুন（Service + Admin ডুয়াল অ্যাপ সুইচ）  
> কনফিগ ফাইল: `service/config/plugin/hg/apidoc/app.php`

---

## সাধারণ কনভেনশন

### Base URL

```
http://your-domain.com/api
```

### আবশ্যক Headers

| Header | মান | বিবরণ |
|--------|----|------|
| `X-API-Version` | `v1` | API ভার্সন নম্বর (আবশ্যক, URL পাথে থাকে না) |
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | অপারেশন উৎস এন্ড (আবশ্যক) |
| `Authorization` | `Bearer <token>` | JWT অথেনটিকেশন টোকেন (লগইন/প্ল্যাটফর্ম লিস্ট/হেলথ চেক ছাড়া আবশ্যক) |

### রিপ্লে-প্রতিরোধ Header（নন-ব্রাউজার এন্ড）

| Header | বিবরণ |
|--------|------|
| `X-Nonce` | র্যান্ডম স্ট্রিং (প্রতিটি রিকোয়েস্টে ইউনিক) |
| `X-Timestamp` | Unix সেকেন্ড টাইমস্ট্যাম্প (±5 মিনিট উইন্ডো) |

### ঐচ্ছিক Headers

| Header | বিবরণ |
|--------|------|
| `X-Tenant-Id` | টেন্যান্ট ID (মাল্টি-টেন্যান্সি মোড) |
| `X-Encrypted` | `1` = রিকোয়েস্ট বডি ডিক্রিপ্ট করতে হবে, রেসপন্স বডি এনক্রিপ্ট করতে হবে |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| মান | বিবরণ |
|----|------|
| `application/json` | JSON রিকোয়েস্ট বডি (প্রস্তাবিত) |
| `application/x-www-form-urlencoded` | ফর্ম রিকোয়েস্ট |
| `multipart/form-data` | ফাইল আপলোড |

### রেসপন্স ফরম্যাট

**সফল**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**পেজিনেশন**:
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [ ... ],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

**এরর**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**হেলথ চেক**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### HTTP স্ট্যাটাস কোড

| স্ট্যাটাস কোড | অর্থ |
|--------|------|
| 200 | সফল |
| 204 | OPTIONS প্রি-ফ্লাইট সফল |
| 400 | রিকোয়েস্ট প্যারামিটার এরর、অসমর্থিত API ভার্সন |
| 401 | আনঅথেনটিকেটেড、Token এক্সপায়ারড、Token IP/UA অমিল |
| 403 | অ্যাক্সেস নিষিদ্ধ (XSS/পাথ ট্রাভার্সাল/CSRF/SQL ইনজেকশন/Origin অমিল) |
| 404 | রিসোর্স নেই |
| 429 | অতিরিক্ত রিকোয়েস্ট (রেট লিমিট/লগইন থ্রটলিং/কনকারেন্ট সেশন লিমিট) |
| 500 | সার্ভার এরর |
| 503 | সার্ভিস ডিগ্রেডেড (DB বা Redis আনঅ্যাভেইলেবল) |

### পেজিনেশন প্যারামিটার

| প্যারামিটার | ডিফল্ট | সর্বোচ্চ | বিবরণ |
|------|--------|--------|------|
| `page` | 1 | — | পেজ নম্বর |
| `per_page` | 20 | 100 | প্রতি পেজ আইটেম (অতিরিক্ত অটো ক্লিপ) |
| `sort` | `id` | — | সর্ট ফিল্ড (হোয়াইটলিস্টের মধ্যে থাকতে হবে) |

### ক্যাশ স্ট্র্যাটেজি

| এন্ডপয়েন্ট | TTL | লেয়ার |
|------|-----|-----|
| `/api/platforms` | 1 ঘণ্টা | L1 মেমরি → L2 APCu → L3 Redis |
| `/api/accounts` + `/api/accounts/:id` | 5 মিনিট | একই |
| `/api/reports/summary` | 5 মিনিট | একই |
| `/api/alerts/rules` | 2 মিনিট | একই |
| `/api/alerts/unread-count` | 30 সেকেন্ড | একই |

---

## মডিউল 1: সিস্টেম

### GET /health — হেলথ চেক

```
GET /health
```

**রেসপন্স**:
```json
{
  "status": "healthy",
  "timestamp": "2026-05-22T00:00:00+08:00",
  "checks": {
    "database": "ok",
    "redis": "ok"
  }
}
```

- `status`: `healthy` (200) বা `degraded` (503)
- কোনো অথেনটিকেশন লাগবে না, ভার্সন রাউটিংয়ে যায় না

---

### GET /ping — প্রোব

```
GET /ping
```

**রেসপন্স**: `{ "pong": true }`

---

### GET /docs — API ডকুমেন্টেশন

```
GET /docs
```

HTML ফরম্যাটে API ডকুমেন্টেশন পেজ রিটার্ন করে (অথেনটিকেশন ছাড়া)।

---

### GET /api/captcha/generate — ক্যাপচা জেনারেট

অথেনটিকেশন ছাড়া।

**রেসপন্স**:
```json
{
  "code": 0,
  "data": {
    "captcha_token": "aes-encrypted-token",
    "background": "base64...",
    "puzzle": "base64..."
  }
}
```

- token 5 মিনিট ভ্যালিড
- অফসেট টলারেন্স 5px

---

### POST /api/captcha/verify — ক্যাপচা ভেরিফাই

অথেনটিকেশন ছাড়া।

**রিকোয়েস্ট**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**রেসপন্স**: `{ "code": 0, "message": "验证通过" }`

---

## মডিউল 2: অথেনটিকেশন

### POST /api/auth/login — লগইন

অথেনটিকেশন ছাড়া।

**রিকোয়েস্ট**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**রেসপন্স**:
```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "username": "admin",
      "name": "超级管理员",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

- JWT Token 24 ঘণ্টা ভ্যালিড
- Token-এ IP + User-Agent hash এমবেড করা
- 5 বার ব্যর্থ → Redis লক 15 মিনিট

---

### GET /api/auth/me — বর্তমান ইউজার

**রিকোয়েস্ট হেডার**: `Authorization: Bearer <token>`

**রেসপন্স**:
```json
{
  "code": 0,
  "data": {
    "id": 1,
    "username": "admin",
    "name": "超级管理员",
    "email": "admin@example.com",
    "role": "admin",
    "tenant_id": 1
  }
}
```

---

### POST /api/auth/refresh — Token রিফ্রেশ

**রিকোয়েস্ট হেডার**: `Authorization: Bearer <old_token>`

**রেসপন্স**:
```json
{
  "code": 0,
  "message": "Token 已刷新",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

- পুরনো Token অটো ব্ল্যাকলিস্ট
- প্রতি ইউজার সর্বোচ্চ 3টি অ্যাক্টিভ Token

---

## মডিউল 3: প্ল্যাটফর্ম ও অ্যাকাউন্ট

### GET /api/platforms — প্ল্যাটফর্ম লিস্ট

অথেনটিকেশন ছাড়া। 1 ঘণ্টা ক্যাশ।

**রেসপন্স**:
```json
{
  "code": 0,
  "data": [
    { "code": "juliang", "name": "巨量引擎", "flag": "🇨🇳", "capabilities": ["campaign", "report"] },
    { "code": "meta", "name": "Meta Ads", "flag": "🇺🇸", "capabilities": ["campaign", "report"] }
  ]
}
```

---

### GET /api/platforms/:code/oauth-url — OAuth অথরাইজেশন URL

**প্যারামিটার**: `?redirect_uri=https://your-domain.com/callback`

**রেসপন্স**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` অবশ্যই SSRF হোয়াইটলিস্ট ভ্যালিডেশন পাস করতে হবে（`OAUTH_ALLOWED_REDIRECTS` এনভায়রনমেন্ট ভ্যারিয়েবল）

---

### POST /api/platforms/:code/callback — OAuth কলব্যাক

**রিকোয়েস্ট**: `{ "state": "...", "code": "..." }`

**রেসপন্স**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/accounts — অ্যাকাউন্ট লিস্ট

5 মিনিট ক্যাশ।

**প্যারামিটার**:

| প্যারামিটার | বিবরণ |
|------|------|
| `platform` | প্ল্যাটফর্ম কোড ফিল্টার |
| `page` | পেজ নম্বর |
| `per_page` | প্রতি পেজ আইটেম |

**রেসপন্স**: পেজিনেশন ফরম্যাট, list-এর প্রতিটি আইটেমে `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at` থাকে

---

### GET /api/accounts/:id — অ্যাকাউন্ট ডিটেইল

5 মিনিট ক্যাশ।

---

### DELETE /api/accounts/:id — অ্যাকাউন্ট আনবাইন্ড

---

### POST /api/accounts/:id/sync — ম্যানুয়াল সিঙ্ক

---

## মডিউল 4: বিজ্ঞাপন প্ল্যান

### GET /api/campaigns — প্ল্যান লিস্ট

**প্যারামিটার**:

| প্যারামিটার | বিবরণ | ঐচ্ছিক মান |
|------|------|--------|
| `platform` | প্ল্যাটফর্ম ফিল্টার | juliang, meta, google... |
| `status` | স্ট্যাটাস ফিল্টার | enabled, paused |
| `keyword` | নাম সার্চ | যেকোনো টেক্সট |
| `sort` | সর্ট ফিল্ড | id, name, platform, daily_budget, status, created_at |
| `page` | পেজ নম্বর | — |
| `per_page` | প্রতি পেজ আইটেম | ≤100 |

**রেসপন্স**: পেজিনেশন ফরম্যাট + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/campaigns — প্ল্যান তৈরি

**রিকোয়েস্ট**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**রেসপন্স**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- `daily_budget` একক：ফেন (20000 = ¥200.00)

---

### GET /api/campaigns/:id — প্ল্যান ডিটেইল

**রেসপন্স**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/campaigns/:id — প্ল্যান আপডেট

**রিকোয়েস্ট**: `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/campaigns/:id/toggle — প্ল্যান স্টার্ট/স্টপ

**রিকোয়েস্ট**: `{ "enabled": false }`

---

### POST /api/campaigns/batch/toggle — বাল্ক স্টার্ট/স্টপ

**রিকোয়েস্ট**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**রেসপন্স**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## মডিউল 5: বিজ্ঞাপন গ্রুপ

### GET /api/ad-groups — অ্যাড গ্রুপ লিস্ট

**প্যারামিটার**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/ad-groups — অ্যাড গ্রুপ তৈরি

**রিকোয়েস্ট**:
```json
{
  "campaign_id": 1,
  "name": "测试广告组",
  "bid_amount": 100,
  "bid_type": "cpc",
  "targeting": { "age": { "min": 18, "max": 45 } },
  "targeting_template_id": "hashids-encoded-template-id"
}
```

- `targeting_template_id`: ঐচ্ছিক, টার্গেটিং টেমপ্লেট থেকে targeting JSON লোড করে মার্জ করে

### GET /api/ad-groups/:id — অ্যাড গ্রুপ ডিটেইল

### PUT /api/ad-groups/:id — অ্যাড গ্রুপ আপডেট

### POST /api/ad-groups/:id/toggle — অ্যাড গ্রুপ স্টার্ট/স্টপ

---

## মডিউল 6: ক্রিয়েটিভ

### GET /api/creatives — ক্রিয়েটিভ লিস্ট

**প্যারামিটার**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/creatives/:id — ক্রিয়েটিভ ডিটেইল

---

## মডিউল 7: রিপোর্ট

### GET /api/reports/summary — ড্যাশবোর্ড সামারি

5 মিনিট ক্যাশ।

**প্যারামিটার**: `date_start`, `date_end`

**রেসপন্স**:
```json
{
  "code": 0,
  "data": {
    "overview": { "cost": 123456, "impressions": 10000, ... },
    "by_platform": [ ... ],
    "daily": [ ... ]
  }
}
```

---

### GET /api/reports/custom — কাস্টম রিপোর্ট

**প্যারামিটার**:

| প্যারামিটার | বিবরণ |
|------|------|
| `dimensions[]` | ডাইমেনশন: date, platform, campaign |
| `metrics[]` | মেট্রিক: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | শুরুর তারিখ |
| `date_end` | শেষের তারিখ |
| `platform` | প্ল্যাটফর্ম ফিল্টার |

---

### GET /api/reports/export — রিপোর্ট এক্সপোর্ট

**প্যারামিটার**: `format=csv`, `date_start`, `date_end`, `metrics[]`

ফাইল ডাউনলোড রিটার্ন করে (CSV UTF-8 BOM বা Excel .xls)。

---

### GET /api/reports/export-dashboard — ড্যাশবোর্ড PDF এক্সপোর্ট

---

### GET /api/reports/calendar — ক্যাম্পেইন ক্যালেন্ডার

**প্যারামিটার**: `date_start`, `date_end`, `platform`

**রেসপন্স**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/reports/budget-alerts — বাজেট অ্যালার্ট

**রেসপন্স**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/reports/attribution — অ্যাট্রিবিউশন অ্যানালাইসিস

**প্যারামিটার**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**রেসপন্স**:
```json
{
  "code": 0,
  "data": {
    "total_conversions": 42,
    "total_value": 123456.78,
    "by_campaign": [ { "campaign_id": 1, "credit": 5000.00 } ]
  }
}
```

---

### GET /api/reports/attribution/models — অ্যাট্রিবিউশন মডেল লিস্ট

**রেসপন্স**: `[{ code: "last_touch", name: "末次触点", description: "..." }]`

মোট 5টি মডেল।

---

## মডিউল 8: অ্যালার্ট

### GET /api/alerts/rules — অ্যালার্ট রুল লিস্ট

2 মিনিট ক্যাশ।

**প্যারামিটার**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/alerts/rules — অ্যালার্ট রুল তৈরি

**রিকোয়েস্ট**:
```json
{
  "name": "花费超限",
  "metric": "cost",
  "condition": "gt",
  "threshold": 100000,
  "scope": "tenant",
  "platform": null,
  "campaign_id": null,
  "channels": ["web"]
}
```

### PUT /api/alerts/rules/:id — অ্যালার্ট রুল আপডেট

### DELETE /api/alerts/rules/:id — অ্যালার্ট রুল ডিলিট

### GET /api/alerts/logs — অ্যালার্ট রেকর্ড

**প্যারামিটার**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/alerts/logs/:id/acknowledge — অ্যালার্ট কনফার্ম

### GET /api/alerts/unread-count — আনরিড অ্যালার্ট কাউন্ট

30 সেকেন্ড ক্যাশ। ফ্রন্টএন্ড 30s পোলিং।

---

## মডিউল 9: নোটিফিকেশন

### GET /api/notifications — নোটিফিকেশন লিস্ট

**প্যারামিটার**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/notifications/unread-count — আনরিড নোটিফিকেশন কাউন্ট

### POST /api/notifications/:id/read — রিড মার্ক

### POST /api/notifications/read-all — সব রিড

---

## মডিউল 10: অটো বিডিং

### GET /api/bid-rules — রুল লিস্ট

### POST /api/bid-rules — রুল তৈরি

**রিকোয়েস্ট**:
```json
{
  "name": "ROI 达标加预算",
  "metric": "roi",
  "condition": "gte",
  "threshold": 3.0,
  "action_type": "adjust_budget",
  "adjust_step": 5000,
  "budget_min": 0,
  "budget_max": 100000,
  "cooldown_minutes": 60
}
```

**ফিল্ড ব্যাখ্যা**:

| ফিল্ড | টাইপ | বিবরণ |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | মনিটরিং মেট্রিক |
| condition | gt/gte/lt/lte | ট্রিগার কন্ডিশন |
| threshold | decimal | থ্রেশহোল্ড |
| action_type | adjust_budget/toggle_pause/toggle_enable | অ্যাকশন টাইপ |
| adjust_step | int (ফেন) | বাজেট অ্যাডজাস্টমেন্ট স্টেপ（পজিটিভ=বাড়ে, নেগেটিভ=কমে） |
| budget_min | int | বাজেট নিম্নসীমা（ফেন） |
| budget_max | int | বাজেট ঊর্ধ্বসীমা（ফেন） |
| cooldown_minutes | int | কুলডাউন সময়（ডিফল্ট 60） |

### PUT /api/bid-rules/:id — রুল আপডেট

### DELETE /api/bid-rules/:id — রুল ডিলিট

### GET /api/bid-rules/logs — বিডিং হিস্টোরি

**প্যারামিটার**: `rule_id`, `campaign_id`

---

## মডিউল 11: টার্গেটিং টেমপ্লেট

### GET /api/targeting-templates — টেমপ্লেট লিস্ট

**প্যারামিটার**: `platform`

### GET /api/targeting-templates/:id — টেমপ্লেট ডিটেইল

### POST /api/targeting-templates — টেমপ্লেট তৈরি

**রিকোয়েস্ট**:
```json
{
  "name": "核心受众",
  "platform": "",
  "targeting": {
    "age": { "min": 18, "max": 45 },
    "gender": "all",
    "interests": ["sports", "tech"],
    "devices": { "os": ["android", "ios"] }
  },
  "is_shared": 0
}
```

### PUT /api/targeting-templates/:id — টেমপ্লেট আপডেট

### DELETE /api/targeting-templates/:id — টেমপ্লেট ডিলিট

---

## মডিউল 12: অ্যাসেট লাইব্রেরি

### GET /api/assets — অ্যাসেট লিস্ট

**প্যারামিটার**: `type`(image/video), `page`, `per_page`

### POST /api/assets/upload — অ্যাসেট আপলোড

**রিকোয়েস্ট**: `multipart/form-data`, ফিল্ড `file`

- ছবি: সর্বোচ্চ 5 MB (jpeg/png/gif/webp)
- ভিডিও: সর্বোচ্চ 50 MB (mp4)

**রেসপন্স**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

- CDN কনফিগার থাকলে `url` ডিফল্ট প্রোভাইডারের `cdn_domain` দিয়ে জোড়া লাগিয়ে সম্পূর্ণ HTTPS ঠিকানা হয়

### POST /api/assets/presign — প্রিসাইন আপলোড URL নিন

**অনুরোধ**: `{ "filename": "demo.mp4", "mime_type": "video/mp4" }`

**রেসপন্স**: `{ "code": 0, "data": { "key": "20260829/ab12...cd34.mp4", "upload_url": "https://signed-url", "expires_in": 3600, "url": "https://cdn.example.com/uploads/assets/..." } }`

- `key` ফরম্যাট `Ymd/32hex.এক্সটেনশন`; ডাইরেক্ট আপলোডের পর `/api/assets/register`-এ ফেরত দিন
- 50 MiB পর্যন্ত ভিডিও ক্লায়েন্ট সরাসরি অবজেক্ট স্টোরেজে আপলোড করে; `local` driver-এ নেই

### POST /api/assets/register — সরাসরি আপলোড করা অ্যাসেট নিবন্ধন

**অনুরোধ**: `{ "key": "20260829/ab12...cd34.mp4", "filename": "demo.mp4", "mime_type": "video/mp4", "size": 52428800 }`

**রেসপন্স**: `{ "code": 0, "message": "Created", "data": { "id": "hashids", "url": "https://cdn.example.com/uploads/assets/...", "type": "video" } }`

- `key` কঠোরভাবে যাচাই (`^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$`) — পাথ ট্রাভার্সাল প্রতিরোধ

### GET /api/assets/:id — অ্যাসেট ডিটেইল

### DELETE /api/assets/:id — অ্যাসেট ডিলিট

---

## Admin এন্ডপয়েন্ট（পোর্ট 8789）

### POST /api/admin/login — অ্যাডমিন লগইন

**রিকোয়েস্ট**: `{ "username": "admin", "password": "..." }`

**রেসপন্স**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Token localStorage-এ সংরক্ষিত
- `csrf_token` পরের POST/PUT/DELETE রিকোয়েস্টের `X-CSRF-Token` header-এ পাঠাতে হবে

### GET /api/admin/me — বর্তমান অ্যাডমিন

### POST /api/admin/logout — লগআউট

### GET /api/admin/users — ইউজার লিস্ট

**প্যারামিটার**: `keyword`, `role_id`, `page`, `per_page`

রেসপন্সে `id` এবং `role_id` hashids এনকোড করা।

### POST /api/admin/users — ইউজার তৈরি

### PUT /api/admin/users/:id — ইউজার আপডেট

### DELETE /api/admin/users/:id — ইউজার ডিসেবল

### GET /api/admin/users/roles — রোল লিস্ট

### GET /api/admin/audit-logs — অডিট লগ

**প্যারামিটার**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

### CDN প্রোভাইডার ম্যানেজমেন্ট (শুধু প্ল্যাটফর্ম মাস্টার টেন্যান্ট tenant 1, AdminMiddleware)

### GET /api/admin/cdn/providers — প্রোভাইডার তালিকা

### POST /api/admin/cdn/providers — প্রোভাইডার তৈরি

**অনুরোধ**: `{ "name": "Aliyun OSS", "driver": "oss", "bucket": "ads-assets", "region": "oss-cn-hangzhou", "endpoint": "https://oss-cn-hangzhou.aliyuncs.com", "access_key": "...", "secret_key": "...", "cdn_domain": "cdn.example.com", "cdn_driver": "aliyun", "cdn_token": "...", "is_default": 1 }`

- `driver`: `local` / `oss` (Alibaba Cloud OSS) / `cos` (Tencent Cloud COS, S3 প্রোটোকল) / `s3` (S3-কমপ্যাটিবল: AWS S3 / Cloudflare R2 / MinIO)
- ক্রেডেনশিয়াল (access_key/secret_key/cdn_token) Encryptable দিয়ে ফিল্ড-লেভেল এনক্রিপ্টেড; রেসপন্সে শুধু মাস্ক করা ফিল্ড

### PUT /api/admin/cdn/providers/:id — প্রোভাইডার আপডেট

### DELETE /api/admin/cdn/providers/:id — প্রোভাইডার ডিলিট (ডিফল্ট পরের enabled প্রোভাইডারে অটো স্থানান্তর)

### PUT /api/admin/cdn/providers/:id/default — ডিফল্ট নির্ধারণ

### PUT /api/admin/cdn/providers/:id/toggle — চালু/বন্ধ (ডিফল্ট বন্ধ করলে অটো স্থানান্তর)

### POST /api/admin/cdn/providers/:id/test — কানেক্টিভিটি টেস্ট

**রেসপন্স**: `{ "code": 0, "data": { "ok": true, "driver": "oss", "status": "ok" } }`

### POST /api/admin/cdn/providers/:id/purge — ক্যাশ পার্জ

**অনুরোধ**: `{ "paths": ["/uploads/assets/20260829/xxx.mp4"] }`

- `cdn_driver` ও `cdn_domain` প্রয়োজন; `aliyun` বাস্তব ইমপ্লিমেন্টেশন (OpenAPI সিগনিং), cloudflare/cloudfront পরে

---

## এরর কোড রেফারেন্স

| code | HTTP | বিবরণ |
|------|------|------|
| 0 | 200 | সফল |
| 1 | 200/400 | সাধারণ ব্যবসায়িক এরর |
| 401 | 401 | আনঅথেনটিকেটেড / Token এক্সপায়ারড / IP/UA অমিল |
| 403 | 403 | অ্যাক্সেস নিষিদ্ধ（সিকিউরিটি ইন্টারসেপ্ট） |
| 404 | 404 | রিসোর্স নেই |
| 422 | 422 | প্যারামিটার ভ্যালিডেশন ব্যর্থ |
| 429 | 429 | অতিরিক্ত রিকোয়েস্ট / লগইন থ্রটলিং / কনকারেন্ট লিমিট |
| 1001 | 200 | অথেনটিকেশন ব্যর্থ（ইউজারনেম বা পাসওয়ার্ড ভুল） |

---

## সিকিউরিটি ইন্টারসেপ্ট রেসপন্স

রিকোয়েস্ট সিকিউরিটি মিডলওয়্যারে ইন্টারসেপ্ট হলে 403 রিটার্ন হয়:

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## রেট লিমিট রেসপন্স

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

`Retry-After` header-এ অবশিষ্ট অপেক্ষার সেকেন্ড থাকে।
