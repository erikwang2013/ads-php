# Phase 5: Sistem Notifikasi Peringatan Implementation Plan

[中文](docs/superpowers/plans/2026-05-15-phase5-alert-system.md) | [English](docs/superpowers/plans/2026-05-15-phase5-alert-system.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase5-alert-system.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase5-alert-system.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase5-alert-system.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase5-alert-system.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase5-alert-system.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase5-alert-system.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase5-alert-system.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase5-alert-system.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase5-alert-system.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase5-alert-system.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase5-alert-system.ja.md)

**Tujuan:** Mengimplementasikan pemantauan dan peringatan data iklan, mendukung aturan kustom (pengeluaran melebihi batas/ROI terlalu rendah/konversi turun drastis), dan mengirimkan melalui berbagai kanal.

## Task 25: Model Data Peringatan + Mesin Aturan

### Files:
- Create: `service/plugin/ads-alert/config/plugin.php`
- Create: `service/plugin/ads-alert/model/AlertRule.php`
- Create: `service/plugin/ads-alert/model/AlertLog.php`
- Create: `service/plugin/ads-alert/service/AlertEngine.php`
- Create: `service/plugin/ads-alert/migration/create_alerts.sql`

### Skema DB:
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

### Service AlertEngine:
- `evaluate(AlertRule, int $tenantId): ?AlertLog` — menjalankan satu aturan terhadap data saat ini
- `evaluateAll(): array` — menjalankan semua aturan yang diaktifkan
- `checkMetric(string $metric, array $scope, string $condition, float $threshold): bool`
- `buildQuery(string $metric, array $scope): QueryBuilder` — membangun query SQL untuk mendapatkan nilai metrik saat ini
- `checkPctChange(string $metric, array $scope, float $threshold): bool` — membandingkan hari ini vs kemarin
- `notify(AlertLog, AlertRule): void` — mengirim ke kanal yang dikonfigurasi

### Endpoint API:
- `GET /api/v1/alerts/rules` — daftar aturan
- `POST /api/v1/alerts/rules` — buat aturan
- `PUT /api/v1/alerts/rules/{id}` — perbarui aturan
- `DELETE /api/v1/alerts/rules/{id}` — hapus aturan
- `GET /api/v1/alerts/logs` — daftar riwayat peringatan
- `POST /api/v1/alerts/logs/{id}/acknowledge` — akui peringatan

## Task 26: Kanal Notifikasi Peringatan

### Files:
- Create: `service/plugin/ads-alert/channel/WebChannel.php`
- Create: `service/plugin/ads-alert/channel/EmailChannel.php`
- Create: `service/plugin/ads-alert/channel/SmsChannel.php`
- Create: `service/plugin/ads-alert/channel/WebhookChannel.php`
- Create: `service/plugin/ads-alert/service/NotificationService.php`

### NotificationService:
- `send(AlertLog, AlertRule): void` — mengirim melalui semua kanal yang dikonfigurasi
- `pushToFrontend(AlertLog): void` — Redis pub/sub untuk dorongan real-time ke dasbor

### Halaman UI Admin:
- `admin/src/views/alert/AlertRuleList.vue` — CRUD aturan
- `admin/src/views/alert/AlertLogList.vue` — riwayat peringatan dengan filter
- `admin/src/components/AlertBadge.vue` — badge di nav bar menampilkan jumlah yang belum diakui

## Task 27: Penjadwalan Peringatan + Dorongan Real-time

### Files:
- Modify: `service/plugin/ads-task/config/cron.php` — tambahkan AlertCheckTask setiap 5 menit
- Create: `service/plugin/ads-task/task/AlertCheckTask.php`
- Create: `service/plugin/ads-api/controller/AlertController.php`
- Modify: `service/plugin/ads-api/config/route.php` — tambahkan rute peringatan
- Create: `admin/src/stores/alert.ts` — polling peringatan real-time
- Modify: `admin/src/components/layout/TopBar.vue` — badge peringatan

### WebSocket (opsional Phase 5):
- Kanal Redis pub/sub untuk dorongan peringatan real-time
- Frontend berlangganan melalui EventSource atau polling
