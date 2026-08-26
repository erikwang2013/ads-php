# المرحلة 5: خطة تنفيذ نظام دفع التنبيهات

[中文](docs/superpowers/plans/2026-05-15-phase5-alert-system.md) | [English](docs/superpowers/plans/2026-05-15-phase5-alert-system.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase5-alert-system.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase5-alert-system.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase5-alert-system.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase5-alert-system.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase5-alert-system.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase5-alert-system.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase5-alert-system.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase5-alert-system.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase5-alert-system.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase5-alert-system.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase5-alert-system.ja.md)

**الهدف:** تنفيذ مراقبة بيانات الإعلانات وتنبيهاتها، مع دعم قواعد مخصصة (تجاوز الإنفاق / انخفاض ROI / انهيار التحويلات)، والدفع عبر قنوات متعددة.

## المهمة 25: نموذج بيانات التنبيهات + محرك القواعد

### الملفات:
- إنشاء: `service/plugin/ads-alert/config/plugin.php`
- إنشاء: `service/plugin/ads-alert/model/AlertRule.php`
- إنشاء: `service/plugin/ads-alert/model/AlertLog.php`
- إنشاء: `service/plugin/ads-alert/service/AlertEngine.php`
- إنشاء: `service/plugin/ads-alert/migration/create_alerts.sql`

### مخطط قاعدة البيانات:
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

### خدمة AlertEngine:
- `evaluate(AlertRule, int $tenantId): ?AlertLog` — تشغيل قاعدة واحدة على البيانات الحالية
- `evaluateAll(): array` — تشغيل جميع القواعد المفعلة
- `checkMetric(string $metric, array $scope, string $condition, float $threshold): bool`
- `buildQuery(string $metric, array $scope): QueryBuilder` — بناء استعلام SQL لجلب قيمة المقياس الحالية
- `checkPctChange(string $metric, array $scope, float $threshold): bool` — مقارنة اليوم بالأمس
- `notify(AlertLog, AlertRule): void` — الإرسال إلى القنوات المُهيأة

### نقاط نهاية API:
- `GET /api/v1/alerts/rules` — قائمة القواعد
- `POST /api/v1/alerts/rules` — إنشاء قاعدة
- `PUT /api/v1/alerts/rules/{id}` — تحديث قاعدة
- `DELETE /api/v1/alerts/rules/{id}` — حذف قاعدة
- `GET /api/v1/alerts/logs` — سجل التنبيهات
- `POST /api/v1/alerts/logs/{id}/acknowledge` — تأكيد التنبيه

## المهمة 26: قنوات دفع التنبيهات

### الملفات:
- إنشاء: `service/plugin/ads-alert/channel/WebChannel.php`
- إنشاء: `service/plugin/ads-alert/channel/EmailChannel.php`
- إنشاء: `service/plugin/ads-alert/channel/SmsChannel.php`
- إنشاء: `service/plugin/ads-alert/channel/WebhookChannel.php`
- إنشاء: `service/plugin/ads-alert/service/NotificationService.php`

### NotificationService:
- `send(AlertLog, AlertRule): void` — الإرسال عبر جميع القنوات المُهيأة
- `pushToFrontend(AlertLog): void` — Redis pub/sub للدفع الفوري للوحة التحكم

### صفحات واجهة الإدارة:
- `admin/src/views/alert/AlertRuleList.vue` — CRUD للقواعد
- `admin/src/views/alert/AlertLogList.vue` — سجل التنبيهات مع عوامل تصفية
- `admin/src/components/AlertBadge.vue` — شارة شريط التنقل تعرض عدد غير المؤكد

## المهمة 27: جدولة التنبيهات + الدفع الفوري

### الملفات:
- تعديل: `service/plugin/ads-task/config/cron.php` — إضافة AlertCheckTask كل 5 دقائق
- إنشاء: `service/plugin/ads-task/task/AlertCheckTask.php`
- إنشاء: `service/plugin/ads-api/controller/AlertController.php`
- تعديل: `service/plugin/ads-api/config/route.php` — إضافة مسارات التنبيهات
- إنشاء: `admin/src/stores/alert.ts` — استطلاع التنبيهات الفوري
- تعديل: `admin/src/components/layout/TopBar.vue` — شارة التنبيهات

### WebSocket (اختياري في المرحلة 5):
- قناة Redis pub/sub للدفع الفوري للتنبيهات
- تشترك الواجهة الأمامية عبر EventSource أو الاستطلاع
