# API इंटरफ़ेस दस्तावेज़

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **hg/apidoc ऑनलाइन दस्तावेज़**: सेवा शुरू करने के बाद `http://127.0.0.1:8788/apidoc` पर जाएँ（Service + Admin दोनों ऐप स्विच करें）  
> कॉन्फ़िगरेशन फ़ाइल: `service/config/plugin/hg/apidoc/app.php`

---

## सामान्य विनिर्देश

### Base URL

```
http://your-domain.com/api
```

### आवश्यक Headers

| Header | मान | विवरण |
|--------|----|------|
| `X-API-Version` | `v1` | API वर्शन नंबर（आवश्यक, URL पाथ में नहीं आता） |
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | ऑपरेशन स्रोत एंड（आवश्यक） |
| `Authorization` | `Bearer <token>` | JWT प्रमाणीकरण टोकन（लॉगिन/प्लेटफ़ॉर्म सूची/हेल्थ चेक को छोड़कर आवश्यक） |

### रिप्ले-सुरक्षा Header（गैर-ब्राउज़र एंड）

| Header | विवरण |
|--------|------|
| `X-Nonce` | रैंडम स्ट्रिंग（प्रत्येक अनुरोध पर अद्वितीय） |
| `X-Timestamp` | Unix सेकंड टाइमस्टैम्प（±5 मिनट विंडो） |

### वैकल्पिक Headers

| Header | विवरण |
|--------|------|
| `X-Tenant-Id` | टेनेंट ID（मल्टी-टेनेंट मोड） |
| `X-Encrypted` | `1` = अनुरोध बॉडी डिक्रिप्ट करनी है, प्रतिक्रिया एन्क्रिप्ट करनी है |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| मान | विवरण |
|----|------|
| `application/json` | JSON अनुरोध बॉडी（अनुशंसित） |
| `application/x-www-form-urlencoded` | फ़ॉर्म अनुरोध |
| `multipart/form-data` | फ़ाइल अपलोड |

### प्रतिक्रिया फ़ॉर्मेट

**सफलता**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**पेजिनेशन**:
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

**त्रुटि**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**हेल्थ चेक**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### HTTP स्टेटस कोड

| स्टेटस कोड | अर्थ |
|--------|------|
| 200 | सफलता |
| 204 | OPTIONS प्रीफ़्लाइट सफल |
| 400 | अनुरोध पैरामीटर त्रुटि, असमर्थित API वर्शन |
| 401 | अनप्रमाणित, Token समाप्त, Token IP/UA मेल नहीं खाता |
| 403 | एक्सेस निषिद्ध（XSS/पाथ ट्रैवर्सल/CSRF/SQL इंजेक्शन/Origin मेल नहीं खाता） |
| 404 | संसाधन मौजूद नहीं |
| 429 | बहुत अधिक अनुरोध（रेट-लिमिट/लॉगिन थ्रॉटलिंग/समवर्ती सत्र सीमा） |
| 500 | सर्वर त्रुटि |
| 503 | सेवा डिग्रेडेड（DB या Redis अनुपलब्ध） |

### पेजिनेशन पैरामीटर

| पैरामीटर | डिफ़ॉल्ट मान | अधिकतम मान | विवरण |
|------|--------|--------|------|
| `page` | 1 | — | पेज नंबर |
| `per_page` | 20 | 100 | प्रति पेज आइटम（अधिक होने पर स्वचालित ट्रंकेट） |
| `sort` | `id` | — | सॉर्ट फ़ील्ड（व्हाइटलिस्ट में होना चाहिए） |

### कैश रणनीति

| एंडपॉइंट | TTL | परत |
|------|-----|-----|
| `/api/platforms` | 1 घंटा | L1 मेमोरी → L2 APCu → L3 Redis |
| `/api/accounts` + `/api/accounts/:id` | 5 मिनट | वही |
| `/api/reports/summary` | 5 मिनट | वही |
| `/api/alerts/rules` | 2 मिनट | वही |
| `/api/alerts/unread-count` | 30 सेकंड | वही |

---

## मॉड्यूल 1: सिस्टम

### GET /health — हेल्थ चेक

```
GET /health
```

**प्रतिक्रिया**:
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

- `status`: `healthy` (200) या `degraded` (503)
- कोई प्रमाणीकरण आवश्यक नहीं, वर्शन रूटिंग से नहीं गुजरता

---

### GET /ping — जीवितता जाँच

```
GET /ping
```

**प्रतिक्रिया**: `{ "pong": true }`

---

### GET /docs — API दस्तावेज़

```
GET /docs
```

HTML प्रारूप में API दस्तावेज़ पेज लौटाता है（बिना प्रमाणीकरण）。

---

### GET /api/captcha/generate — कैप्चा जनरेट करें

बिना प्रमाणीकरण।

**प्रतिक्रिया**:
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

- token 5 मिनट वैध
- ऑफ़सेट सहनशीलता 5px

---

### POST /api/captcha/verify — कैप्चा सत्यापित करें

बिना प्रमाणीकरण।

**अनुरोध**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**प्रतिक्रिया**: `{ "code": 0, "message": "验证通过" }`

---

## मॉड्यूल 2: प्रमाणीकरण

### POST /api/auth/login — लॉगिन

बिना प्रमाणीकरण।

**अनुरोध**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**प्रतिक्रिया**:
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

- JWT Token 24 घंटे वैध
- Token में IP + User-Agent hash एम्बेडेड
- 5 बार विफलता → Redis 15 मिनट लॉक

---

### GET /api/auth/me — वर्तमान उपयोगकर्ता

**अनुरोध हेडर**: `Authorization: Bearer <token>`

**प्रतिक्रिया**:
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

### POST /api/auth/refresh — Token रिफ़्रेश करें

**अनुरोध हेडर**: `Authorization: Bearer <old_token>`

**प्रतिक्रिया**:
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

- पुराना Token स्वचालित रूप से ब्लैकलिस्ट में
- प्रत्येक उपयोगकर्ता अधिकतम 3 सक्रिय Token

---

## मॉड्यूल 3: प्लेटफ़ॉर्म और खाता

### GET /api/platforms — प्लेटफ़ॉर्म सूची

बिना प्रमाणीकरण। 1 घंटे कैश।

**प्रतिक्रिया**:
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

### GET /api/platforms/:code/oauth-url — OAuth प्राधिकरण URL

**पैरामीटर**: `?redirect_uri=https://your-domain.com/callback`

**प्रतिक्रिया**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` को SSRF व्हाइटलिस्ट सत्यापन से गुजरना होगा（`OAUTH_ALLOWED_REDIRECTS` एनवायरनमेंट वेरिएबल）

---

### POST /api/platforms/:code/callback — OAuth कॉलबैक

**अनुरोध**: `{ "state": "...", "code": "..." }`

**प्रतिक्रिया**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/accounts — खाता सूची

5 मिनट कैश।

**पैरामीटर**:

| पैरामीटर | विवरण |
|------|------|
| `platform` | प्लेटफ़ॉर्म कोड फ़िल्टर |
| `page` | पेज नंबर |
| `per_page` | प्रति पेज आइटम |

**प्रतिक्रिया**: पेजिनेशन फ़ॉर्मेट, list में प्रत्येक आइटम में `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at` शामिल

---

### GET /api/accounts/:id — खाता विवरण

5 मिनट कैश।

---

### DELETE /api/accounts/:id — खाता अनबाइंड करें

---

### POST /api/accounts/:id/sync — मैनुअल सिंक

---

## मॉड्यूल 4: विज्ञापन अभियान

### GET /api/campaigns — अभियान सूची

**पैरामीटर**:

| पैरामीटर | विवरण | वैकल्पिक मान |
|------|------|--------|
| `platform` | प्लेटफ़ॉर्म फ़िल्टर | juliang, meta, google... |
| `status` | स्थिति फ़िल्टर | enabled, paused |
| `keyword` | नाम खोज | कोई भी टेक्स्ट |
| `sort` | सॉर्ट फ़ील्ड | id, name, platform, daily_budget, status, created_at |
| `page` | पेज नंबर | — |
| `per_page` | प्रति पेज आइटम | ≤100 |

**प्रतिक्रिया**: पेजिनेशन फ़ॉर्मेट + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/campaigns — अभियान बनाएँ

**अनुरोध**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**प्रतिक्रिया**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- `daily_budget` इकाई: 分（20000 = ¥200.00）

---

### GET /api/campaigns/:id — अभियान विवरण

**प्रतिक्रिया**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/campaigns/:id — अभियान अपडेट करें

**अनुरोध**: `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/campaigns/:id/toggle — अभियान स्टार्ट/स्टॉप

**अनुरोध**: `{ "enabled": false }`

---

### POST /api/campaigns/batch/toggle — बैच स्टार्ट/स्टॉप

**अनुरोध**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**प्रतिक्रिया**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## मॉड्यूल 5: विज्ञापन समूह

### GET /api/ad-groups — विज्ञापन समूह सूची

**पैरामीटर**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/ad-groups — विज्ञापन समूह बनाएँ

**अनुरोध**:
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

- `targeting_template_id`: वैकल्पिक, टार्गेटिंग टेम्पलेट से targeting JSON लोड करके मर्ज करता है

### GET /api/ad-groups/:id — विज्ञापन समूह विवरण

### PUT /api/ad-groups/:id — विज्ञापन समूह अपडेट करें

### POST /api/ad-groups/:id/toggle — विज्ञापन समूह स्टार्ट/स्टॉप

---

## मॉड्यूल 6: क्रिएटिव

### GET /api/creatives — क्रिएटिव सूची

**पैरामीटर**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/creatives/:id — क्रिएटिव विवरण

---

## मॉड्यूल 7: रिपोर्ट

### GET /api/reports/summary — डैशबोर्ड सारांश

5 मिनट कैश।

**पैरामीटर**: `date_start`, `date_end`

**प्रतिक्रिया**:
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

### GET /api/reports/custom — कस्टम रिपोर्ट

**पैरामीटर**:

| पैरामीटर | विवरण |
|------|------|
| `dimensions[]` | आयाम: date, platform, campaign |
| `metrics[]` | मेट्रिक्स: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | आरंभ तिथि |
| `date_end` | समाप्ति तिथि |
| `platform` | प्लेटफ़ॉर्म फ़िल्टर |

---

### GET /api/reports/export — रिपोर्ट एक्सपोर्ट करें

**पैरामीटर**: `format=csv`, `date_start`, `date_end`, `metrics[]`

फ़ाइल डाउनलोड लौटाता है（CSV UTF-8 BOM या Excel .xls）。

---

### GET /api/reports/export-dashboard — डैशबोर्ड PDF एक्सपोर्ट

---

### GET /api/reports/calendar — डिलीवरी कैलेंडर

**पैरामीटर**: `date_start`, `date_end`, `platform`

**प्रतिक्रिया**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/reports/budget-alerts — बजट अलर्ट

**प्रतिक्रिया**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/reports/attribution — एट्रिब्यूशन विश्लेषण

**पैरामीटर**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**प्रतिक्रिया**:
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

### GET /api/reports/attribution/models — एट्रिब्यूशन मॉडल सूची

**प्रतिक्रिया**: `[{ code: "last_touch", name: "末次触点", description: "..." }]`

कुल 5 मॉडल।

---

## मॉड्यूल 8: अलर्ट

### GET /api/alerts/rules — अलर्ट नियम सूची

2 मिनट कैश।

**पैरामीटर**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/alerts/rules — अलर्ट नियम बनाएँ

**अनुरोध**:
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

### PUT /api/alerts/rules/:id — अलर्ट नियम अपडेट करें

### DELETE /api/alerts/rules/:id — अलर्ट नियम हटाएँ

### GET /api/alerts/logs — अलर्ट रिकॉर्ड

**पैरामीटर**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/alerts/logs/:id/acknowledge — अलर्ट पुष्टि करें

### GET /api/alerts/unread-count — अपठित अलर्ट संख्या

30 सेकंड कैश। फ्रंटएंड 30s पोलिंग।

---

## मॉड्यूल 9: नोटिफिकेशन

### GET /api/notifications — नोटिफिकेशन सूची

**पैरामीटर**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/notifications/unread-count — अपठित नोटिफिकेशन संख्या

### POST /api/notifications/:id/read — पढ़ा-चिह्नित करें

### POST /api/notifications/read-all — सभी पढ़े

---

## मॉड्यूल 10: स्वचालित बिडिंग

### GET /api/bid-rules — नियम सूची

### POST /api/bid-rules — नियम बनाएँ

**अनुरोध**:
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

**फ़ील्ड विवरण**:

| फ़ील्ड | प्रकार | विवरण |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | मॉनिटरिंग मेट्रिक |
| condition | gt/gte/lt/lte | ट्रिगर शर्त |
| threshold | decimal | थ्रेशोल्ड |
| action_type | adjust_budget/toggle_pause/toggle_enable | क्रिया प्रकार |
| adjust_step | int (分) | बजट समायोजन चरण（धनात्मक=बढ़ाएँ, ऋणात्मक=घटाएँ） |
| budget_min | int | बजट निचली सीमा（分） |
| budget_max | int | बजट ऊपरी सीमा（分） |
| cooldown_minutes | int | कूलडाउन समय（डिफ़ॉल्ट 60） |

### PUT /api/bid-rules/:id — नियम अपडेट करें

### DELETE /api/bid-rules/:id — नियम हटाएँ

### GET /api/bid-rules/logs — बिडिंग इतिहास

**पैरामीटर**: `rule_id`, `campaign_id`

---

## मॉड्यूल 11: टार्गेटिंग टेम्पलेट

### GET /api/targeting-templates — टेम्पलेट सूची

**पैरामीटर**: `platform`

### GET /api/targeting-templates/:id — टेम्पलेट विवरण

### POST /api/targeting-templates — टेम्पलेट बनाएँ

**अनुरोध**:
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

### PUT /api/targeting-templates/:id — टेम्पलेट अपडेट करें

### DELETE /api/targeting-templates/:id — टेम्पलेट हटाएँ

---

## मॉड्यूल 12: एसेट लाइब्रेरी

### GET /api/assets — एसेट सूची

**पैरामीटर**: `type`(image/video), `page`, `per_page`

### POST /api/assets/upload — एसेट अपलोड करें

**अनुरोध**: `multipart/form-data`, फ़ील्ड `file`

- छवि: अधिकतम 5 MB (jpeg/png/gif/webp)
- वीडियो: अधिकतम 50 MB (mp4)

**प्रतिक्रिया**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

- CDN कॉन्फ़िगर होने पर `url` डिफ़ॉल्ट प्रोवाइडर के `cdn_domain` से जुड़कर पूरा HTTPS पता बनता है

### POST /api/assets/presign — प्रीसाइन अपलोड URL पाएं

**अनुरोध**: `{ "filename": "demo.mp4", "mime_type": "video/mp4" }`

**प्रतिक्रिया**: `{ "code": 0, "data": { "key": "20260829/ab12...cd34.mp4", "upload_url": "https://signed-url", "expires_in": 3600, "url": "https://cdn.example.com/uploads/assets/..." } }`

- `key` फ़ॉर्मेट `Ymd/32hex.एक्सटेंशन`; डायरेक्ट अपलोड के बाद `/api/assets/register` में लौटाएं
- 50 MiB तक के वीडियो क्लाइंट सीधे ऑब्जेक्ट स्टोरेज में अपलोड करता है; `local` driver में उपलब्ध नहीं

### POST /api/assets/register — सीधे अपलोड किए एसेट का पंजीकरण

**अनुरोध**: `{ "key": "20260829/ab12...cd34.mp4", "filename": "demo.mp4", "mime_type": "video/mp4", "size": 52428800 }`

**प्रतिक्रिया**: `{ "code": 0, "message": "Created", "data": { "id": "hashids", "url": "https://cdn.example.com/uploads/assets/...", "type": "video" } }`

- `key` सख्ती से वैलिडेट (`^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$`) — पाथ ट्रैवर्सल रोकथाम

### GET /api/assets/:id — एसेट विवरण

### DELETE /api/assets/:id — एसेट हटाएँ

---

## Admin एंडपॉइंट（पोर्ट 8789）

### POST /api/admin/login — एडमिन लॉगिन

**अनुरोध**: `{ "username": "admin", "password": "..." }`

**प्रतिक्रिया**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Token localStorage में स्टोर होता है
- `csrf_token` बाद के POST/PUT/DELETE अनुरोधों के `X-CSRF-Token` header में भेजना होगा

### GET /api/admin/me — वर्तमान एडमिन

### POST /api/admin/logout — लॉगआउट

### GET /api/admin/users — उपयोगकर्ता सूची

**पैरामीटर**: `keyword`, `role_id`, `page`, `per_page`

प्रतिक्रिया में `id` और `role_id` hashids से एन्कोडेड हैं।

### POST /api/admin/users — उपयोगकर्ता बनाएँ

### PUT /api/admin/users/:id — उपयोगकर्ता अपडेट करें

### DELETE /api/admin/users/:id — उपयोगकर्ता निष्क्रिय करें

### GET /api/admin/users/roles — भूमिका सूची

### GET /api/admin/audit-logs — ऑडिट लॉग

**पैरामीटर**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

### CDN प्रोवाइडर प्रबंधन (केवल प्लेटफ़ॉर्म मास्टर टेनेंट tenant 1, AdminMiddleware)

### GET /api/admin/cdn/providers — प्रोवाइडर सूची

### POST /api/admin/cdn/providers — प्रोवाइडर बनाएं

**अनुरोध**: `{ "name": "Aliyun OSS", "driver": "oss", "bucket": "ads-assets", "region": "oss-cn-hangzhou", "endpoint": "https://oss-cn-hangzhou.aliyuncs.com", "access_key": "...", "secret_key": "...", "cdn_domain": "cdn.example.com", "cdn_driver": "aliyun", "cdn_token": "...", "is_default": 1 }`

- `driver`: `local` / `oss` (Alibaba Cloud OSS) / `cos` (Tencent Cloud COS, S3 प्रोटोकॉल) / `s3` (S3-कंपैटिबल: AWS S3 / Cloudflare R2 / MinIO)
- क्रेडेंशियल (access_key/secret_key/cdn_token) Encryptable से फ़ील्ड-स्तर एन्क्रिप्टेड; रिस्पॉन्स में सिर्फ़ मास्क किए फ़ील्ड

### PUT /api/admin/cdn/providers/:id — प्रोवाइडर अपडेट करें

### DELETE /api/admin/cdn/providers/:id — प्रोवाइडर हटाएं (डिफ़ॉल्ट अगले enabled प्रोवाइडर को ऑटो ट्रांसफर)

### PUT /api/admin/cdn/providers/:id/default — डिफ़ॉल्ट बनाएं

### PUT /api/admin/cdn/providers/:id/toggle — चालू/बंद (डिफ़ॉल्ट बंद करने पर ऑटो ट्रांसफर)

### POST /api/admin/cdn/providers/:id/test — कनेक्टिविटी टेस्ट

**प्रतिक्रिया**: `{ "code": 0, "data": { "ok": true, "driver": "oss", "status": "ok" } }`

### POST /api/admin/cdn/providers/:id/purge — कैश पर्ज

**अनुरोध**: `{ "paths": ["/uploads/assets/20260829/xxx.mp4"] }`

- `cdn_driver` और `cdn_domain` चाहिए; `aliyun` वास्तविक इम्प्लीमेंटेशन (OpenAPI सिग्निंग), cloudflare/cloudfront बाकी

---

## त्रुटि कोड संदर्भ

| code | HTTP | विवरण |
|------|------|------|
| 0 | 200 | सफलता |
| 1 | 200/400 | सामान्य बिज़नेस त्रुटि |
| 401 | 401 | अनप्रमाणित / Token समाप्त / IP/UA मेल नहीं खाता |
| 403 | 403 | एक्सेस निषिद्ध（सुरक्षा इंटरसेप्शन） |
| 404 | 404 | संसाधन मौजूद नहीं |
| 422 | 422 | पैरामीटर सत्यापन विफल |
| 429 | 429 | बहुत अधिक अनुरोध / लॉगिन थ्रॉटलिंग / समवर्ती सीमा |
| 1001 | 200 | प्रमाणीकरण विफल（उपयोगकर्ता नाम या पासवर्ड गलत） |

---

## सुरक्षा इंटरसेप्शन प्रतिक्रिया

जब अनुरोध सुरक्षा मिडलवेयर द्वारा इंटरसेप्ट होता है, 403 लौटता है:

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## रेट-लिमिट प्रतिक्रिया

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

`Retry-After` header में शेष प्रतीक्षा सेकंड होते हैं।
