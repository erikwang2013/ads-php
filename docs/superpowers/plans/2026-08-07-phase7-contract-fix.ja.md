# Phase 7: クロスエンド契約修正 Implementation Plan

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **ステータス更新（2026-08-16）：** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ すべて完了、tester 回帰検証通過（35 tests OK、契約クロスチェックでゴーストエンドポイントなし、Phase 7 は受入可能）。

**Goal:** チーム監査で検出されたクロスエンド API 契約の問題を修正：Flutter 3 つのゴーストエンドポイント（404）、Admin `admin.ts` の二重プレフィックス bug、`/system/info` のルートなし、ServiceProxy の未配線、ドキュメント口径の陳腐化。三端（Admin/Flutter/HarmonyOS）による service API の一貫した消費を復旧します。

**出典:** 2026-08-07 チーム並行監査（backend-dev ルート棚卸し 61 エンドポイント、vue-dev Admin 呼び出し棚卸し 50 呼び出し箇所、mobile-dev モバイル端棚卸し、researcher 実装済み/計画済み棚卸しのクロス比較）

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1: Flutter ゴーストエンドポイントの修正（🔴 最優先）

### 背景
Flutter の 3 ページが service に存在しないルートを呼び出しており、すべて 404：

| Flutter 呼び出し | service の実際のルート | 修正方針 |
|---|---|---|
| `GET /dashboard` | なし（ダッシュボード集計は `/reports/summary`） | `GET /reports/summary` に変更 |
| `GET /alerts` | なし（アラートは `/alerts/rules`、`/alerts/logs`、`/alerts/unread-count`） | `GET /alerts/logs` に変更（アラート一覧の意味） |
| `GET /reports` | なし（レポートは `/reports/summary`、`/reports/custom`） | `GET /reports/custom` に変更（日付/次元/指標パラメータ付き、ReportBuilder::buildCustom と一致） |

### Files:
- Modify: `apps/flutter/lib/features/dashboard/dashboard_page.dart`（`/dashboard` → `/reports/summary` ×2 箇所、レスポンス構造 `data.overview`/`by_platform`/`daily` に適応）✅
- Modify: `apps/flutter/lib/features/alert/alert_page.dart`（`/alerts` → `/alerts/logs`、ページング構造 `data.list` に適応、AlertLog フィールド rule_name/metric/current_value/condition/threshold）✅
- Modify: `apps/flutter/lib/features/report/report_page.dart`（`/reports` → `/reports/custom`、パラメータ date_start/date_end/dimensions[]/metrics[]、`data.list` を解析、フィールド cost）✅
- Verify: レスポンスフィールドが `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` の実際の返却と一致 ✅

### 受入
- [x] 3 箇所のパス修正完了、クエリパラメータ保持（report ページの日付パラメータ → date_start/date_end + dimensions/metrics）✅
- [x] レスポンス解析がバックエンドの実際の JSON 構造に整合（overview / paginated list / custom list）✅
- [x] 修正後 `flutter analyze` でエラーなし — 本環境の Flutter SDK キャッシュは読み取り専用で実行不可のため、SDK 内蔵 `dart analyze` を全プロジェクトに実行し **0 errors**（既存 15 警告はすべて修正前から存在、今回新たな問題は導入していない）✅

---

## Task 2: Admin `admin.ts` の二重プレフィックス bug 修正

### 背景
- `admin/public/web/src/api/admin.ts` のパスが `/api/admin/...` なのに対し、axios baseURL は既に `/api`（`src/api/index.ts`）で、実際には `/api/api/admin/...` になり、UserManage.vue / AuditLog.vue の 5 つの呼び出しがほぼ確実に 404。
- **深層アーキテクチャ問題（vue-dev 最終レポートで確認）**：admin バックエンド（8789）は自前で 12 本のローカルルートを提供（`/api/admin/login`、`me`、`logout`、`users` CRUD、`roles`、`audit-logs`、`/api/install/*`）が：
  - `docker/nginx/admin.conf` の `location /api/` は **すべて** `service_api`（php:8788）へ proxy_pass；
  - `upstream admin_backend`（admin-php:8789）は定義されているものの、**どの location も参照していない** → 本番環境で `/api/admin/*` は永遠に 8789 へ届かない；
  - Vite dev プロキシも `/api` をすべて 8788 へ向けている。
  - 結論：二重プレフィックスを直しても `/api/admin/*` は 404——admin バックエンドのローカルルートは本番経路で未配線。

### 決定ポイント（backend-dev + vue-dev + devops の確認が必要）
- 案 A（推奨）：vue-dev が `admin.ts` のパスを相対 `/admin/users`、`/admin/audit-logs` に変更し、同時に **devops が Nginx に `location /api/admin/` → `proxy_pass http://admin_backend` を追加**（`location /api/` の前に配置、前方一致が優先）、admin 専用ルートは 8789 が直接処理し、業務ルートは引き続き 8788
- 案 B：backend-dev が service に `/api/admin/*` ルートを追加（Admin 端の責務と重複、非推奨）
- 案 C：業務クエリも ServiceProxy 経由に変更（配線が必要、変更量最大、admin 端の統一認証が必要な場合のみ検討）

### Files:
- Modify: `admin/public/web/src/api/admin.ts`（`/api` プレフィックスを除去）
- Modify: `docker/nginx/admin.conf`（`location /api/admin/` → admin_backend upstream を追加）
- Modify: `admin/public/web/vite.config.ts`（dev プロキシに `/api/admin` → 8789 ルールを追加、`/api` より前に配置）
- Verify: `admin/config/route.php` の admin バックエンドルート（/api/admin/users 等）とフロントエンド呼び出しが一致

### 受入
- [x] フロントエンドのリクエストパスが実際に存在するバックエンドルートと一致（404 なし）— admin.ts の 9 メソッドすべて route.php と照合 ✅、vue-tsc 通過
- [x] Nginx / Vite とも `/api/admin/*` を 8789 へ、`/api/*` の残りを 8788 へ正しく振り分け — Nginx に `location /api/admin/` 追加、Vite に `/api/admin` プロキシ追加（`/api` の前）✅
- [x] UserManage / AuditLog ページが利用可能 — パス整合済み（listRoles → `/admin/users/roles` の決定を含む）✅

---

## Task 3: `/system/info` ルートなし + ServiceProxy 決定

### 背景
- `SystemInfo.vue` / `stores/admin.ts` が `GET /api/system/info` を呼ぶが、service にこのルートはない（/health、/ping のみ）、404 は try/catch で握りつぶされる
- `admin/app/controller/ServiceProxy.php` は定義済みだがリポジトリ全体でアクティブな呼び出し元は 0（"定義済み・未配線"）

### 決定ポイント
- `/system/info`：案 A — フロントエンドを `/health` 呼び出しに変更（service に既存）；案 B — backend-dev が service に `/api/system/info` エンドポイントを追加（バージョン/環境情報を返却、HarmonyOS/Flutter にも有用、推奨）
- ServiceProxy：案 A — admin が必要とする admin 専用 API（監査ログ転送など）に配線；案 B — クラスを削除し、"Admin は service に直接接続"とドキュメント更新（現在の実際のアーキテクチャ）

### 実行済み（2026-08-16）
- **`/system/info` → 案 A（フロントエンドを `/health` に変更）**：SystemInfo.vue をネイティブ axios で `GET /health` 呼び出しに変更、`checks.database === 'ok'` で判定；`/health` ルートは service 側で `/api` プレフィックスなし、Vite に `/health` プロキシ追加済み、Nginx の既存 `location /health` はそのまま；`stores/admin.ts` のデッドコードも `/health` に同期変更 ✅
- **ServiceProxy → 案 B（保留 + ドキュメント説明）**：クラスは予備インフラとして維持（`ServiceProxy::init()` の自己初期化は無害）、`admin/config/app.php` のコメントを"予備インフラ、現在アクティブな呼び出し元なし"に更新 ✅

### 受入
- [x] `/system/info` の決定が着地：フロントエンドは呼び出しを除去（/health に変更）、404 ゴーストリクエストなし ✅
- [x] ServiceProxy の決定が着地：クラスを維持し config コメントで現状を説明 ✅

---

## Task 4: ドキュメントのバックフィルと口径統一

### 背景
- README の"14 控制器 / 45+ 端点"が陳腐化（実際は 17 控制器 / 61 端点）
- `docs/superpowers/plans/` 各 phase の checkbox が未バックフィル（コードは実装済みだがドキュメントは未チェック）
- HarmonyOS のステータス"UI 规划中"が陳腐化（実際は 6 ページ + ApiClient が準備完了）
- install.html / InstallController のデフォルト `.../api/v1` と config デフォルト `/api`（X-API-Version ヘッダー）が不一致
- CacheService のコメントは二段キャッシュと記載、実際は三段（L1 メモリ / APCu / Redis）

### Files:
- Modify: `README.md` / `README.en.md`（控制器数、端点数、HarmonyOS ステータス、キャッシュ階層）
- Modify: `admin/public/install.html` / `admin/app/controller/InstallController.php`（バージョンプレフィックス口径を統一）
- Modify: `service/support/CacheService.php`（コメント訂正）
- Optional: `docs/superpowers/plans/*.md` の checkbox をバックフィル

### 実行済み（2026-08-16）
- README.md / README.en.md：17 控制器 / 61 端点 / HarmonyOS 6 ページ / 19 Vue ページ / SPA 直接接続の口径をすべて更新 ✅
- install.html / InstallController：`/api/v1` デフォルト値 → `/api`（X-API-Version ヘッダー機構）✅
- 8 份の phase plan checkbox をすべてバックフィル ✅（phase7 を除く、実行待ち）

### 受入
- [x] README のデータがコードと一致（17 控制器 / 61 端点 / HarmonyOS 6 ページ）✅
- [x] インストールウィザードのバージョンプレフィックスが X-API-Version 機構と一致 ✅

---

## 次フェーズ計画（Phase 8-10、本計画外）

| Phase | 内容 | ステータス |
|---|---|---|
| Phase 8 | アラート多渠道の着地：ads-alert に channel/ を追加（Email SMTP、Webhook、SMS ゲートウェイ占位）—— Phase 5 の残課題を補完 | 未着手 |
| Phase 9 | HarmonyOS 実機連携：6 ページを ApiClient に接続（現在 0 実呼び出し、全モックデータ） | 未着手 |
| Phase 10 | 深化と商業化：29 プラットフォーム実機連携、同期状態の可視化、コンバージョンデータのループ、Flutter/HarmonyOS CI パッケージング、マルチテナント SaaS クォータ | 未着手 |
