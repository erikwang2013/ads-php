# Atribuição entre plataformas

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

Como configurar e usar o mecanismo de atribuição para analisar a origem das conversões.

## Preparação de dados

1. Criar a tabela de eventos de conversão:

```sql
-- ads_conversions: 转化事件
INSERT INTO ads_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. Os dados de pontos de contato vêm de `ads_report_metrics` (registros com clicks > 0 são considerados pontos de contato).

## Modelos de atribuição

| Modelo | Algoritmo | Cenário de uso |
|------|------|----------|
| `first_touch` | Primeiro contato 100% | Reconhecimento de marca |
| `last_touch` | Último contato 100% | Conversão direta |
| `linear` | Todos os contatos igualmente divididos | Veiculação contínua |
| `time_decay` | e^(-λ×Δt), meia-vida de 7 dias | Sensível ao tempo |
| `position_based` | Primeiros 40% + últimos 40% + meio 20% | Avaliação abrangente |

## Chamadas de API

```bash
# 获取模型列表
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# 执行归因计算
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

Resposta:
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

## Chamada direta do AttributionEngine

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

## Parâmetros de configuração

| Parâmetro | Padrão | Descrição |
|------|--------|------|
| `lookbackDays` | 30 | Janela de retrospectiva |
| `halfLife` | 7.0 | Meia-vida do decaimento temporal (dias) |

