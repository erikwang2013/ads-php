# Кросс-платформенная атрибуция

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

Настройка и использование движка атрибуции для анализа источников конверсий.

## Подготовка данных

1. Создайте таблицу событий конверсий:

```sql
-- ads_conversions: 转化事件
INSERT INTO ads_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. Данные точек касания берутся из `ads_report_metrics` (записи с clicks > 0 считаются точками касания).

## Модели атрибуции

| Модель | Алгоритм | Подходящие сценарии |
|------|------|----------|
| `first_touch` | Первая точка касания 100% | Для узнаваемости бренда |
| `last_touch` | Последняя точка касания 100% | Для прямых конверсий |
| `linear` | Все точки касания поровну | Для постоянных кампаний |
| `time_decay` | e^(-λ×Δt), период полураспада 7 дней | Для чувствительных к времени |
| `position_based` | Первые 40% + последние 40% + середина 20% | Комплексная оценка |

## Вызов API

```bash
# 获取模型列表
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# 执行归因计算
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

Ответ:
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

## Прямой вызов AttributionEngine

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

## Параметры конфигурации

| Параметр | По умолчанию | Описание |
|------|--------|------|
| `lookbackDays` | 30 | Окно ретроспективы |
| `halfLife` | 7.0 | Период полураспада временного затухания (дней) |
