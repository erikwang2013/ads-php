# Phase 5: Implementierungsplan für das Alarm-Push-System

[中文](docs/superpowers/plans/2026-05-15-phase5-alert-system.md) | [English](docs/superpowers/plans/2026-05-15-phase5-alert-system.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase5-alert-system.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase5-alert-system.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase5-alert-system.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase5-alert-system.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase5-alert-system.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase5-alert-system.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase5-alert-system.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase5-alert-system.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase5-alert-system.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase5-alert-system.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase5-alert-system.ja.md)

**Ziel:** Überwachung und Alarmierung der Werbedaten implementieren, mit benutzerdefinierten Regeln (Ausgaben über Limit/ROI zu niedrig/Conversion-Einbruch) und Push über mehrere Kanäle.

## Task 25: Alarm-Datenmodell + Regel-Engine

### Dateien:
- Erstellen: `service/plugin/ads-alert/config/plugin.php`
- Erstellen: `service/plugin/ads-alert/model/AlertRule.php`
- Erstellen: `service/plugin/ads-alert/model/AlertLog.php`
- Erstellen: `service/plugin/ads-alert/service/AlertEngine.php`
- Erstellen: `service/plugin/ads-alert/migration/create_alerts.sql`

### DB-Schema:
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

### AlertEngine-Service:
- `evaluate(AlertRule, int $tenantId): ?AlertLog` — führt eine einzelne Regel gegen aktuelle Daten aus
- `evaluateAll(): array` — führt alle aktivierten Regeln aus
- `checkMetric(string $metric, array $scope, string $condition, float $threshold): bool`
- `buildQuery(string $metric, array $scope): QueryBuilder` — baut die SQL-Abfrage für den aktuellen Metrikwert
- `checkPctChange(string $metric, array $scope, float $threshold): bool` — vergleicht heute mit gestern
- `notify(AlertLog, AlertRule): void` — leitet an die konfigurierten Kanäle weiter

### API-Endpunkte:
- `GET /api/v1/alerts/rules` — Regeln auflisten
- `POST /api/v1/alerts/rules` — Regel erstellen
- `PUT /api/v1/alerts/rules/{id}` — Regel aktualisieren
- `DELETE /api/v1/alerts/rules/{id}` — Regel löschen
- `GET /api/v1/alerts/logs` — Alarmhistorie auflisten
- `POST /api/v1/alerts/logs/{id}/acknowledge` — Alarm bestätigen

## Task 26: Alarm-Push-Kanäle

### Dateien:
- Erstellen: `service/plugin/ads-alert/channel/WebChannel.php`
- Erstellen: `service/plugin/ads-alert/channel/EmailChannel.php`
- Erstellen: `service/plugin/ads-alert/channel/SmsChannel.php`
- Erstellen: `service/plugin/ads-alert/channel/WebhookChannel.php`
- Erstellen: `service/plugin/ads-alert/service/NotificationService.php`

### NotificationService:
- `send(AlertLog, AlertRule): void` — sendet über alle konfigurierten Kanäle
- `pushToFrontend(AlertLog): void` — Redis pub/sub für Echtzeit-Push ins Dashboard

### Admin-UI-Seiten:
- `admin/src/views/alert/AlertRuleList.vue` — CRUD für Regeln
- `admin/src/views/alert/AlertLogList.vue` — Alarmhistorie mit Filtern
- `admin/src/components/AlertBadge.vue` — Badge in der Navigationsleiste mit Anzahl unbestätigter Alarme

## Task 27: Alarm-Planung + Echtzeit-Push

### Dateien:
- Ändern: `service/plugin/ads-task/config/cron.php` — AlertCheckTask alle 5 Minuten hinzufügen
- Erstellen: `service/plugin/ads-task/task/AlertCheckTask.php`
- Erstellen: `service/plugin/ads-api/controller/AlertController.php`
- Ändern: `service/plugin/ads-api/config/route.php` — Alarm-Routen hinzufügen
- Erstellen: `admin/src/stores/alert.ts` — Echtzeit-Alarm-Polling
- Ändern: `admin/src/components/layout/TopBar.vue` — Alarm-Badge

### WebSocket (optional Phase 5):
- Redis-pub/sub-Kanal für Echtzeit-Alarm-Push
- Frontend abonniert via EventSource oder Polling
