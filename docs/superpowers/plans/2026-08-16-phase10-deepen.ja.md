# Phase 10: 深化と商業化 Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**Goal:** Phase 7-9 の契約とマルチチャネルの基盤の上に、同期状態の可視化、コンバージョンデータのループ、モバイル端 CI パッケージング、マルチテナント SaaS クォータの 4 つの深化能力を着地させます。

**出典:** Phase 7 チーム監査が推測した方向性（researcher：ES/読み書き分離/キューの着地、Flutter/鴻蒙 CI、29 プラットフォーム実機連携、SaaS 課金クォータ、コンバージョンデータループ、同期状態の可視化、AI 入札）

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## 現状（確認済み）

| 候補サブ項目 | 現状 |
|---|---|
| 同期状態の可視化 | `ads_sync_errors` テーブル + `RetrySyncTask`（リトライ 3 回、退避 5^n 分）は既存；**同期失敗率と遅延を表示するフロントエンドページ/API なし** |
| コンバージョンデータのループ | `ads_conversions` + `ads_attribution_results` テーブルは既存、帰因エンジン実装済み；**コンバージョンデータ収集の入口なし**（回送/埋点 API） |
| モバイル端 CI | `ci.yml` は PHP 構文→PHPUnit→vue-tsc→Docker のみ；**Flutter/HarmonyOS のビルドパッケージングなし** |
| マルチテナント SaaS | `ads_tenants` テーブル + TenantIdentify ミドルウェアは既存；**課金/クォータ/使用量統計なし** |
| ES の着地 | scout.php 設定済み + webman-scout 依存導入済み；**docker-compose に ES サービスなし** |
| 29 プラットフォーム実機連携 | 29 アダプターのコードは揃っている；**サンドボックス/資格情報の連携記録なし**（外部資格情報が必要、手動項目と明記） |

## Task 1: 同期状態の可視化

### Files:
- Modify: `service/plugin/ads-api/controller/v1/DashboardController.php` または新規 `service/plugin/ads-api/controller/v1/SyncController.php` + route
- Create: `admin/public/web/src/api/sync.ts`
- Create: `admin/public/web/src/views/sync/SyncStatus.vue`（またはシステムページに統合）

### 設計ポイント
- エンドポイント：`GET /api/sync/status`（アカウント次元：last_sync_at、成功率、今日の失敗数、pending リトライ数）+ `GET /api/sync/errors`（ページングエラー一覧、last_error/retry_count/next_retry_at 含む）
- フロントエンド：同期状態ページ（テーブル + サマリーカード）、Full/Standard バージョン線のみ
- データソース：ads_platform_accounts（last_sync_at）+ ads_sync_errors

## Task 2: コンバージョンデータ収集 API

### Files:
- Modify: `service/plugin/ads-api/controller/v1/`（新規 ConversionController + route）
- Create: `service/plugin/ads-report/service/ConversionService.php`

### 設計ポイント
- エンドポイント：`POST /api/conversions`（業務側のコンバージョン回送：platform/campaign_id/order_id/conversion_time/value/currency/channel）+ `GET /api/conversions`（照会）
- 検証：campaign_id の存在、金額が非負、時間形式；ads_conversions に書き込み
- 帰因連携：回送後に帰因再計算をトリガー可能（または既存の AttributionEngine の定期/手動再計算に任せる旨を説明）
- フロントエンド：帰因レポートページに"コンバージョン回送"の説明/デモを追加（任意）

## Task 3: モバイル端 CI パッケージング

### Files:
- Modify: `.github/workflows/ci.yml`（新規 job：Flutter build（web + linux または apk）+ HarmonyOS 静的検査）

### 設計ポイント
- Flutter：`flutter pub get && flutter analyze && flutter build web`（または apk、リポジトリ現状に応じてビルド可能なターゲットを選択；flutter 環境が制約される場合は dart analyze）
- HarmonyOS：標準の Linux CI ツールチェーンなし、静的検査の説明またはスキップ（注記）
- 既存の php-tests job と並行、メインフローをブロックしない

## Task 4: マルチテナント SaaS クォータ（MVP）

### Files:
- Modify: `service/plugin/ads-tenant/`（新規 QuotaService）
- Modify: `service/plugin/ads-api/config/route.php` + controller

### 設計ポイント
- データ：ads_tenants に quota フィールド追加、または新テーブル ads_tenant_quotas（plan/account_limit/campaign_limit/sync_quota）
- 検証ポイント：アカウント紐付け数、計画作成数、毎日同期回数（AccountController/CampaignController/DataSyncTask の入口で検査）
- エンドポイント：`GET /api/tenant/quota`（使用量 + クォータ）
- フロントエンド：システムページにクォータ使用量を表示（任意、MVP は API のみ可）
- バージョン線：quota デフォルト値は lite/standard/full で差異（config 定数）

## 受入（Task 別）
- [ ] Task 1：sync API エンドポイントが利用可能、フロントエンドページ表示、テストカバレッジ
- [ ] Task 2：conversions 回送 API が書き込み・照会可能、検証が有効、テストカバレッジ
- [ ] Task 3：CI の新規 job が通過（またはスキップ項目を明記）
- [ ] Task 4：quota API が正しく返却、超過遮断が有効、テストカバレッジ
- [ ] 全体：`php vendor/bin/phpunit --no-coverage` 全通過、vue-tsc 通過

## 本期の範囲外（外部リソースが必要）
- 29 プラットフォーム実機連携（各プラットフォームの資格情報/サンドボックスが必要）
- ES サービスの着地（docker-compose に ES サービスとインデックス初期化を追加する必要あり）
- AI 入札提案（モデル/データ準備）
