# Phase 5: 告警推送系统 Implementation Plan

[中文](docs/superpowers/plans/2026-05-15-phase5-alert-system.md) | [English](docs/superpowers/plans/2026-05-15-phase5-alert-system.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase5-alert-system.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase5-alert-system.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase5-alert-system.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase5-alert-system.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase5-alert-system.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase5-alert-system.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase5-alert-system.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase5-alert-system.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase5-alert-system.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase5-alert-system.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase5-alert-system.ja.md)

**Goal:** 広告データ監視アラートを実装し、カスタムルール（費消超過/ROI 低下/コンバージョン急減）をサポートし、複数チャネルでプッシュします。

## Task 25: アラートデータモデル + ルールエンジン

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

### AlertEngine サービス:
- `evaluate(AlertRule, int $tenantId): ?AlertLog` — 単一ルールを現在のデータに対して実行
- `evaluateAll(): array` — 有効な全ルールを実行
- `checkMetric(string $metric, array $scope, string $condition, float $threshold): bool`
- `buildQuery(string $metric, array $scope): QueryBuilder` — 現在のメトリック値を取得する SQL クエリを構築
- `checkPctChange(string $metric, array $scope, float $threshold): bool` — 今日と昨日を比較
- `notify(AlertLog, AlertRule): void` — 設定済みチャネルに配信

### API endpoints:
- `GET /api/v1/alerts/rules` — ルール一覧
- `POST /api/v1/alerts/rules` — ルール作成
- `PUT /api/v1/alerts/rules/{id}` — ルール更新
- `DELETE /api/v1/alerts/rules/{id}` — ルール削除
- `GET /api/v1/alerts/logs` — アラート履歴一覧
- `POST /api/v1/alerts/logs/{id}/acknowledge` — アラート確認

## Task 26: アラートプッシュチャネル

### Files:
- Create: `service/plugin/ads-alert/channel/WebChannel.php`
- Create: `service/plugin/ads-alert/channel/EmailChannel.php`
- Create: `service/plugin/ads-alert/channel/SmsChannel.php`
- Create: `service/plugin/ads-alert/channel/WebhookChannel.php`
- Create: `service/plugin/ads-alert/service/NotificationService.php`

### NotificationService:
- `send(AlertLog, AlertRule): void` — 設定済みの全チャネルで送信
- `pushToFrontend(AlertLog): void` — リアルタイムダッシュボードプッシュ用の Redis pub/sub

### Admin UI ページ:
- `admin/src/views/alert/AlertRuleList.vue` — ルールの CRUD
- `admin/src/views/alert/AlertLogList.vue` — フィルタ付きアラート履歴
- `admin/src/components/AlertBadge.vue` — 未確認件数を表示するナビバーバッジ

## Task 27: アラートスケジューリング + リアルタイムプッシュ

### Files:
- Modify: `service/plugin/ads-task/config/cron.php` — 5 分ごとに AlertCheckTask を追加
- Create: `service/plugin/ads-task/task/AlertCheckTask.php`
- Create: `service/plugin/ads-api/controller/AlertController.php`
- Modify: `service/plugin/ads-api/config/route.php` — アラートルートを追加
- Create: `admin/src/stores/alert.ts` — リアルタイムアラートポーリング
- Modify: `admin/src/components/layout/TopBar.vue` — アラートバッジ

### WebSocket (任意の Phase 5):
- リアルタイムアラートプッシュ用の Redis pub/sub チャネル
- フロントエンドは EventSource またはポーリングで購読
