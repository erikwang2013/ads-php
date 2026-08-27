# Phase 10: गहरीकरण और व्यावसायीकरण Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**लक्ष्य:** Phase 7-9 के कॉन्ट्रैक्ट और मल्टी-चैनल आधार पर, सिंक स्थिति विज़ुअलाइज़ेशन, रूपांतरण डेटा लूप, मोबाइल CI पैकेजिंग, मल्टी-टेनेंट SaaS कोटा — चार गहरीकरण क्षमताएँ लागू करें।

**स्रोत:** Phase 7 टीम ऑडिट से अनुमानित दिशाएँ (researcher: ES/रीड-राइट स्प्लिटिंग/क्यू लागू करना, Flutter/हार्मनी CI, 29 प्लेटफ़ॉर्म वास्तविक इंटीग्रेशन, SaaS बिलिंग कोटा, रूपांतरण डेटा लूप, सिंक स्थिति विज़ुअलाइज़ेशन, AI बिडिंग)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## वर्तमान स्थिति (सत्यापित)

| उम्मीदवार उप-आइटम | वर्तमान स्थिति |
|---|---|
| सिंक स्थिति विज़ुअलाइज़ेशन | `ads_sync_errors` टेबल + `RetrySyncTask` (3 बार रीट्राय, 5^n मिनट बैकऑफ़) मौजूद; **सिंक विफलता दर और विलंब दिखाने वाला कोई फ़्रंटएंड पेज/API नहीं** |
| रूपांतरण डेटा लूप | `ads_conversions` + `ads_attribution_results` टेबल मौजूद, एट्रिब्यूशन इंजन लागू; **कोई रूपांतरण डेटा संग्रह प्रवेश बिंदु नहीं** (रिटर्न/ट्रैकिंग API) |
| मोबाइल CI | `ci.yml` में केवल PHP सिंटैक्स→PHPUnit→vue-tsc→Docker; **कोई Flutter/HarmonyOS बिल्ड पैकेजिंग नहीं** |
| मल्टी-टेनेंट SaaS | `ads_tenants` टेबल + TenantIdentify मिडलवेयर मौजूद; **कोई बिलिंग/कोटा/उपयोग आँकड़े नहीं** |
| ES लागू करना | scout.php कॉन्फ़िगर + webman-scout निर्भरता जोड़ी गई; **docker-compose में कोई ES सेवा नहीं** |
| 29 प्लेटफ़ॉर्म वास्तविक इंटीग्रेशन | 29 एडाप्टर कोड पूर्ण; **कोई सैंडबॉक्स/क्रेडेंशियल इंटीग्रेशन रिकॉर्ड नहीं** (बाहरी क्रेडेंशियल आवश्यक, मैनुअल आइटम के रूप में चिह्नित) |

## Task 1: सिंक स्थिति विज़ुअलाइज़ेशन

### Files:
- Modify: `service/plugin/ads-api/controller/v1/DashboardController.php` या नया `service/plugin/ads-api/controller/v1/SyncController.php` + route
- Create: `admin/public/web/src/api/sync.ts`
- Create: `admin/public/web/src/views/sync/SyncStatus.vue` (या सिस्टम पेज में शामिल करें)

### डिज़ाइन मुख्य बिंदु
- एंडपॉइंट: `GET /api/sync/status` (खाता आयाम: last_sync_at, सफलता दर, आज की विफलताएँ, pending रीट्राय संख्या) + `GET /api/sync/errors` (पेजिनेटेड त्रुटि सूची, last_error/retry_count/next_retry_at सहित)
- फ़्रंटएंड: सिंक स्थिति पेज (टेबल + सारांश कार्ड), केवल Full/Standard वर्शन लाइन
- डेटा स्रोत: ads_platform_accounts (last_sync_at) + ads_sync_errors

## Task 2: रूपांतरण डेटा संग्रह API

### Files:
- Modify: `service/plugin/ads-api/controller/v1/` (नया ConversionController + route)
- Create: `service/plugin/ads-report/service/ConversionService.php`

### डिज़ाइन मुख्य बिंदु
- एंडपॉइंट: `POST /api/conversions` (बिज़नेस पक्ष रूपांतरण रिटर्न: platform/campaign_id/order_id/conversion_time/value/currency/channel) + `GET /api/conversions` (क्वेरी)
- सत्यापन: campaign_id मौजूद हो, राशि गैर-ऋणात्मक, समय प्रारूप; ads_conversions में लिखें
- एट्रिब्यूशन लिंकेज: रिटर्न के बाद एट्रिब्यूशन पुनर्गणना ट्रिगर हो सकती है (या बताएँ कि मौजूदा AttributionEngine नियमित/मैनुअल पुनर्गणना करता है)
- फ़्रंटएंड: एट्रिब्यूशन रिपोर्ट पेज में "रूपांतरण रिटर्न" स्पष्टीकरण/डेमो जोड़ें (वैकल्पिक)

## Task 3: मोबाइल CI पैकेजिंग

### Files:
- Modify: `.github/workflows/ci.yml` (नया job: Flutter build (web + linux या apk) + HarmonyOS स्टैटिक चेक)

### डिज़ाइन मुख्य बिंदु
- Flutter: `flutter pub get && flutter analyze && flutter build web` (या apk, रिपो की स्थिति के अनुसार बनाने योग्य लक्ष्य चुनें; अगर flutter पर्यावरण सीमित हो तो dart analyze)
- HarmonyOS: कोई मानक Linux CI टूलचेन नहीं, स्टैटिक चेक स्पष्टीकरण या छोड़ें (चिह्नित करें)
- मौजूदा php-tests job के समानांतर, मुख्य फ़्लो को ब्लॉक न करें

## Task 4: मल्टी-टेनेंट SaaS कोटा (MVP)

### Files:
- Modify: `service/plugin/ads-tenant/` (नया QuotaService)
- Modify: `service/plugin/ads-api/config/route.php` + controller

### डिज़ाइन मुख्य बिंदु
- डेटा: ads_tenants में quota फ़ील्ड जोड़ें या नई टेबल ads_tenant_quotas (plan/account_limit/campaign_limit/sync_quota)
- सत्यापन बिंदु: खाता बाइंडिंग संख्या, प्लान निर्माण संख्या, दैनिक सिंक संख्या (AccountController/CampaignController/DataSyncTask प्रवेश पर जाँच)
- एंडपॉइंट: `GET /api/tenant/quota` (उपयोग + कोटा)
- फ़्रंटएंड: सिस्टम पेज में कोटा उपयोग दिखाएँ (वैकल्पिक, MVP में केवल API हो सकता है)
- वर्शन लाइन: quota डिफ़ॉल्ट मान lite/standard/full के अनुसार भिन्न (config कॉन्स्टेंट)

## स्वीकृति (Task अनुसार)
- [ ] Task 1: sync API एंडपॉइंट उपलब्ध, फ़्रंटएंड पेज दिखाता है, टेस्ट कवरेज
- [ ] Task 2: conversions रिटर्न API लिखा-पढ़ा जा सकता है, सत्यापन प्रभावी, टेस्ट कवरेज
- [ ] Task 3: CI में नया job पास (या स्पष्ट रूप से स्किप आइटम चिह्नित)
- [ ] Task 4: quota API सही रिटर्न, सीमा से अधिक रोकथाम प्रभावी, टेस्ट कवरेज
- [ ] सभी: `php vendor/bin/phpunit --no-coverage` सभी पास, vue-tsc पास

## इस चरण के दायरे में नहीं (बाहरी संसाधन आवश्यक)
- 29 प्लेटफ़ॉर्म वास्तविक इंटीग्रेशन (हर प्लेटफ़ॉर्म के क्रेडेंशियल/सैंडबॉक्स आवश्यक)
- ES सेवा लागू करना (docker-compose में ES सेवा और इंडेक्स इनिशियलाइज़ेशन जोड़ना आवश्यक)
- AI बिडिंग सुझाव (मॉडल/डेटा तैयारी)
