# Phase 5: 告警推送系统 Implementation Plan

**Goal:** 实现广告数据监控告警，支持自定义规则（花费超限/ROI过低/转化骤降），通过多种渠道推送。

## Task 25: 告警数据模型 + 规则引擎

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

### AlertEngine service:
- `evaluate(AlertRule, int $tenantId): ?AlertLog` — runs a single rule against current data
- `evaluateAll(): array` — runs all enabled rules
- `checkMetric(string $metric, array $scope, string $condition, float $threshold): bool`
- `buildQuery(string $metric, array $scope): QueryBuilder` — builds the SQL query to get current metric value
- `checkPctChange(string $metric, array $scope, float $threshold): bool` — compares today vs yesterday
- `notify(AlertLog, AlertRule): void` — dispatches to configured channels

### API endpoints:
- `GET /api/v1/alerts/rules` — list rules
- `POST /api/v1/alerts/rules` — create rule
- `PUT /api/v1/alerts/rules/{id}` — update rule
- `DELETE /api/v1/alerts/rules/{id}` — delete rule
- `GET /api/v1/alerts/logs` — list alert history
- `POST /api/v1/alerts/logs/{id}/acknowledge` — acknowledge alert

## Task 26: 告警推送渠道

### Files:
- Create: `service/plugin/ads-alert/channel/WebChannel.php`
- Create: `service/plugin/ads-alert/channel/EmailChannel.php`
- Create: `service/plugin/ads-alert/channel/SmsChannel.php`
- Create: `service/plugin/ads-alert/channel/WebhookChannel.php`
- Create: `service/plugin/ads-alert/service/NotificationService.php`

### NotificationService:
- `send(AlertLog, AlertRule): void` — sends via all configured channels
- `pushToFrontend(AlertLog): void` — Redis pub/sub for real-time dashboard push

### Admin UI pages:
- `admin/src/views/alert/AlertRuleList.vue` — CRUD for rules
- `admin/src/views/alert/AlertLogList.vue` — alert history with filters
- `admin/src/components/AlertBadge.vue` — nav bar badge showing unacknowledged count

## Task 27: 告警调度 + 实时推送

### Files:
- Modify: `service/plugin/ads-task/config/cron.php` — add AlertCheckTask every 5 min
- Create: `service/plugin/ads-task/task/AlertCheckTask.php`
- Create: `service/plugin/ads-api/controller/AlertController.php`
- Modify: `service/plugin/ads-api/config/route.php` — add alert routes
- Create: `admin/src/stores/alert.ts` — real-time alert polling
- Modify: `admin/src/components/layout/TopBar.vue` — alert badge

### WebSocket (optional Phase 5):
- Redis pub/sub channel for real-time alert push
- Frontend subscribes via EventSource or polling
