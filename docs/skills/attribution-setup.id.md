# Atribusi Lintas-Platform

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

Siapkan dan gunakan mesin atribusi untuk menganalisis sumber konversi.

## Persiapan Data

1. Buat tabel peristiwa konversi:

```sql
-- ads_conversions: 转化事件
INSERT INTO ads_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. Data titik sentuh berasal dari `ads_report_metrics` (catatan dengan clicks > 0 dianggap sebagai titik sentuh).

## Model Atribusi

| Model | Algoritma | Skenario penggunaan |
|------|------|----------|
| `first_touch` | Titik sentuh pertama 100% | Jenis kesadaran merek |
| `last_touch` | Titik sentuh terakhir 100% | Jenis konversi langsung |
| `linear` | Semua titik sentuh dibagi rata | Jenis penayangan berkelanjutan |
| `time_decay` | e^(-λ×Δt), waktu paruh 7 hari | Jenis sensitif waktu |
| `position_based` | Awal 40%+akhir 40%+tengah 20% | Evaluasi komprehensif |

## Panggilan API

```bash
# 获取模型列表
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# 执行归因计算
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

Respons:
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

## Pemanggilan Langsung AttributionEngine

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

## Parameter Konfigurasi

| Parameter | Nilai default | Keterangan |
|------|--------|------|
| `lookbackDays` | 30 | Jendela retrospektif |
| `halfLife` | 7.0 | Waktu paruh peluruhan waktu (hari) |
