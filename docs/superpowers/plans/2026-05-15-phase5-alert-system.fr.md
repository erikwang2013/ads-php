# Phase 5 : Système de push d'alertes — Plan d'implémentation

[中文](docs/superpowers/plans/2026-05-15-phase5-alert-system.md) | [English](docs/superpowers/plans/2026-05-15-phase5-alert-system.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase5-alert-system.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase5-alert-system.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase5-alert-system.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase5-alert-system.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase5-alert-system.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase5-alert-system.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase5-alert-system.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase5-alert-system.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase5-alert-system.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase5-alert-system.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase5-alert-system.ja.md)

**Objectif :** Implémenter la surveillance et les alertes sur les données publicitaires, avec des règles personnalisées (dépassement de dépense / ROI trop faible / chute brutale des conversions), poussées via plusieurs canaux.

## Task 25 : Modèle de données d'alertes + moteur de règles

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

### Service AlertEngine :
- `evaluate(AlertRule, int $tenantId): ?AlertLog` — exécute une seule règle sur les données courantes
- `evaluateAll(): array` — exécute toutes les règles activées
- `checkMetric(string $metric, array $scope, string $condition, float $threshold): bool`
- `buildQuery(string $metric, array $scope): QueryBuilder` — construit la requête SQL pour obtenir la valeur courante de la métrique
- `checkPctChange(string $metric, array $scope, float $threshold): bool` — compare aujourd'hui vs hier
- `notify(AlertLog, AlertRule): void` — distribue vers les canaux configurés

### Points d'API :
- `GET /api/v1/alerts/rules` — lister les règles
- `POST /api/v1/alerts/rules` — créer une règle
- `PUT /api/v1/alerts/rules/{id}` — mettre à jour une règle
- `DELETE /api/v1/alerts/rules/{id}` — supprimer une règle
- `GET /api/v1/alerts/logs` — lister l'historique des alertes
- `POST /api/v1/alerts/logs/{id}/acknowledge` — accuser réception d'une alerte

## Task 26 : Canaux de push d'alertes

### Files:
- Create: `service/plugin/ads-alert/channel/WebChannel.php`
- Create: `service/plugin/ads-alert/channel/EmailChannel.php`
- Create: `service/plugin/ads-alert/channel/SmsChannel.php`
- Create: `service/plugin/ads-alert/channel/WebhookChannel.php`
- Create: `service/plugin/ads-alert/service/NotificationService.php`

### NotificationService:
- `send(AlertLog, AlertRule): void` — envoie via tous les canaux configurés
- `pushToFrontend(AlertLog): void` — Redis pub/sub pour le push temps réel sur le tableau de bord

### Pages UI Admin :
- `admin/src/views/alert/AlertRuleList.vue` — CRUD des règles
- `admin/src/views/alert/AlertLogList.vue` — historique des alertes avec filtres
- `admin/src/components/AlertBadge.vue` — badge de la barre de navigation affichant le nombre d'alertes non accusées

## Task 27 : Planification des alertes + push temps réel

### Files:
- Modify: `service/plugin/ads-task/config/cron.php` — ajouter AlertCheckTask toutes les 5 min
- Create: `service/plugin/ads-task/task/AlertCheckTask.php`
- Create: `service/plugin/ads-api/controller/AlertController.php`
- Modify: `service/plugin/ads-api/config/route.php` — ajouter les routes d'alertes
- Create: `admin/src/stores/alert.ts` — interrogation temps réel des alertes
- Modify: `admin/src/components/layout/TopBar.vue` — badge d'alertes

### WebSocket (Phase 5 optionnelle) :
- Canal Redis pub/sub pour le push temps réel des alertes
- Le front-end s'abonne via EventSource ou une interrogation périodique
