# 플랫폼 간 기여도

[中文](docs/skills/attribution-setup.md) | [English](docs/skills/attribution-setup.en.md) | [한국어](docs/skills/attribution-setup.ko.md) | [Русский](docs/skills/attribution-setup.ru.md) | [Deutsch](docs/skills/attribution-setup.de.md) | [Français](docs/skills/attribution-setup.fr.md) | [Español](docs/skills/attribution-setup.es.md) | [Português](docs/skills/attribution-setup.pt.md) | [हिन्दी](docs/skills/attribution-setup.hi.md) | [العربية](docs/skills/attribution-setup.ar.md) | [বাংলা](docs/skills/attribution-setup.bn.md) | [Bahasa Indonesia](docs/skills/attribution-setup.id.md) | [日本語](docs/skills/attribution-setup.ja.md)

귀인 엔진을 설정하고 사용하여 전환 출처를 분석합니다.

## 데이터 준비

1. 전환 이벤트 테이블 생성:

```sql
-- ads_conversions: 转化事件
INSERT INTO ads_conversions (id, tenant_id, platform, campaign_id, conversion_time, value, order_id)
VALUES (snowflake_id(), 1, 'juliang', 1, NOW(), 299.00, 'ORD-001');
```

2. 터치포인트 데이터는 `ads_report_metrics`에서 옵니다（clicks > 0 기록을 터치포인트로 사용）.

## 기여도 모델

| 모델 | 알고리즘 | 적용 시나리오 |
|------|------|----------|
| `first_touch` | 첫 번째 터치포인트 100% | 브랜드 인지형 |
| `last_touch` | 마지막 터치포인트 100% | 직접 전환형 |
| `linear` | 모든 터치포인트 균등 분배 | 지속 집행형 |
| `time_decay` | e^(-λ×Δt), 7일 반감기 | 시효 민감형 |
| `position_based` | 처음 40%+마지막 40%+중간 20% | 종합 평가 |

## API 호출

```bash
# 모델 목록 가져오기
curl -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8788/api/reports/attribution/models

# 기여도 계산 실행
curl -H "Authorization: Bearer <token>" \
  "http://127.0.0.1:8788/api/reports/attribution?model=last_touch&date_start=2026-05-01&date_end=2026-05-22"
```

응답:
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

## AttributionEngine 직접 호출

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

## 설정 파라미터

| 파라미터 | 기본값 | 설명 |
|------|--------|------|
| `lookbackDays` | 30 | 소급 창 |
| `halfLife` | 7.0 | 시간 감쇠 반감기（일） |
