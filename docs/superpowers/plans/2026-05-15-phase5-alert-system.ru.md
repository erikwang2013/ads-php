# Phase 5: План реализации системы оповещений об аномалиях

[中文](docs/superpowers/plans/2026-05-15-phase5-alert-system.md) | [English](docs/superpowers/plans/2026-05-15-phase5-alert-system.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase5-alert-system.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase5-alert-system.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase5-alert-system.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase5-alert-system.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase5-alert-system.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase5-alert-system.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase5-alert-system.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase5-alert-system.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase5-alert-system.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase5-alert-system.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase5-alert-system.ja.md)

**Goal:** реализовать мониторинг и оповещения по данным рекламы с поддержкой пользовательских правил (превышение расходов/низкий ROI/резкое падение конверсий) и push-уведомлениями по нескольким каналам.

## Task 25: Модель данных оповещений + движок правил

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

### Сервис AlertEngine:
- `evaluate(AlertRule, int $tenantId): ?AlertLog` — выполняет одно правило по текущим данным
- `evaluateAll(): array` — выполняет все включённые правила
- `checkMetric(string $metric, array $scope, string $condition, float $threshold): bool`
- `buildQuery(string $metric, array $scope): QueryBuilder` — строит SQL-запрос для получения текущего значения метрики
- `checkPctChange(string $metric, array $scope, float $threshold): bool` — сравнивает сегодня со вчера
- `notify(AlertLog, AlertRule): void` — отправляет по настроенным каналам

### API endpoints:
- `GET /api/v1/alerts/rules` — список правил
- `POST /api/v1/alerts/rules` — создать правило
- `PUT /api/v1/alerts/rules/{id}` — обновить правило
- `DELETE /api/v1/alerts/rules/{id}` — удалить правило
- `GET /api/v1/alerts/logs` — история оповещений
- `POST /api/v1/alerts/logs/{id}/acknowledge` — подтвердить оповещение

## Task 26: Каналы push-уведомлений

### Files:
- Create: `service/plugin/ads-alert/channel/WebChannel.php`
- Create: `service/plugin/ads-alert/channel/EmailChannel.php`
- Create: `service/plugin/ads-alert/channel/SmsChannel.php`
- Create: `service/plugin/ads-alert/channel/WebhookChannel.php`
- Create: `service/plugin/ads-alert/service/NotificationService.php`

### NotificationService:
- `send(AlertLog, AlertRule): void` — отправка по всем настроенным каналам
- `pushToFrontend(AlertLog): void` — Redis pub/sub для push на дашборд в реальном времени

### Страницы Admin UI:
- `admin/src/views/alert/AlertRuleList.vue` — CRUD правил
- `admin/src/views/alert/AlertLogList.vue` — история оповещений с фильтрами
- `admin/src/components/AlertBadge.vue` — бейдж в навбаре с числом неподтверждённых

## Task 27: Планировщик оповещений + push в реальном времени

### Files:
- Modify: `service/plugin/ads-task/config/cron.php` — добавить AlertCheckTask каждые 5 минут
- Create: `service/plugin/ads-task/task/AlertCheckTask.php`
- Create: `service/plugin/ads-api/controller/AlertController.php`
- Modify: `service/plugin/ads-api/config/route.php` — добавить маршруты alert
- Create: `admin/src/stores/alert.ts` — опрос оповещений в реальном времени
- Modify: `admin/src/components/layout/TopBar.vue` — бейдж оповещений

### WebSocket (опционально в Phase 5):
- Канал Redis pub/sub для push оповещений в реальном времени
- Фронтенд подписывается через EventSource или опрос
