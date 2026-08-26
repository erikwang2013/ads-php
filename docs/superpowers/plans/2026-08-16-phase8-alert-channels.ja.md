# Phase 8: アラート多渠道の着地 Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**Goal:** Phase 5 の残課題を補完——`NotificationService` の email/sms チャネルを echo スタブから実実装（SMTP メール + 汎用 Webhook）にアップグレードし、チャネル設定をサポートします。web チャネルと Redis pub/sub は実装済みのため変更しません。

**出典:** Phase 7 チーム監査の結論（researcher の計画対照：唯一明確な"一部完了"項目 = Phase 5 アラート多渠道、`ads-alert` に `channel/` ディレクトリがない）

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## 現状（確認済み）

| コンポーネント | ステータス |
|---|---|
| `NotificationService::send()` | `match ($channel)` で web/email/sms を振り分け；web は実で `erik_notifications` に書き込み、email/sms は echo スタブ |
| `AlertRule.channels` | JSON フィールド + Eloquent cast array、フロントエンドは既に `['web','email','sms']` を送信 |
| Admin AlertRuleList.vue | チャネル選択 UI は既存（web はロック、email/sms は選択可能） |
| Redis pub/sub | `alert:new` チャネルへのプッシュは実装済み |
| SMTP/メール設定 | なし（service/config に mail 設定がない） |

## Task 1: メールチャネル（SMTP）

### Files:
- Create: `service/config/mail.php`（smtp host/port/user/pass/from/from_name/encryption、env 駆動）
- Create: `service/plugin/ads-alert/service/channel/EmailChannel.php`（send(AlertLog, AlertRule) を実装）
- Modify: `service/plugin/ads-alert/service/NotificationService.php`（email 分岐で EmailChannel を呼び出し、echo スタブを除去）
- Modify: `service/composer.json`（PHPMailer を選ぶ場合は依存を追加；優先して依存なしの `mail()`/socket 実装を検討し軽量を維持、実装者が評価）

### 設計ポイント
- 受信者：AlertRule 設定またはテナント設定から読み取り（ない場合は `email` フィールドまたは設定デフォルト）
- 件名/本文：sendWeb の文面テンプレートを再利用（"告警触发: {rule.name}" + 指標/現在値/条件/閾値）
- 失敗処理：例外を捕捉してログ記録、他チャネルとメインフローに影響させない
- 設定欠落時はグレースフルに降格（log で通知、例外で中断しない）

## Task 2: Webhook チャネル

### Files:
- Create: `service/plugin/ads-alert/service/channel/WebhookChannel.php`（設定 URL に JSON を POST）
- Modify: `NotificationService::send()` の match に `'webhook'` 分岐を追加

### 設計ポイント
- 設定ソース：AlertRule に `webhook_url` フィールドを拡張（migration）または channels 設定；最小変更のため、優先して AlertRule に `webhook_url` カラム（NULL 可）を追加
- ペイロード：`{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`、アラートレベル/指標/値/閾値/時間を含む
- タイムアウトとリトライ：接続タイムアウト 5s、合計タイムアウト 10s、失敗はログ記録（リトライなし、シンプルを維持）
- セキュリティ：http/https のみ許可、内網アドレス検証なし（SSRF リスクは既知の制限として記録、または非内網を検証——実装者が評価し記録）

## Task 3: ショートメッセージチャネル（ゲートウェイ占位）

### Files:
- Modify: `NotificationService::sendSms`（占位を維持し、接続ポイントを明確にコメント；実装者が軽量案を評価して着地可能な場合は着地）

### 設計ポイント
- ショートメッセージゲートウェイ（阿里云/腾讯云）は AK/SK と課金が必要、本フェーズは占位実装を維持し、コメントで接続手順を明記
- フロントエンド UI の sms オプションは選択可能のまま、バックエンドはログのみ記録（ゲートウェイ未設定であることをユーザーに明確に通知）

## Task 4: チャネル設定とフロントエンド

### Files:
- Modify: `admin/public/web/src/views/alert/AlertRuleList.vue`（webhook オプションと URL 入力を追加する場合）
- Modify: `service/plugin/ads-api/controller/v1/AlertController.php`（ルール作成/更新で webhook_url を受け付ける）
- Modify: `service/plugin/ads-alert/model/AlertRule.php`（fillable/casts に webhook_url を追加）
- Modify: `service/plugin/ads-alert/migration/create_alerts.sql`（ALTER または増分スクリプトの説明）

### 受入
- [ ] email チャネル：SMTP 設定後にアラートが発火するとメールを受信；未設定時はグレースフル降格
- [ ] webhook チャネル：アラート発火時に設定 URL へ JSON を POST、ペイロードフィールドが完全
- [ ] sms チャネル：占位を維持、ログ記録
- [ ] web チャネルと Redis pub/sub の回帰は影響なし
- [ ] Admin ルールフォームで新チャネルフィールドを設定可能
- [ ] `php vendor/bin/phpunit --no-coverage` 全通過
- [ ] 新規/更新テスト：AlertEngine/NotificationService のチャネル振り分けテスト
