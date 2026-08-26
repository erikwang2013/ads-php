# क्रॉस-प्लेटफ़ॉर्म एट्रिब्यूशन

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

कन्वर्ज़न स्रोतों के विश्लेषण के लिए एट्रिब्यूशन इंजन सेट करना और उपयोग करना।

## डेटा तैयारी

1. कन्वर्ज़न इवेंट टेबल बनाएँ:

```sql
-- erik_conversions: 转化事件
INSERT INTO erik_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. टचपॉइंट डेटा `erik_report_metrics` से आता है (clicks > 0 वाले रिकॉर्ड टचपॉइंट माने जाते हैं)।

## एट्रिब्यूशन मॉडल

| मॉडल | एल्गोरिदम | उपयुक्त परिदृश्य |
|------|------|----------|
| `first_touch` | पहला टचपॉइंट 100% | ब्रांड जागरूकता प्रकार |
| `last_touch` | अंतिम टचपॉइंट 100% | प्रत्यक्ष कन्वर्ज़न प्रकार |
| `linear` | सभी टचपॉइंट बराबर विभाजित | निरंतर डिलीवरी प्रकार |
| `time_decay` | e^(-λ×Δt), 7 दिन आधा जीवन | समय-संवेदनशील प्रकार |
| `position_based` | पहला 40% + अंतिम 40% + मध्य 20% | समग्र मूल्यांकन |

## API कॉल

```bash
# 获取模型列表
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# 执行归因计算
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

प्रतिक्रिया:
```json
{
  "code": 0,
  "data": {
    "total_conversions": 42,
    "total_value": 12345.67,
    "by_campaign": [
      { "campaign_id": 1, "credit": 5000.00 }
    ]
  }
}
```

## AttributionEngine प्रत्यक्ष कॉल

```php
use plugin\ads_report\service\AttributionEngine;

$engine = new AttributionEngine();
$result = $engine->compute(
    tenantId: 1,
    dateStart: '2026-05-01',
    dateEnd: '2026-05-22',
    model: 'position_based',
);
```

## कॉन्फ़िगरेशन पैरामीटर

| पैरामीटर | डिफ़ॉल्ट मान | विवरण |
|------|--------|------|
| `lookbackDays` | 30 | रिट्रोस्पेक्ट विंडो |
| `halfLife` | 7.0 | समय क्षय आधा जीवन (दिन) |
