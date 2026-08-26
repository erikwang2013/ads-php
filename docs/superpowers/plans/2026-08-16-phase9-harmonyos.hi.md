# Phase 9: HarmonyOS वास्तविक इंटीग्रेशन Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**लक्ष्य:** HarmonyOS एंड के 6 पेजों को सिम्युलेटेड डेटा से वास्तविक API कॉल (service :8788) पर स्विच करें, ApiClient के baseUrl हार्डकोडिंग की समस्या ठीक करें, लॉगिन को वास्तविक बनाएँ, ताकि हार्मनी एंड उपयोगी तीसरा क्लाइंट बने।

**स्रोत:** Phase 7 टीम ऑडिट (mobile-dev इन्वेंट्री: HarmonyOS 6 पेज सभी सिम्युलेटेड डेटा, 0 वास्तविक कॉल, ApiClient baseUrl हार्डकोडेड `http://127.0.0.1:8788/api`)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## वर्तमान स्थिति (सत्यापित)

| घटक | स्थिति |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login पूर्ण; baseUrl हार्डकोडेड `http://127.0.0.1:8788/api` (Flutter सापेक्ष `/api` उपयोग करता है); login() का कोई कॉलर नहीं |
| `pages/LoginPage.ets` | सिम्युलेटेड लॉगिन (setTimeout 1s जंप), टिप्पणी "replace with actual API call" |
| `pages/DashboardPage.ets` | `@State` हार्डकोडेड मेट्रिक (totalCost=1250000 आदि) |
| `pages/CampaignListPage.ets` | L187 टिप्पणी प्लेसहोल्डर `/campaigns` |
| `pages/AccountPage.ets` | L138 टिप्पणी प्लेसहोल्डर `/accounts` |
| `pages/AlertPage.ets` | L146 टिप्पणी प्लेसहोल्डर `/alerts` |
| `pages/ReportPage.ets` | L242 टिप्पणी प्लेसहोल्डर `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric मौजूद |
| i18n | StringResources.ets (15+ keys) |

## Task 1: ApiClient संवर्धन

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### डिज़ाइन मुख्य बिंदु
- **baseUrl कॉन्फ़िगर करने योग्य बनाएँ**: setBaseUrl रखें, डिफ़ॉल्ट मान अभी `http://127.0.0.1:8788/api` (रियल डिवाइस/एम्युलेटर को लैन पता चाहिए, टिप्पणी में बताएँ); Flutter जैसी सापेक्ष पथ से बचें (ArkTS को पूर्ण URL चाहिए)
- **डुप्लिकेट replayHeaders bug ठीक करें**: `{ ...this.replayHeaders(), ...this.replayHeaders() }` बार-बार विस्तार (get विधि में) → एक बार
- **login() रिटर्न मान अनुकूलन**: service `POST /api/auth/login` लौटाता है `{access_token, token_type, expires_in, user}` (`service/plugin/ads-api/controller/v1/AuthController.php` के वास्तविक फ़ील्ड की तुलना करें — access_token है, token नहीं, सत्यापन के बाद `data.token` जाँच सुधारें)
- **त्रुटि हैंडलिंग**: resp.responseCode 2xx न होने पर एरर/स्पष्ट त्रुटि जानकारी; JSON.parse विफलता सुरक्षा
- get/post/put/delete का `data.data` (ApiResponse अनरैप) रिटर्न करने का मौजूदा सम्मेलन बनाए रखें

## Task 2: LoginPage वास्तविक लॉगिन

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### डिज़ाइन मुख्य बिंदु
- `handleLogin()` कॉल `ApiClient.login(username, password)`; सफलता → setToken + Dashboard जंप; विफलता → toast त्रुटि संदेश
- लोडिंग स्थिति isLoading पहले से मौजूद, पुनः उपयोग करें
- त्रुटि संदेश में प्राथमिकता service का message (ApiResponse envelope), न हो तो सामान्य टेक्स्ट

## Task 3: पाँच बिज़नेस पेजों को वास्तविक बनाना

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`、`CampaignListPage.ets`、`AccountPage.ets`、`AlertPage.ets`、`ReportPage.ets`

### एंडपॉइंट तुलना (Phase 7 ऑडिट पुष्टि, Flutter फिक्स के बाद समान)
| पेज | कॉल | पार्सिंग |
|---|---|---|
| DashboardPage | `GET /reports/summary` (आज की अवधि) | `data.overview` → totalCost/total_impressions/avg_ctr आदि (राशि फ़ेन में, formatFen मौजूद) |
| CampaignListPage | `GET /campaigns` | `data.list` (पेजिनेशन) → Campaign model |
| AccountPage | `GET /accounts` | `data.list` → PlatformAccount model |
| AlertPage | `GET /alerts/logs` | `data.list` → AlertLog फ़ील्ड (metric/rule_name/current_value/condition/threshold/status) |
| ReportPage | `GET /reports/custom` (date_start/date_end/dimensions[]/metrics[]) | `data.list` → ReportMetric |

### डिज़ाइन मुख्य बिंदु
- पेज लोड (aboutToAppear) पर अनुरोध ट्रिगर करें; @State डेटा इनिशियलाइज़ेशन खाली/0, सिम्युलेटेड मान का अवशेष न रहे
- लोड विफलता पर त्रुटि + रीट्राय दिखाएँ (Flutter पेजों के त्रुटि/रीट्राय पैटर्न देखें)
- मुद्रा इकाई: service फ़ेन में संख्या लौटाता है, formatFen पहले से संभालता है
- **कोई नई फ़ाइल नहीं**, प्रत्येक पेज की मौजूदा UI संरचना और i18n बनाए रखें

## Task 4: सत्यापन

### स्वीकृति
- [ ] ApiClient में डुप्लिकेट replayHeaders नहीं, login रिटर्न फ़ील्ड AuthController के अनुरूप
- [ ] 6 पेजों में हार्डकोडेड सिम्युलेटेड बिज़नेस डेटा अवशेष नहीं (grep सत्यापन)
- [ ] 5 बिज़नेस पेजों के कॉल पाथ service रूट से एक-से-एक मेल (`service/plugin/ads-api/config/route.php` की तुलना)
- [ ] ArkTS सिंटैक्स चेक (इस वातावरण में hvigor/DevEco टूलचेन हो तो चलाएँ; न हो तो बताएँ और मैनुअल जाँच)
- [ ] रिग्रेशन: service PHPUnit अप्रभावित
