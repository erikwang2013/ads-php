# Phase 5: अलर्ट पुश सिस्टम Implementation Plan

[中文](docs/superpowers/plans/2026-05-15-phase5-alert-system.md) | [English](docs/superpowers/plans/2026-05-15-phase5-alert-system.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase5-alert-system.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase5-alert-system.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase5-alert-system.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase5-alert-system.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase5-alert-system.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase5-alert-system.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase5-alert-system.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase5-alert-system.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase5-alert-system.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase5-alert-system.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase5-alert-system.ja.md)

**लक्ष्य:** विज्ञापन डेटा मॉनिटरिंग अलर्ट लागू करें, कस्टम नियमों (खर्च सीमा से अधिक/ROI बहुत कम/रूपांतरण में अचानक गिरावट) का समर्थन करें, कई चैनलों के माध्यम से पुश करें।

## Task 25: अलर्ट डेटा मॉडल + नियम इंजन

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

### AlertEngine सेवा:
- `evaluate(AlertRule, int $tenantId): ?AlertLog` — वर्तमान डेटा के विरुद्ध एकल नियम चलाता है
- `evaluateAll(): array` — सभी सक्षम नियम चलाता है
- `checkMetric(string $metric, array $scope, string $condition, float $threshold): bool`
- `buildQuery(string $metric, array $scope): QueryBuilder` — वर्तमान मेट्रिक मान प्राप्त करने के लिए SQL क्वेरी बनाता है
- `checkPctChange(string $metric, array $scope, float $threshold): bool` — आज की तुलना कल से करता है
- `notify(AlertLog, AlertRule): void` — कॉन्फ़िगर किए गए चैनलों पर भेजता है

### API endpoints:
- `GET /api/v1/alerts/rules` — नियम सूची
- `POST /api/v1/alerts/rules` — नियम बनाएँ
- `PUT /api/v1/alerts/rules/{id}` — नियम अपडेट करें
- `DELETE /api/v1/alerts/rules/{id}` — नियम हटाएँ
- `GET /api/v1/alerts/logs` — अलर्ट इतिहास सूची
- `POST /api/v1/alerts/logs/{id}/acknowledge` — अलर्ट स्वीकार करें

## Task 26: अलर्ट पुश चैनल

### Files:
- Create: `service/plugin/ads-alert/channel/WebChannel.php`
- Create: `service/plugin/ads-alert/channel/EmailChannel.php`
- Create: `service/plugin/ads-alert/channel/SmsChannel.php`
- Create: `service/plugin/ads-alert/channel/WebhookChannel.php`
- Create: `service/plugin/ads-alert/service/NotificationService.php`

### NotificationService:
- `send(AlertLog, AlertRule): void` — सभी कॉन्फ़िगर किए गए चैनलों के माध्यम से भेजता है
- `pushToFrontend(AlertLog): void` — रीयल-टाइम डैशबोर्ड पुश के लिए Redis pub/sub

### Admin UI पेज:
- `admin/src/views/alert/AlertRuleList.vue` — नियमों का CRUD
- `admin/src/views/alert/AlertLogList.vue` — फ़िल्टर के साथ अलर्ट इतिहास
- `admin/src/components/AlertBadge.vue` — अनस्वीकृत गिनती दिखाने वाला नेव बार बैज

## Task 27: अलर्ट शेड्यूलिंग + रीयल-टाइम पुश

### Files:
- Modify: `service/plugin/ads-task/config/cron.php` — हर 5 मिनट में AlertCheckTask जोड़ें
- Create: `service/plugin/ads-task/task/AlertCheckTask.php`
- Create: `service/plugin/ads-api/controller/AlertController.php`
- Modify: `service/plugin/ads-api/config/route.php` — अलर्ट रूट जोड़ें
- Create: `admin/src/stores/alert.ts` — रीयल-टाइम अलर्ट पोलिंग
- Modify: `admin/src/components/layout/TopBar.vue` — अलर्ट बैज

### WebSocket (वैकल्पिक Phase 5):
- रीयल-टाइम अलर्ट पुश के लिए Redis pub/sub चैनल
- फ़्रंटएंड EventSource या पोलिंग के माध्यम से सब्सक्राइब करता है
