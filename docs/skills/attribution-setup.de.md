# Plattformübergreifende Attribution

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

Attributions-Engine einrichten und verwenden, um Konversionsquellen zu analysieren.

## Datenvorbereitung

1. Tabelle der Konversionsereignisse erstellen:

```sql
-- ads_conversions: 转化事件
INSERT INTO ads_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. Touchpoint-Daten stammen aus `ads_report_metrics` (Datensätze mit clicks > 0 gelten als Touchpoints).

## Attributionsmodelle

| Modell | Algorithmus | Anwendungsszenario |
|------|------|----------|
| `first_touch` | Erster Touchpoint 100% | Markenbekanntheit |
| `last_touch` | Letzter Touchpoint 100% | Direkte Konversion |
| `linear` | Alle Touchpoints gleichverteilt | Dauerschaltung |
| `time_decay` | e^(-λ×Δt), Halbwertszeit 7 Tage | Zeitlich sensibel |
| `position_based` | Erste 40% + letzte 40% + Mitte 20% | Gesamtbewertung |

## API-Aufruf

```bash
# 获取模型列表
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# 执行归因计算
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

Antwort:
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

## Direkter Aufruf der AttributionEngine

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

## Konfigurationsparameter

| Parameter | Standardwert | Beschreibung |
|------|--------|------|
| `lookbackDays` | 30 | Rückblick-Fenster |
| `halfLife` | 7.0 | Halbwertszeit des Zeitabfalls (Tage) |
