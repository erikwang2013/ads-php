# ক্রস-প্ল্যাটফর্ম অ্যাট্রিবিউশন

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

কনভার্সন উৎস বিশ্লেষণের জন্য অ্যাট্রিবিউশন ইঞ্জিন সেটআপ ও ব্যবহার।

## ডেটা প্রস্তুতি

1. কনভার্সন ইভেন্ট টেবিল তৈরি করুন:

```sql
-- ads_conversions: 转化事件
INSERT INTO ads_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. টাচপয়েন্ট ডেটা `ads_report_metrics` থেকে আসে (clicks > 0 রেকর্ডগুলো টাচপয়েন্ট হিসেবে)।

## অ্যাট্রিবিউশন মডেল

| মডেল | অ্যালগরিদম | প্রযোজ্য পরিস্থিতি |
|------|------|----------|
| `first_touch` | প্রথম টাচপয়েন্ট 100% | ব্র্যান্ড অ্যাওয়ারনেস টাইপ |
| `last_touch` | শেষ টাচপয়েন্ট 100% | সরাসরি কনভার্সন টাইপ |
| `linear` | সব টাচপয়েন্ট সমান ভাগ | ধারাবাহিক ডেলিভারি টাইপ |
| `time_decay` | e^(-λ×Δt), 7 দিন হাফ-লাইফ | সময়-সংবেদনশীল টাইপ |
| `position_based` | প্রথম 40% + শেষ 40% + মাঝের 20% | সমন্বিত মূল্যায়ন |

## API কল

```bash
# 获取模型列表
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# 执行归因计算
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

রেসপন্স:
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

## AttributionEngine সরাসরি কল

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

## কনফিগ প্যারামিটার

| প্যারামিটার | ডিফল্ট | বিবরণ |
|------|--------|------|
| `lookbackDays` | 30 | লুকব্যাক উইন্ডো |
| `halfLife` | 7.0 | টাইম ডিকে হাফ-লাইফ (দিন) |
