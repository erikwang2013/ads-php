# Fase 5: Plan de Implementación — Sistema de Alertas y Notificaciones

[中文](docs/superpowers/plans/2026-05-15-phase5-alert-system.md) | [English](docs/superpowers/plans/2026-05-15-phase5-alert-system.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase5-alert-system.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase5-alert-system.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase5-alert-system.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase5-alert-system.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase5-alert-system.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase5-alert-system.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase5-alert-system.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase5-alert-system.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase5-alert-system.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase5-alert-system.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase5-alert-system.ja.md)

**Objetivo:** Implementar alertas de monitorización de datos publicitarios, con reglas personalizables (gasto excesivo/ROI demasiado bajo/descenso brusco de conversiones) y envío por múltiples canales.

## Tarea 25: Modelo de datos de alertas + motor de reglas

### Archivos:
- Crear: `service/plugin/ads-alert/config/plugin.php`
- Crear: `service/plugin/ads-alert/model/AlertRule.php`
- Crear: `service/plugin/ads-alert/model/AlertLog.php`
- Crear: `service/plugin/ads-alert/service/AlertEngine.php`
- Crear: `service/plugin/ads-alert/migration/create_alerts.sql`

### Esquema de BD:
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

### Servicio AlertEngine:
- `evaluate(AlertRule, int $tenantId): ?AlertLog` — ejecuta una regla contra los datos actuales
- `evaluateAll(): array` — ejecuta todas las reglas habilitadas
- `checkMetric(string $metric, array $scope, string $condition, float $threshold): bool`
- `buildQuery(string $metric, array $scope): QueryBuilder` — construye la consulta SQL para obtener el valor actual de la métrica
- `checkPctChange(string $metric, array $scope, float $threshold): bool` — compara hoy vs ayer
- `notify(AlertLog, AlertRule): void` — despacha a los canales configurados

### Endpoints API:
- `GET /api/v1/alerts/rules` — listar reglas
- `POST /api/v1/alerts/rules` — crear regla
- `PUT /api/v1/alerts/rules/{id}` — actualizar regla
- `DELETE /api/v1/alerts/rules/{id}` — eliminar regla
- `GET /api/v1/alerts/logs` — listar historial de alertas
- `POST /api/v1/alerts/logs/{id}/acknowledge` — confirmar alerta

## Tarea 26: Canales de envío de alertas

### Archivos:
- Crear: `service/plugin/ads-alert/channel/WebChannel.php`
- Crear: `service/plugin/ads-alert/channel/EmailChannel.php`
- Crear: `service/plugin/ads-alert/channel/SmsChannel.php`
- Crear: `service/plugin/ads-alert/channel/WebhookChannel.php`
- Crear: `service/plugin/ads-alert/service/NotificationService.php`

### NotificationService:
- `send(AlertLog, AlertRule): void` — envía por todos los canales configurados
- `pushToFrontend(AlertLog): void` — Redis pub/sub para push en tiempo real al dashboard

### Páginas de UI de Admin:
- `admin/src/views/alert/AlertRuleList.vue` — CRUD de reglas
- `admin/src/views/alert/AlertLogList.vue` — historial de alertas con filtros
- `admin/src/components/AlertBadge.vue` — insignia en la barra de navegación con el conteo de no confirmadas

## Tarea 27: Programación de alertas + push en tiempo real

### Archivos:
- Modificar: `service/plugin/ads-task/config/cron.php` — añadir AlertCheckTask cada 5 min
- Crear: `service/plugin/ads-task/task/AlertCheckTask.php`
- Crear: `service/plugin/ads-api/controller/AlertController.php`
- Modificar: `service/plugin/ads-api/config/route.php` — añadir rutas de alertas
- Crear: `admin/src/stores/alert.ts` — sondeo de alertas en tiempo real
- Modificar: `admin/src/components/layout/TopBar.vue` — insignia de alertas

### WebSocket (opcional Fase 5):
- Canal Redis pub/sub para push de alertas en tiempo real
- El frontend se suscribe mediante EventSource o sondeo
