# फ़ंक्शन डिज़ाइन दस्तावेज़

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> सभी API इंटरफ़ेस परिभाषाएँ（अनुरोध/प्रतिक्रिया/पैरामीटर）[api.hi.md](api.hi.md) में देखें।

---

## मॉड्यूल अवलोकन

| # | मॉड्यूल | कंट्रोलर/सेवा | API रूट संख्या | Vue पेज |
|---|------|--------|-----------|----------|
| 1 | प्रमाणीकरण/प्राधिकरण | AuthController | 3 | LoginPage |
| 2 | प्लेटफ़ॉर्म प्रबंधन | PlatformController | 3 | — |
| 3 | खाता प्रबंधन | AccountController | 5 | AccountList, AccountBind |
| 4 | विज्ञापन अभियान | CampaignController | 6 | CampaignList |
| 5 | विज्ञापन समूह | AdGroupController | 5 | AdGroupList |
| 6 | विज्ञापन क्रिएटिव | CreativeController | 2 | CreativeList |
| 7 | डेटा रिपोर्ट | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | अलर्ट मॉनिटरिंग | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | नोटिफिकेशन सेंटर | NotificationController | 4 | NotificationList |
| 10 | स्वचालित बिडिंग | BidRuleController | 5 | BidRuleList |
| 11 | टार्गेटिंग टेम्पलेट | TargetingTemplateController | 5 | — |
| 12 | सिस्टम प्रबंधन | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | डेटा सिंक | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | एसेट लाइब्रेरी | AssetController | 4 | AssetGallery |
| 15 | बजट अलर्ट | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | डिलीवरी कैलेंडर | CalendarService | 1 | CampaignCalendar |
| 17 | क्रॉस-प्लेटफ़ॉर्म एट्रिब्यूशन | AttributionEngine | 2 | AttributionReport |
| 18 | हेल्थ चेक | HealthController | 2 | — |
| 19 | कैप्चा | CaptchaController | 2 | — |
| 20 | API दस्तावेज़ | DocController | 1 | — |

**कुल**: 20 मॉड्यूल, 65+ रूट, 18 Vue पेज

---

## मॉड्यूल 1: प्रमाणीकरण/प्राधिकरण

- कैप्चा जाँच (वैकल्पिक)
- `admin_users` टेबल क्वेरी
- bcrypt `password_verify()` सत्यापन
- JWT Token जनरेशन (24h TTL)
- पुराने Token स्वचालित रूप से ब्लैकलिस्ट में
- Token से `uid` निकालकर उपयोगकर्ता जानकारी क्वेरी करें

इंटरफ़ेस: लॉगिन / Token रिफ़्रेश / वर्तमान उपयोगकर्ता → [api.hi.md मॉड्यूल 2](api.hi.md#模块-2-认证)

---

## मॉड्यूल 2-3: प्लेटफ़ॉर्म और खाता प्रबंधन

- प्लेटफ़ॉर्म सूची 1 घंटे कैश (Redis), Season ध्वज emoji एकीकृत
- OAuth प्रवाह: रैंडम state जनरेट करें → प्राधिकरण URL बनाएँ → कॉलबैक संभालें → Token स्टोर करें
- खाता सूची/विवरण 5 मिनट कैश

इंटरफ़ेस: प्लेटफ़ॉर्म सूची / OAuth / खाता CRUD + सिंक → [api.hi.md मॉड्यूल 3](api.hi.md#模块-3-平台--账户)

---

## मॉड्यूल 4-6: विज्ञापन डिलीवरी पदानुक्रम

### डेटा संरचना

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- अभियान निर्माण प्लेटफ़ॉर्म एडाप्टर के माध्यम से + लोकल में लिखना
- प्लेटफ़ॉर्म/स्थिति/कीवर्ड द्वारा फ़िल्टरिंग, सूची में आज का सारांश शामिल
- विज्ञापन समूह निर्माण `targeting_template_id` के माध्यम से टार्गेटिंग टेम्पलेट लोड करता है

इंटरफ़ेस: अभियान / विज्ञापन समूह / क्रिएटिव → [api.hi.md मॉड्यूल 4-6](api.hi.md#模块-4-广告计划)

---

## मॉड्यूल 7: डेटा रिपोर्ट

- डैशबोर्ड सारांश 5 मिनट कैश: 8 KPI मेट्रिक कार्ड + दैनिक ट्रेंड लाइन चार्ट + प्लेटफ़ॉर्म बार चार्ट
- कस्टम रिपोर्ट आयाम: date, platform, campaign
- मेट्रिक्स: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- एक्सपोर्ट फ़ॉर्मेट: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (HTML प्रिंट)

इंटरफ़ेस: सारांश / कस्टम / एक्सपोर्ट → [api.hi.md मॉड्यूल 7](api.hi.md#模块-7-报表)

---

## मॉड्यूल 8: अलर्ट मॉनिटरिंग

### AlertEngine मूल्यांकन प्रवाह

```
遍历 enabled=1 的规则
  → 查询 ads_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### नोटिफिकेशन चैनल

| चैनल | स्थिति | कार्यान्वयन |
|------|------|------|
| web | ✅ | `ads_notifications` में लिखें |
| email | प्लेसहोल्डर | echo स्टब |
| sms | प्लेसहोल्डर | echo स्टब |
| Redis pub/sub | ✅ | `alert:new` चैनल पर JSON पुश |

इंटरफ़ेस: नियम CRUD / अलर्ट रिकॉर्ड / पुष्टि / अपठित संख्या → [api.hi.md मॉड्यूल 8](api.hi.md#模块-8-告警)

---

## मॉड्यूल 9: नोटिफिकेशन सेंटर

- फ्रंटएंड Pinia store 30s पोलिंग
- साइडबार घंटी आइकन + अपठित संख्या बैज

इंटरफ़ेस: सूची / अपठित संख्या / पढ़ा-चिह्नित / सभी पढ़े → [api.hi.md मॉड्यूल 9](api.hi.md#模块-9-通知)

---

## मॉड्यूल 10: स्वचालित बिडिंग इंजन

### BidEngine मूल्यांकन प्रवाह

```
遍历 enabled=1 的规则
  → 查询 ads_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 冷却检查 (cooldown_minutes 内是否有过操作)
  → 执行动作:
    - adjust_budget: 新预算 = current + adjust_step, 限制 [budget_min, budget_max]
    - toggle_pause: 暂停计划
    - toggle_enable: 启用计划
  → 通过 AdapterRegistry → PlatformAdapter 调用平台 API
  → 更新本地 DB + 写入 BidLog
```

### नियम फ़ील्ड

| फ़ील्ड | प्रकार | विवरण |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | मॉनिटरिंग मेट्रिक |
| condition | gt/gte/lt/lte | ट्रिगर शर्त |
| threshold | DECIMAL(12,2) | थ्रेशोल्ड |
| scope | tenant/platform/campaign | दायरा |
| action_type | adjust_budget/toggle_pause/toggle_enable | क्रिया |
| adjust_step | INT (रुपये) | बजट समायोजन चरण (धनात्मक=बढ़ाएँ, ऋणात्मक=घटाएँ) |
| budget_min, budget_max | BIGINT | बजट सीमाएँ |
| cooldown_minutes | INT | कूलडाउन अवधि |

इंटरफ़ेस: नियम CRUD / बिडिंग इतिहास → [api.hi.md मॉड्यूल 10](api.hi.md#模块-10-自动出价)

---

## मॉड्यूल 11: ऑडियंस टार्गेटिंग टेम्पलेट

### विज्ञापन समूह में एकीकरण

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### सामान्य JSON Schema

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

इंटरफ़ेस: टेम्पलेट CRUD → [api.hi.md मॉड्यूल 11](api.hi.md#模块-11-定向模板)

---

## मॉड्यूल 12: सिस्टम प्रबंधन (Admin)

- उपयोगकर्ता सूची ID hashids एन्कोडेड
- उपयोगकर्ता निर्माण पर bcrypt हैश पासवर्ड
- उपयोगकर्ता निष्क्रिय करना सॉफ्ट डिसेबल है (status=0)

ऑडिट लॉग फ़ील्ड: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

इंटरफ़ेस: उपयोगकर्ता प्रबंधन / ऑडिट लॉग / भूमिकाएँ → [api.hi.md Admin एंडपॉइंट](api.hi.md#admin-端点端口-8789)

---

## मॉड्यूल 13: डेटा सिंक

### DataSyncTask प्रवाह (हर 10 मिनट)

```
遍历 sync_enabled=1 的账户
  → 获取平台适配器
  → 同步 Campaigns (fetchCampaigns → updateOrInsert)
  → 同步 AdGroups (fetchAdGroups → 遍历每 campaign)
  → 同步 Creatives (fetchCreatives → 遍历每 ad_group)
  → 同步 Reports (fetchReports → 过去 2 天 daily, 9 个指标)
  → 清除 Dashboard 缓存
  → 更新 last_sync_at
```

---

## प्रतिक्रिया फ़ॉर्मेट

### सफलता
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### पेजिनेशन
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### त्रुटि
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## मॉड्यूल 14: विज्ञापन एसेट लाइब्रेरी

- समर्थित प्रकार: image/jpeg, image/png, image/gif, image/webp, video/mp4
- फ़ाइल स्टोरेज: `public/uploads/assets/`
- फ्रंटएंड: ग्रिड गैलरी + ड्रैग-ड्रॉप अपलोड + छवि पूर्वावलोकन + वीडियो प्ले + URL कॉपी

इंटरफ़ेस: अपलोड / सूची / विवरण / डिलीट → [api.hi.md मॉड्यूल 12](api.hi.md#模块-12-素材库)

---

## मॉड्यूल 15: बजट अलर्ट

- तीन-स्तरीय अलर्ट: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask हर 15 मिनट चलता है
- डी-डुप्लिकेशन: एक ही अभियान एक ही स्तर पर दिन में केवल एक बार नोटिफ़ाई
- `ads_notifications` टेबल में लिखें

इंटरफ़ेस: बजट अलर्ट → [api.hi.md मॉड्यूल 7](api.hi.md#模块-7-报表)

---

## मॉड्यूल 16: डिलीवरी कैलेंडर

- तारीख के अनुसार campaign शेड्यूल एकत्र करें
- फ्रंटएंड Gantt चार्ट: x-अक्ष तारीख, y-अक्ष अभियान, प्लेटफ़ॉर्म के अनुसार रंग अलग
- मास/सप्ताह दृश्य स्विचिंग समर्थित

इंटरफ़ेस: डिलीवरी कैलेंडर → [api.hi.md मॉड्यूल 7](api.hi.md#模块-7-报表)

---

## मॉड्यूल 17: क्रॉस-प्लेटफ़ॉर्म एट्रिब्यूशन

### एट्रिब्यूशन मॉडल

| मॉडल | एल्गोरिदम |
|------|------|
| first_touch | पहला टचपॉइंट 100% |
| last_touch | अंतिम टचपॉइंट 100% |
| linear | सभी टचपॉइंट बराबर विभाजित (1/N) |
| time_decay | e^(-λ×Δt), 7-दिन आधा जीवन |
| position_based | पहला 40% + अंतिम 40% + मध्य 20% |

- रिट्रोस्पेक्ट विंडो: 30 दिन
- टचपॉइंट स्रोत: `ads_report_metrics` (क्लिक > 0)
- परिणाम `ads_attribution_results` में लिखे जाते हैं
- फ्रंटएंड: AttributionReport.vue मॉडल स्विचिंग + स्टैटिस्टिक कार्ड + ECharts बार चार्ट + विवरण तालिका

### डेटा टेबल

| टेबल | फ़ील्ड |
|----|------|
| `ads_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `ads_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

इंटरफ़ेस: एट्रिब्यूशन विश्लेषण / मॉडल सूची → [api.hi.md मॉड्यूल 7](api.hi.md#模块-7-报表)

### हेल्थ चेक
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```

---

## मॉड्यूल 18: प्लेटफ़ॉर्म कॉल रेज़िलिएंस (सर्किट ब्रेकर / डिग्रेडेशन)

### सर्किट ब्रेकर स्टेट मशीन

`CircuitBreaker` (service/plugin/ads-platform/src/CircuitBreaker.php) — प्रति-प्लेटफ़ॉर्म स्टेट मशीन:

| स्थिति | ट्रिगर | व्यवहार |
|--------|---------|----------|
| CLOSED | सामान्य | कॉल पास |
| OPEN | लगातार 5 विफलताएँ | फ़ास्ट-फ़ेल, प्लेटफ़ॉर्म छोड़ें |
| HALF_OPEN | 30s कूलडाउन के बाद | एक प्रोब अनुरोध |
| CLOSED | प्रोब सफल | पुनर्स्थापित, काउंटर रीसेट |
| OPEN | प्रोब फिर विफल | फिर से तोड़ें |

### GuardedAdapter प्रॉक्सी

- `AdapterRegistry::get()` GuardedAdapter प्रॉक्सी लौटाता है; 14 कॉल साइट, शून्य बदलाव
- OPEN होने पर `CircuitBreakerOpenException` (फ़ास्ट-फ़ेल) फेंकता है; टास्क लेयर कैच कर अवशोषित करती है = प्रति-प्लेटफ़ॉर्म डिग्रेडेशन
- Generator विधि: पूर्ण इटरेशन → success, रुकावट → failure

### टाइमआउट जाँच

- सभी 29 एडेप्टर में CURLOPT_TIMEOUT (30/60s) + CURLOPT_CONNECTTIMEOUT (10s)

### टेस्ट कवरेज

- CircuitBreakerTest 8 मामले + GuardedAdapterTest 13 मामले

### ज्ञात सीमा

- एकल-नोड इन-मेमोरी स्टेट; मल्टी-नोड के लिए Redis साझा स्टेट चाहिए
