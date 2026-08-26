# Phase 5: 경보 푸시 시스템 구현 계획

[中文](docs/superpowers/plans/2026-05-15-phase5-alert-system.md) | [English](docs/superpowers/plans/2026-05-15-phase5-alert-system.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase5-alert-system.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase5-alert-system.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase5-alert-system.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase5-alert-system.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase5-alert-system.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase5-alert-system.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase5-alert-system.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase5-alert-system.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase5-alert-system.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase5-alert-system.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase5-alert-system.ja.md)

**목표:** 광고 데이터 모니터링 경보 구현 — 사용자 정의 규칙(비용 초과/ROI 과다 저하/전환 급감)을 지원하고 여러 채널을 통해 푸시합니다.

## Task 25: 경보 데이터 모델 + 규칙 엔진

### Files:
- Create: `service/plugin/ads-alert/config/plugin.php`
- Create: `service/plugin/ads-alert/model/AlertRule.php`
- Create: `service/plugin/ads-alert/model/AlertLog.php`
- Create: `service/plugin/ads-alert/service/AlertEngine.php`
- Create: `service/plugin/ads-alert/migration/create_alerts.sql`

### DB Schema:
```sql
CREATE TABLE alert_rules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    name VARCHAR(100) NOT NULL COMMENT '规则名称',
    metric VARCHAR(32) NOT NULL COMMENT 'cost/impressions/clicks/conversions/ctr/cvr/roi',
    condition VARCHAR(16) NOT NULL COMMENT 'gt/gte/lt/lte/eq/pct_change',
    threshold DECIMAL(12,2) NOT NULL COMMENT '阈值',
    scope VARCHAR(32) DEFAULT 'tenant' COMMENT 'tenant/platform/campaign',
    platform VARCHAR(32) NULL,
    campaign_id BIGINT NULL,
    check_interval INT DEFAULT 5 COMMENT '检查间隔(分钟)',
    channels JSON NULL COMMENT '通知渠道: ["web","email","sms"]',
    enabled TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE alert_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    rule_id BIGINT NOT NULL,
    metric VARCHAR(32) NOT NULL,
    current_value DECIMAL(12,2) NOT NULL,
    threshold DECIMAL(12,2) NOT NULL,
    condition VARCHAR(16) NOT NULL,
    status ENUM('triggered','acknowledged','resolved') DEFAULT 'triggered',
    acknowledged_by BIGINT NULL,
    resolved_at DATETIME NULL,
    extra JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### AlertEngine 서비스:
- `evaluate(AlertRule, int $tenantId): ?AlertLog` — 현재 데이터를 기준으로 단일 규칙 실행
- `evaluateAll(): array` — 활성화된 전체 규칙 실행
- `checkMetric(string $metric, array $scope, string $condition, float $threshold): bool`
- `buildQuery(string $metric, array $scope): QueryBuilder` — 현재 지표 값을 조회하는 SQL 쿼리 생성
- `checkPctChange(string $metric, array $scope, float $threshold): bool` — 오늘과 어제 비교
- `notify(AlertLog, AlertRule): void` — 구성된 채널로 디스패치

### API 엔드포인트:
- `GET /api/v1/alerts/rules` — 규칙 목록
- `POST /api/v1/alerts/rules` — 규칙 생성
- `PUT /api/v1/alerts/rules/{id}` — 규칙 수정
- `DELETE /api/v1/alerts/rules/{id}` — 규칙 삭제
- `GET /api/v1/alerts/logs` — 경보 이력 목록
- `POST /api/v1/alerts/logs/{id}/acknowledge` — 경보 확인 처리

## Task 26: 경보 푸시 채널

### Files:
- Create: `service/plugin/ads-alert/channel/WebChannel.php`
- Create: `service/plugin/ads-alert/channel/EmailChannel.php`
- Create: `service/plugin/ads-alert/channel/SmsChannel.php`
- Create: `service/plugin/ads-alert/channel/WebhookChannel.php`
- Create: `service/plugin/ads-alert/service/NotificationService.php`

### NotificationService:
- `send(AlertLog, AlertRule): void` — 구성된 전체 채널로 전송
- `pushToFrontend(AlertLog): void` — 실시간 대시보드 푸시용 Redis pub/sub

### Admin UI 페이지:
- `admin/src/views/alert/AlertRuleList.vue` — 규칙 CRUD
- `admin/src/views/alert/AlertLogList.vue` — 필터가 있는 경보 이력
- `admin/src/components/AlertBadge.vue` — 미확인 경보 수를 표시하는 내비게이션 바 배지

## Task 27: 경보 스케줄링 + 실시간 푸시

### Files:
- Modify: `service/plugin/ads-task/config/cron.php` — 5분마다 실행되는 AlertCheckTask 추가
- Create: `service/plugin/ads-task/task/AlertCheckTask.php`
- Create: `service/plugin/ads-api/controller/AlertController.php`
- Modify: `service/plugin/ads-api/config/route.php` — alert 라우트 추가
- Create: `admin/src/stores/alert.ts` — 실시간 경보 폴링
- Modify: `admin/src/components/layout/TopBar.vue` — 경보 배지

### WebSocket(선택, Phase 5):
- 실시간 경보 푸시용 Redis pub/sub 채널
- 프론트엔드는 EventSource 또는 폴링으로 구독
