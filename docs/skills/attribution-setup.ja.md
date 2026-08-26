# クロスプラットフォームアトリビューション

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

アトリビューションエンジンを設定・使用して変換の発生源を分析します。

## データ準備

1. 変換イベントテーブルを作成:

```sql
-- erik_conversions: 変換イベント
INSERT INTO erik_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. タッチポイントデータは `erik_report_metrics` から取得（clicks > 0 のレコードをタッチポイントとする）。

## アトリビューションモデル

| モデル | アルゴリズム | 適用シーン |
|------|------|----------|
| `first_touch` | 最初のタッチポイント 100% | ブランド認知系 |
| `last_touch` | 最後のタッチポイント 100% | 直接変換系 |
| `linear` | 全タッチポイントに均等配分 | 継続配信系 |
| `time_decay` | e^(-λ×Δt), 7 日半減期 | 鮮度重視系 |
| `position_based` | 先頭40%+末尾40%+中間20% | 総合評価 |

## API 呼び出し

```bash
# モデルリストを取得
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# アトリビューション計算を実行
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

レスポンス:
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

## AttributionEngine の直接呼び出し

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

## 設定パラメータ

| パラメータ | デフォルト値 | 説明 |
|------|--------|------|
| `lookbackDays` | 30 | 遡及ウィンドウ |
| `halfLife` | 7.0 | 時間減衰の半減期（日） |
