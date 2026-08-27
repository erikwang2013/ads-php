# 跨平台归因

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

设置和使用归因引擎分析转化来源。

## 数据准备

1. 创建转化事件表：

```sql
-- ads_conversions: 转化事件
INSERT INTO ads_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. 触点数据来自 `ads_report_metrics`（clicks > 0 的记录作为触点）。

## 归因模型

| 模型 | 算法 | 适用场景 |
|------|------|----------|
| `first_touch` | 首次触点 100% | 品牌认知类 |
| `last_touch` | 末次触点 100% | 直接转化类 |
| `linear` | 所有触点均分 | 持续投放类 |
| `time_decay` | e^(-λ×Δt), 7天半衰期 | 时效敏感类 |
| `position_based` | 首40%+末40%+中间20% | 综合评估 |

## API 调用

```bash
# 获取模型列表
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# 执行归因计算
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

响应：
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

## AttributionEngine 直接调用

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

## 配置参数

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `lookbackDays` | 30 | 回溯窗口 |
| `halfLife` | 7.0 | 时间衰减半衰期（天） |
