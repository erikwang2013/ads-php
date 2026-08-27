# वर्शन तुलना

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| वर्शन | लाइसेंस | प्राप्ति विधि |
|------|------|----------|
| **सरलीकृत (Lite)** | ओपन-सोर्स (MIT) | GitHub सार्वजनिक रिपॉज़िटरी |
| **स्टैंडर्ड (Standard)** | वाणिज्यिक लाइसेंस | erik@erik.xyz से संपर्क करें |
| **पूर्ण (Full)** | वाणिज्यिक लाइसेंस | erik@erik.xyz से संपर्क करें |

---

## फ़ंक्शन तुलना

### बेसिक फ़ंक्शन

| फ़ंक्शन | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| प्रमाणीकरण (लॉगिन/Token रिफ़्रेश/वर्तमान उपयोगकर्ता) | ✅ | ✅ | ✅ |
| प्लेटफ़ॉर्म प्रबंधन (29 प्लेटफ़ॉर्म सूची + OAuth) | ✅ | ✅ | ✅ |
| खाता प्रबंधन (CRUD + सिंक) | ✅ | ✅ | ✅ |
| विज्ञापन अभियान (CRUD + स्टार्ट/स्टॉप + बैच) | ✅ | ✅ | ✅ |
| रिपोर्ट (डैशबोर्ड + कस्टम + CSV/Excel/PDF एक्सपोर्ट) | ✅ | ✅ | ✅ |
| हेल्थ चेक + API दस्तावेज़ + कैप्चा | ✅ | ✅ | ✅ |
| डेटा सिंक (Campaign + Report) | ✅ | ✅ | ✅ |

### डिलीवरी प्रबंधन

| फ़ंक्शन | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| विज्ञापन समूह (CRUD + स्टार्ट/स्टॉप) | — | ✅ | ✅ |
| विज्ञापन क्रिएटिव (सूची + विवरण) | — | ✅ | ✅ |
| विज्ञापन समूह/क्रिएटिव डेटा सिंक | — | ✅ | ✅ |

### मॉनिटरिंग और नोटिफिकेशन

| फ़ंक्शन | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| अलर्ट नियम इंजन (7 मेट्रिक/4 शर्त/3 दायरा) | — | ✅ | ✅ |
| अलर्ट रिकॉर्ड + पुष्टि + अपठित संख्या | — | ✅ | ✅ |
| नोटिफिकेशन सेंटर (सूची/पढ़ा/सभी पढ़े) | — | ✅ | ✅ |

### उन्नत फ़ंक्शन

| फ़ंक्शन | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| स्वचालित बिडिंग नियम इंजन (3 क्रियाएँ/कूलडाउन) | — | — | ✅ |
| ऑडियंस टार्गेटिंग टेम्पलेट (सामान्य JSON Schema) | — | — | ✅ |
| विज्ञापन एसेट लाइब्रेरी (अपलोड/गैलरी/पूर्वावलोकन) | — | — | ✅ |
| बजट अलर्ट (तीन-स्तरीय 50/80/100%) | — | — | ✅ |
| डिलीवरी कैलेंडर (Gantt विज़ुअलाइज़ेशन) | — | — | ✅ |
| क्रॉस-प्लेटफ़ॉर्म एट्रिब्यूशन (5 मॉडल/30 दिन रिट्रोस्पेक्ट) | — | — | ✅ |

---

## सुरक्षा तुलना

| सुरक्षा आइटम | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| CORS व्हाइटलिस्ट | ✅ | ✅ | ✅ |
| सुरक्षा प्रतिक्रिया हेडर (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| वर्शन रूटिंग (X-API-Version) | ✅ | ✅ | ✅ |
| API रेट-लिमिट (स्लाइडिंग विंडो) | ✅ | ✅ | ✅ |
| SQL इंजेक्शन डिटेक्शन (पैटर्न मैचिंग) | ✅ | ✅ | ✅ |
| इनपुट फ़िल्टरिंग (strip_tags + trim) | ✅ | ✅ | ✅ |
| ट्रांसमिशन एन्क्रिप्शन/डिक्रिप्शन (X-Encrypted) | ✅ | ✅ | ✅ |
| JWT Bearer प्रमाणीकरण | ✅ | ✅ | ✅ |
| XSS अटैक डिटेक्शन (11 पैटर्न) | — | ✅ | ✅ |
| पाथ ट्रैवर्सल डिटेक्शन (7 पैटर्न) | — | ✅ | ✅ |
| Header इंजेक्शन डिटेक्शन | — | ✅ | ✅ |
| Body आकार सीमा (10 MiB) | — | ✅ | ✅ |
| Content-Type व्हाइटलिस्ट | — | ✅ | ✅ |
| क्लाइंट स्रोत पहचान (8 एंड) | — | ✅ | ✅ |
| लॉगिन थ्रॉटलिंग (5 बार→15 मिनट) | — | ✅ | ✅ |
| प्रतिक्रिया समय मॉनिटरिंग (X-Response-Time) | — | ✅ | ✅ |
| Origin/Referer सत्यापन | — | — | ✅ |
| रिप्ले-अटैक सुरक्षा (Nonce+Timestamp) | — | — | ✅ |
| समवर्ती सत्र सीमा (अधिकतम 3) | — | — | ✅ |
| CSRF Token (Admin एंड) | — | — | ✅ |
| SSRF सुरक्षा (OAuth व्हाइटलिस्ट) | — | — | ✅ |
| लॉग डेटा डी-सेंसिटाइज़ेशन | — | — | ✅ |
| JWT IP/UA बाइंडिंग | — | — | ✅ |

---

## मिडलवेयर चेन तुलना

### Service एंड

| Lite (7 परतें) | Standard (11 परतें) | Full (15 परतें) |
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

### Admin एंड

| Lite (1 परत) | Standard (4 परतें) | Full (5 परतें) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## निर्धारित कार्य तुलना

| कार्य | आवृत्ति | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55min | ✅ | ✅ | ✅ |
| DataSyncTask | 10min | ✅ (केवल Campaign+Report) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5min | — | ✅ | ✅ |
| BidCheckTask | 10min | — | — | ✅ |
| BudgetCheckTask | 15min | — | — | ✅ |

---

## डेटाबेस टेबल तुलना

| श्रेणी | टेबल नाम | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| बेसिक | ads_tenants | ✅ | ✅ | ✅ |
| खाता | ads_platform_accounts | ✅ | ✅ | ✅ |
| | ads_auth_tokens | ✅ | ✅ | ✅ |
| डिलीवरी | ads_campaigns | ✅ | ✅ | ✅ |
| | ads_report_metrics | ✅ | ✅ | ✅ |
| | ads_report_extras | ✅ | ✅ | ✅ |
| | ads_ad_groups | — | ✅ | ✅ |
| | ads_creatives | — | ✅ | ✅ |
| अलर्ट | ads_alert_rules | — | ✅ | ✅ |
| | ads_alert_logs | — | ✅ | ✅ |
| नोटिफिकेशन | ads_notifications | — | ✅ | ✅ |
| बिडिंग | ads_bid_rules | — | — | ✅ |
| | ads_bid_logs | — | — | ✅ |
| टार्गेटिंग | ads_targeting_templates | — | — | ✅ |
| एसेट | ads_assets | — | — | ✅ |
| एट्रिब्यूशन | ads_conversions | — | — | ✅ |
| | ads_attribution_results | — | — | ✅ |
| सिस्टम | ads_sync_errors | ✅ | ✅ | ✅ |
| प्रशासन | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **कुल** | | **8** | **13** | **18** |

---

## फ्रंटएंड पेज तुलना

### Vue Admin SPA

| पेज | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| लॉगिन | ✅ | ✅ | ✅ |
| डैशबोर्ड | ✅ | ✅ | ✅ |
| खाता सूची + बाइंडिंग | ✅ | ✅ | ✅ |
| विज्ञापन अभियान | ✅ | ✅ | ✅ |
| रिपोर्ट एक्सपोर्ट | ✅ | ✅ | ✅ |
| उपयोगकर्ता प्रबंधन | ✅ | ✅ | ✅ |
| ऑडिट लॉग | ✅ | ✅ | ✅ |
| विज्ञापन समूह | — | ✅ | ✅ |
| विज्ञापन क्रिएटिव | — | ✅ | ✅ |
| रिपोर्ट विश्लेषण (ECharts) | — | ✅ | ✅ |
| अलर्ट नियम | — | ✅ | ✅ |
| अलर्ट रिकॉर्ड | — | ✅ | ✅ |
| नोटिफिकेशन सेंटर | — | ✅ | ✅ |
| स्वचालित बिडिंग | — | — | ✅ |
| एसेट लाइब्रेरी | — | — | ✅ |
| डिलीवरी कैलेंडर | — | — | ✅ |
| एट्रिब्यूशन विश्लेषण | — | — | ✅ |
| **कुल** | **7** | **13** | **17** |

### Flutter

| पेज | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| लॉगिन | ✅ | ✅ | ✅ |
| डैशबोर्ड | ✅ | ✅ | ✅ |
| विज्ञापन अभियान (सूची+विवरण) | ✅ | ✅ | ✅ |
| डेटा रिपोर्ट | ✅ | ✅ | ✅ |
| प्लेटफ़ॉर्म खाते | ✅ | ✅ | ✅ |
| अलर्ट प्रबंधन | ✅ | ✅ | ✅ |
| विज्ञापन समूह | — | ✅ | ✅ |
| विज्ञापन क्रिएटिव | — | ✅ | ✅ |
| रिपोर्ट विश्लेषण | — | ✅ | ✅ |
| नोटिफिकेशन सेंटर | — | ✅ | ✅ |
| स्वचालित बिडिंग | — | — | ✅ |
| **कुल** | **6** | **10** | **11** |

---

## API एंडपॉइंट तुलना

| मॉड्यूल | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| सिस्टम (health/ping/docs/captcha) | 6 | 6 | 6 |
| प्रमाणीकरण (login/me/refresh) | 3 | 3 | 3 |
| प्लेटफ़ॉर्म (list/oauthUrl/callback) | 3 | 3 | 3 |
| खाता (index/show/destroy/sync) | 4 | 4 | 4 |
| विज्ञापन अभियान (CRUD/toggle/batch) | 6 | 6 | 6 |
| विज्ञापन समूह (CRUD/toggle) | — | 5 | 5 |
| क्रिएटिव (index/show) | — | 2 | 2 |
| रिपोर्ट (summary/custom/export×2) | 4 | 4 | 4 |
| रिपोर्ट (calendar/budget/attribution/models) | — | — | 4 |
| अलर्ट (rules CRUD + logs + acknowledge + unread) | — | 7 | 7 |
| नोटिफिकेशन (index/unread/read/readAll) | — | 4 | 4 |
| स्वचालित बिडिंग (CRUD + logs) | — | — | 5 |
| टार्गेटिंग टेम्पलेट (CRUD) | — | — | 5 |
| एसेट लाइब्रेरी (index/upload/show/destroy) | — | — | 4 |
| **कुल** | **26** | **44** | **62** |

---

## तकनीकी स्टैक

तीनों वर्शन एक ही तकनीकी स्टैक साझा करते हैं:

| परत | तकनीक |
|----|------|
| बैकएंड फ्रेमवर्क | webman v2, PHP 8.2+ |
| डेटाबेस | MySQL 8.0 (InnoDB, utf8mb4) |
| कैश | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| प्रमाणीकरण | erikwang2013/jwt-webman |
| ID जनरेशन | erikwang2013/snowflake-php |
| ID एन्कोडिंग | erikwang2013/hashids |
| फ्रंटएंड | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| डिप्लॉयमेंट | Docker + Nginx + Docker Compose |

---

## अपग्रेड पथ

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
