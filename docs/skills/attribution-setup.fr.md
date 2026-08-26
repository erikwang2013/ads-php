# Attribution inter-plateformes

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

Configurer et utiliser le moteur d'attribution pour analyser les sources de conversion.

## Préparation des données

1. Créer la table des événements de conversion :

```sql
-- erik_conversions: 转化事件
INSERT INTO erik_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. Les données de points de contact proviennent de `erik_report_metrics` (les enregistrements avec clicks > 0 servent de points de contact).

## Modèles d'attribution

| Modèle | Algorithme | Cas d'usage |
|------|------|----------|
| `first_touch` | Premier point de contact 100 % | Notoriété de marque |
| `last_touch` | Dernier point de contact 100 % | Conversion directe |
| `linear` | Répartition égale entre tous les points de contact | Diffusion continue |
| `time_decay` | e^(-λ×Δt), demi-vie de 7 jours | Sensible au temps |
| `position_based` | 40 % premier + 40 % dernier + 20 % intermédiaire | Évaluation globale |

## Appels API

```bash
# 获取模型列表
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# 执行归因计算
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

Réponse :
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

## Appel direct d'AttributionEngine

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

## Paramètres de configuration

| Paramètre | Valeur par défaut | Description |
|------|--------|------|
| `lookbackDays` | 30 | Fenêtre de remontée |
| `halfLife` | 7.0 | Demi-vie de l'atténuation temporelle (jours) |
