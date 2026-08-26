# الإسناد عبر المنصات (Cross-Platform Attribution)

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

إعداد واستخدام محرك الإسناد لتحليل مصادر التحويلات.

## تحضير البيانات

1. إنشاء جدول أحداث التحويل:

```sql
-- erik_conversions: 转化事件
INSERT INTO erik_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. بيانات نقاط التلامس تأتي من `erik_report_metrics` (السجلات ذات clicks > 0 تُعتبر نقاط تلامس).

## نماذج الإسناد

| النموذج | الخوارزمية | سيناريو الاستخدام |
|------|------|----------|
| `first_touch` | نقطة التلامس الأولى 100% | التوعية بالعلامة التجارية |
| `last_touch` | نقطة التلامس الأخيرة 100% | التحويل المباشر |
| `linear` | توزيع متساوٍ على جميع نقاط التلامس | الحملات المستمرة |
| `time_decay` | e^(-λ×Δt)، نصف عمر 7 أيام | الحساسة للتوقيت |
| `position_based` | أول 40% + آخر 40% + وسط 20% | التقييم الشامل |

## استدعاء API

```bash
# 获取模型列表
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# 执行归因计算
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

الاستجابة:
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

## الاستدعاء المباشر لـ AttributionEngine

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

## معاملات التكوين

| المعامل | القيمة الافتراضية | الوصف |
|------|--------|------|
| `lookbackDays` | 30 | نافذة الاسترجاع |
| `halfLife` | 7.0 | نصف عمر الاضمحلال الزمني (أيام) |
