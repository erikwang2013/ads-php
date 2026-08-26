# Cross-Platform Attribution

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

Set up and use the attribution engine to analyze conversion sources.

## Data Preparation

1. Create the conversion event table:

```sql
-- erik_conversions: 转化事件
INSERT INTO erik_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. Touchpoint data comes from `erik_report_metrics` (records with clicks > 0 act as touchpoints).

## Attribution Models

| Model | Algorithm | Use Case |
|-------|-----------|----------|
| `first_touch` | First touchpoint gets 100% | Brand awareness |
| `last_touch` | Last touchpoint gets 100% | Direct conversion |
| `linear` | All touchpoints share equally | Ongoing campaigns |
| `time_decay` | e^(-λ×Δt), 7-day half-life | Time-sensitive |
| `position_based` | First 40% + last 40% + middle 20% | Balanced assessment |

## API Calls

```bash
# Get model list
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# Run attribution
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

Response:
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

## Calling AttributionEngine Directly

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

## Configuration Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `lookbackDays` | 30 | Lookback window |
| `halfLife` | 7.0 | Time-decay half-life (days) |
