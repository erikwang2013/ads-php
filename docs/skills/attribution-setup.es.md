# 跨平台归因

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

Configura y usa el motor de atribución para analizar el origen de las conversiones.

## Preparación de datos

1. Crea la tabla de eventos de conversión:

```sql
-- ads_conversions: 转化事件
INSERT INTO ads_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. Los datos de puntos de contacto provienen de `ads_report_metrics` (los registros con clicks > 0 actúan como puntos de contacto).

## Modelos de atribución

| Modelo | Algoritmo | Escenario de uso |
|------|------|----------|
| `first_touch` | Primer punto de contacto 100% | Tipo de reconocimiento de marca |
| `last_touch` | Último punto de contacto 100% | Tipo de conversión directa |
| `linear` | Todos los puntos de contacto a partes iguales | Tipo de campaña continua |
| `time_decay` | e^(-λ×Δt), semivida de 7 días | Tipo sensible a la temporalidad |
| `position_based` | Primero 40% + último 40% + medio 20% | Evaluación integral |

## Llamadas a la API

```bash
# Obtener la lista de modelos
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# Ejecutar el cálculo de atribución
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

Respuesta:
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

## Llamada directa a AttributionEngine

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

## Parámetros de configuración

| Parámetro | Valor por defecto | Descripción |
|------|--------|------|
| `lookbackDays` | 30 | Ventana de retroceso |
| `halfLife` | 7.0 | Semivida de la decadencia temporal (días) |
