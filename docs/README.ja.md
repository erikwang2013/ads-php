# Ads Platform — マルチプラットフォーム広告管理システム

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## 概要

**29 の広告プラットフォーム**に連携し、広告配信とクロスプラットフォームのデータレポートを統合管理。アラート監視、自動入札、マルチエンドアクセスに対応。

> アーキテクチャ設計 → [docs/architecture.ja.md](docs/architecture.ja.md)  
> 機能モジュール → [docs/features.ja.md](docs/features.ja.md)  
> API ドキュメント → [docs/api.ja.md](docs/api.ja.md) | hg/apidoc: `http://127.0.0.1:8788/apidoc`  
> バージョン比較 → [docs/versions.ja.md](docs/versions.ja.md)（Lite オープンソース / Standard & Full は erik@erik.xyz まで連絡）

### 対応プラットフォーム

#### 国内 (16)
| プラットフォーム | アダプター | 認証 |
|------|--------|------|
| 巨量引擎 | Juliang | OAuth2 Access-Token |
| 百度营销 | Baidu | OAuth2 + 信封签名 |
| 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5 |
| 腾讯广告 | Tencent | OAuth2 + nonce |
| 快手磁力引擎 | Kuaishou | OAuth2 URLパラメータ |
| 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer |
| 微博粉丝通 | Weibo | OAuth2 Bearer |
| B站花火 | Bilibili | OAuth2 Bearer |
| 优酷广告 | Youku | OAuth2 + MD5 |
| 美团广告 | Meituan | OAuth2 Bearer |
| 知乎广告 | Zhihu | OAuth2 Bearer |
| 360推广 | Qihoo360 | API Key + Sign |
| 搜狗推广 | Sogou | API Key + Sign |
| 友盟 | Umeng | API Key + MD5 |
| 京东京准通 | Jingdong | OAuth2 + MD5 |
| 拼多多广告 | Pinduoduo | OAuth2 + カスタム Sign |

#### 海外 (13)
| プラットフォーム | アダプター | 認証 |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 URLパラメータ |
| TikTok Ads | Tiktok | OAuth2 Access-Token |
| LinkedIn Ads | Linkedin | OAuth2 Bearer |
| Snapchat Ads | Snapchat | OAuth2 Bearer |
| Pinterest Ads | Pinterest | OAuth2 Bearer |
| Twitter/X Ads | Twitter | OAuth2 Bearer |
| Amazon Ads | Amazon | OAuth2 + Profile |
| The Trade Desk | TheTradeDesk | HMAC-SHA256 |
| Spotify Ads | Spotify | OAuth2 Bearer |
| Twitch Ads | Twitch | OAuth2 Bearer + ClientId |
| Netflix Ads | Netflix | OAuth2 client_credentials |

---

## 技術スタック

| レイヤー | 技術 | 説明 |
|----|------|------|
| サーバー側 | webman v2 + PHP 8.2+ | 7 プラグイン、65+ API エンドポイント |
| データベース | MySQL 8.0 | 28 テーブル、erik_ プレフィックス、Snowflake BIGINT 主キー |
| キャッシュ | Redis 7 | 3 段キャッシュ (L1メモリ/L2 APCu/L3 Redis)、レート制限カウント、Pub/Sub、メッセージキュー |
| 検索 | Elasticsearch | webman-scout 自動インデックス同期（設定済み） |
| 管理バックエンド | webman-admin v2 + Vue 3 + TypeScript + Element Plus | PHP バックエンド(ポート 8789)、SPA は業務 API(ポート 8788)に直結、19 ページ、ECharts ビジュアライゼーション |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | PC/Mobile レスポンシブ、Desktop Shell レイアウト、12 ページ |
| HarmonyOS | ArkTS + ArkUI | 6 ページ実装済み、HTTP クライアント準備完了 |
| デプロイ | Docker + Nginx + GHCR | Docker Compose ワンクリック起動、GitHub Actions 自動ビルド・プッシュ |

## アーキテクチャ図

![システムアーキテクチャ図](docs/diagrams/svg/architecture.ja.svg)

### リクエストフロー図

![リクエストフロー図](docs/diagrams/svg/request-flow.ja.svg)

### 機能モジュール図

![機能モジュール図](docs/diagrams/svg/functional-modules.ja.svg)

### データライフサイクル図

![データライフサイクル図](docs/diagrams/svg/data-lifecycle.ja.svg)

> 全詳細注釈、Admin 側パイプライン、定期タスクのガントチャート、キャッシュ状態遷移図を含む完全版 → [docs/diagrams/](docs/diagrams/) |

> 詳細なアーキテクチャ説明、セキュリティアーキテクチャ、高並行設計は [アーキテクチャ設計ドキュメント](docs/architecture.ja.md) を参照 | 過去の設計仕様は [design.ja.md](docs/superpowers/specs/design.ja.md)

## アーキテクチャ説明

- **`service/`** — webman v2 ユーザー向け業務 API サービス。ポート **8788** で待ち受け。広告プラットフォーム連携、OAuth 認可、データ同期、レポートエンジン、アラート監視などの業務ロジックを処理。
- **`admin/`** — webman-admin v2 独立管理バックエンド。ポート **8789** で待ち受け。PHP バックエンド（認証・認可、ユーザー管理、システム設定）と Vue 3 SPA フロントエンドで構成。
- **管理バックエンドと業務サービスの通信** — Vue SPA は axios（baseURL `/api`）で service API に直結。admin 専用ルート（`/api/admin/*`）は admin PHP バックエンド（8789）が提供し、Nginx がパスで振り分け。
- **開発モード** — Vite dev server (ポート 5173) が `/api` を service:8788 にプロキシ。admin PHP バックエンドは 8789 で session 認証と SPA 静的ファイルを提供。
- **本番モード** — Nginx が `/` を admin:8789（管理バックエンド SPA）へ、`/api/` を service:8788（業務 API）へルーティング。

## Erik Stack 統合

| パッケージ | 用途 |
|----|------|
| `erikwang2013/snowflake-php` | 分散 Snowflake ID 生成 |
| `erikwang2013/hashids` | API ID パラメータの暗号化/復号 |
| `erikwang2013/jwt-webman` | JWT 認証トークン |
| `erikwang2013/encryption` | API 層の機密データ暗号化/復号 |
| `erikwang2013/encryptable` | DB フィールド単位の自動暗号化/復号 |
| `erikwang2013/webman-scout` | Elasticsearch データ同期 |
| `erikwang2013/season` | 国旗アイコン表示 |
| `erikwang2013/poster-php` | スライダー認証コード（ログイン保護） |
| `hg/apidoc` | API ドキュメント自動生成（アノテーション + Web UI） |

## 国際化

全インターフェースが **中文 (zh-CN)** / **English (en)** のバイリンガル切替に対応：

| 端 | 技術 | 切替方法 |
|----|------|---------|
| Admin | vue-i18n v9 | TopBar 言語ドロップダウンメニュー、localStorage で永続化 |
| Service API | `erik\support\I18n` | Accept-Language リクエストヘッダー / `?lang=` パラメータ |
| Flutter | AppLocalizations + Delegate | システム言語の自動検出 |
| HarmonyOS | StringResources | `setLang()` で切替 |

## セキュリティ

### Service 側 (14 層グローバル + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware（ルート層）

### Admin 側 (10 層グローバル + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck（ルート層）

### 防御機能一覧 (22 項目)

| 分類 | 防御項目 | 説明 |
|------|--------|------|
| 入力検知 | XSS (11パターン) | script/iframe/event handler/javascript:/data: |
| | パストラバーサル (7パターン) | ../ / null byte / /etc/passwd / .env / .git |
| | Header インジェクション | CRLF 検出 |
| | Body サイズ制限 | 10 MiB |
| | Content-Type ホワイトリスト | JSON/Form/Multipart/Plain |
| | SQL インジェクション | UNION/DROP/ALTER パターン検出 |
| 認証 | JWT Token バインド | IP + User-Agent hash 検証 |
| | Token リフレッシュ + ブラックリスト | 旧 Token を自動無効化 |
| | ログインスロットル | 5 回失敗 → 15 分ロック (Redis) |
| | 同時セッション制限 | ユーザーごとに最大 3 つの有効 Token |
| | 認証コード | スライダー認証コード (5分有効, 5px 許容差) |
| リクエスト検証 | CORS ホワイトリスト | 本番環境のドメインホワイトリスト |
| | Origin/Referer 検証 | クロスドメイン由来の検証 |
| | CSRF Token | Admin 側 session token 検証 |
| | リプレイ攻撃対策 | Nonce + Timestamp ±5min (非ブラウザー側) |
| | インターフェースレート制限 | スライディングウィンドウ 60回/60s |
| | SSRF 対策 | OAuth redirect_uri ホワイトリスト |
| レスポンスヘッダー | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | クリックジャッキング対策 + HTTPS 強制 |
| | X-Content-Type-Options | nosniff |
| データ保護 | 転送暗号化 | EncryptionMiddleware (X-Encrypted) |
| | 保存時暗号化 | Encryptable (DB フィールド単位) |
| | ログ難読化 | password/token/secret → \*\*\* |

### セキュリティアーキテクチャ図

![セキュリティアーキテクチャ図](docs/diagrams/svg/security.ja.svg)

**多層防御**：外部層（Nginx）→ 入口ガード（5 層ミドルウェア）→ 身元認証（7 項目）→ 入力検証（4 項目）→ 頻度制御 → データ暗号化 → 監査追跡

**認証**：サーバー側と admin は統一して `admin_users` テーブル + bcrypt ハッシュを使用。JWT 24h + refresh ローテーション

**監査**：すべての操作で IP / User-Agent / Client-Platform / 操作詳細を記録

**再確認**：削除/解除/一括操作は「確認語入力」方式（`GlobalConfirm` + `useConfirmStore`）

---

## 高度な機能

| 機能 | 説明 | 技術 |
|------|------|------|
| 素材ライブラリ | 画像/動画のアップロード管理、ギャラリープレビュー、URL コピー | AssetController + Vue ギャラリー |
| 予算警告 | 日予算消費のリアルタイム追跡、3 段階アラート (50/80/100%) | BudgetAlertService + 15min Cron |
| 配信カレンダー | クロスプラットフォーム Gantt 図、月/週ビュー、プラットフォーム別配色 | CalendarService + Vue Gantt |
| クロスプラットフォームアトリビューション | 5 モデルアトリビューション (first/last/linear/time_decay/position_based)、30 日遡及 | AttributionEngine + ECharts |

---

## 高並行処理

| 最適化 | 方式 | ファイル |
|------|------|------|
| DB リードライト分離 | マスター `shared` + 読み取り専用レプリカ `read_replica`、SELECT は自動的にレプリカへルーティング | `config/database.php` |
| DB コネクションプール | `PDO::ATTR_PERSISTENT` 永続接続 + タイムゾーン初期化のウォームアップ | `config/database.php` |
| Redis コネクションプール | `persistent` 永続接続 + 読み書き分離 `readonly` 設定 | `config/redis.php` |
| 3 段キャッシュ | L1 プロセスメモリ → L2 APCu 共有メモリ → L3 Redis | `support/CacheService.php` |
| メッセージキュー非同期 | Redis List 4 チャネル (sync/report/export/notification) | `support/AsyncJobService.php` |
| Nginx 段階的レート制限 | 30r/s + burst 20 + 20 同時接続 + keepalive 32 | `docker/nginx/admin.conf` |
| 水平スケール | upstream マルチインスタンス + フェイルオーバー + sticky session | `docker/nginx/admin.conf` |
| CDN 高速化 | 静的リソース `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## クイックスタート

### ワンクリック Web インストール（推奨）

サービス起動後、ブラウザで `/install` にアクセスしてインストールウィザードを開始：

```bash
# 管理バックエンドを起動 (ポート 8789)
cd admin && composer install && php start.php start

# ブラウザで http://localhost:8789/install にアクセス
# インストールウィザードでデータベース情報、管理者アカウントを入力し「インストール開始」をクリック
```

インストールウィザードが Web ページ上で以下の手順を案内します：
1. **データベース接続** — MySQL ホスト、ポート、データベース名、ユーザー名・パスワードを入力。接続テスト対応
2. **Redis 設定** — Redis 接続情報を入力（任意）
3. **管理者アカウント** — バックエンドのログインユーザー名、パスワード、表示名を設定
4. **ワンクリックインストール** — 自動でデータベースを作成し、`install.sql` を実行して 28 テーブルを作成、シードデータを投入、管理者パスワードを更新

インストール完了後、`/` にアクセスして管理バックエンドに入り、設定したユーザー名とパスワードでログインします。

### Docker (本番環境に推奨)

```bash
# 全サービスを起動 (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# データベース初期化（テーブル作成 + シードデータ）
make db-init

# アクセス
# 管理バックエンド: http://localhost
# インストールウィザード: http://localhost/install
# API: http://localhost/api（Header: X-API-Version: v1）
```

### ローカル開発

```bash
# サーバー側 (ポート 8788)
cd service && composer install && php start.php start

# 管理バックエンド (ポート 5173)
cd admin/public/web && npm install && npm run dev

# Flutter App
cd apps/flutter && flutter run -d chrome  # Web PC
# HarmonyOS App
# DevEco Studio で apps/harmonyos ディレクトリを開く
cd apps/flutter && flutter run -d android # Mobile

# TypeScript チェック
cd admin/public/web && npx vue-tsc --noEmit   # エラーゼロ
```

---

## プロジェクト構成

```
ads-php/
├── service/                           # ユーザー向け業務サービス (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (61 エンドポイント、バージョンルーティング)
│   │   │   ├── controller/v1/         # 17 コントローラー
│   │   │   ├── middleware/            # 15 ミドルウェア
│   │   │   ├── config/route.php       # ルート定義
│   │   │   └── route_helpers.php      # versioned() ヘルパー関数
│   │   ├── ads-platform/              # プラットフォームアダプターのコア
│   │   │   ├── adapter/               # 29 プラットフォームアダプター
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # SQL マイグレーション + パフォーマンスインデックス
│   │   ├── ads-account/               # OAuth アカウント管理
│   │   ├── ads-task/                  # 定期タスクスケジューリング (6 cron)
│   │   ├── ads-alert/                 # アラート監視エンジン + 予算警告
│   │   ├── ads-report/                # レポートエンジン (CSV/Excel/PDF) + アトリビューションエンジン + 配信カレンダー
│   │   └── ads-tenant/                # マルチテナント管理
│   ├── support/                       # Erik Stack ユーティリティクラス
│   │   ├── ControllerTrait.php        # コントローラー共通 trait
│   │   ├── JwtService.php             # JWT ラッパークラス
│   │   ├── CacheService.php           # Redis キャッシュサービス
│   │   ├── ExceptionHandler.php       # API 例外ハンドラー
│   │   └── ApiResponse.php            # 統一レスポンス形式
│   ├── config/                        # グローバル設定 (DB/Redis/Log/Middleware)
│   ├── tests/                         # PHPUnit テスト (244 tests)
│   │   ├── Unit/                      # ユニットテスト (Middleware, Task)
│   │   └── Integration/               # 統合テスト (Auth, Health)
│   └── start.php                      # サービスエントリ
├── admin/                             # 独立管理バックエンド (webman-admin v2 :8789)
│   ├── public/web/src/
│   │   ├── views/                     # 15 の Vue ページ
│   │   │   ├── dashboard/             # ダッシュボード (ECharts)
│   │   │   ├── campaign/              # 広告プラン
│   │   │   ├── adgroup/               # 広告グループ
│   │   │   ├── creative/              # 広告クリエイティブ
│   │   │   ├── report/                # レポート分析 + エクスポート
│   │   │   ├── alert/                 # アラートルール + 記録
│   │   │   ├── notification/          # 通知センター
│   │   │   ├── bid/                   # 自動入札ルール
│   │   │   └── system/                # ユーザー管理 + 監査ログ
│   │   ├── api/                       # 9 の API クライアント
│   │   ├── stores/                    # 4 の Pinia Store
│   │   └── components/                # 共有コンポーネント (ListPageLayout など)
│   ├── app/                           # PHP バックエンド (controller/middleware)
│   └── config/                        # Admin 設定
├── apps/
│   ├── flutter/                       # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/              # 12 の機能ページ + Shell レイアウト
│   │       ├── config/menu_config.dart # 2 段階メニュー設定
│   │       ├── router.dart            # GoRouter (ShellRoute + ルートガード)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API Client 準備完了)
├── docker/                            # Docker & Nginx 設定
├── .github/workflows/                 # CI (構文→テスト→TS→Docker) + CD (ビルド・プッシュ)
├── docs/                              # 設計ドキュメント、実装計画、Skills
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## API エンドポイント

> 全 API エンドポイント定義は [docs/api.ja.md](docs/api.ja.md) を参照（リクエスト/レスポンス例、エラーコード、レート制限ポリシーを含む）。
> hg/apidoc オンラインドキュメント: サービス起動後 `http://127.0.0.1:8788/apidoc` にアクセス

## データベース

**命名規則**: テーブルプレフィックス `erik_`、主キー `BIGINT UNSIGNED PRIMARY KEY`（オートインクリメントなし、Snowflake ID）、エンジン InnoDB、文字セット utf8mb4

| 分類 | テーブル名 | 用途 |
|------|------|------|
| 基盤 | `erik_tenants` | マルチテナント |
| アカウント | `erik_platform_accounts`, `erik_auth_tokens` | OAuth プラットフォームアカウント |
| 配信 | `erik_campaigns`, `erik_ad_groups`, `erik_creatives` | 広告配信階層 |
| レポート | `erik_report_metrics`, `erik_report_extras` | 統一レポート指標 |
| 素材 | `erik_assets` | クリエイティブ素材ライブラリ |
| ターゲティング | `erik_targeting_templates` | オーディエンスターゲティングテンプレート |
| アトリビューション | `erik_conversions`, `erik_attribution_results` | コンバージョントラッキング + アトリビューション結果 |
| 入札 | `erik_bid_rules`, `erik_bid_logs` | 自動入札ルール + 履歴 |
| アラート | `erik_alert_rules`, `erik_alert_logs` | アラート監視 |
| 通知 | `erik_notifications` | サイト内通知 |
| システム | `erik_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | 同期エラー、RBAC、監査 |

---

## 定期タスク

| タスク | 頻度 | 機能 |
|------|------|------|
| TokenRefreshTask | 55 分ごと | 期限切れ OAuth Token をスキャンし自動リフレッシュ |
| DataSyncTask | 10 分ごと | 各プラットフォームのプラン+広告グループ+クリエイティブ+レポートを取得し統一テーブルに書き込み、キャッシュをクリア |
| AlertCheckTask | 5 分ごと | 有効なアラートルールを走査し、しきい値を評価、プッシュをトリガー |
| BidCheckTask | 10 分ごと | 自動入札ルールを走査し、指標を照会、予算調整/開始・停止を実行 |
| BudgetCheckTask | 15 分ごと | 配信中プランを走査し、日予算消費を追跡、3 段階警告 (50/80/100%) |
| RetrySyncTask | 3 分ごと | 失敗した同期タスクを再試行（最大 3 回、指数バックオフ） |

---

## テスト

```bash
cd service && ./vendor/bin/phpunit
# 244 テスト / 654 アサーション
```

**カバー範囲**: ミドルウェア (Version/SQLGuard/SecurityHeaders) · データオブジェクト (CampaignData/FieldMapping/Hashids) · エンジン (ReportBuilder/AdapterRegistry) · 統合テスト (Auth/Health)

```bash
# TypeScript チェック
cd admin/public/web && npx vue-tsc --noEmit   # エラーゼロ

# Dart 分析
cd apps/flutter && dart analyze   # エラーゼロ
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): 自動パイプライン — **PHP Syntax → PHPUnit → TypeScript → Docker Build**

**CD** (`.github/workflows/deploy.yml`): 手動トリガー — **Docker Buildx → GHCR へプッシュ (service/admin/admin-php) → デプロイ通知**

`.github/dependabot.yml` が毎週 Composer + npm + Docker 依存関係を自動更新。

---

## Skills

`docs/skills/` — 11 の再利用可能なプロジェクトスキル：

| Skill | 説明 |
|------|------|
| `adapter-generator` | 新しい広告プラットフォームアダプターを生成（14 メソッドテンプレート） |
| `migration-generator` | SQL マイグレーションファイルを生成（erik_ プレフィックス + BIGINT PK） |
| `erik-stack` | Erik Stack 8 パッケージ統合ガイド |
| `admin-page-generator` | Vue3 管理バックエンドページを生成 |
| `api-endpoint` | RESTful API エンドポイントを追加 |
| `tdd-workflow` | TDD 検証フロー（テスト→実装→構文→TypeScript→コミット） |
| `security-middleware` | セキュリティミドルウェア層を追加（インターフェース仕様 + 登録 + 既存チェーン参照） |
| `version-split` | Lite/Standard/Full 3 バージョン分割（操作手順 + 設定更新） |
| `cache-strategy` | 3 段キャッシュ戦略（L1メモリ/L2 APCu/L3 Redis + TTL 推奨） |
| `attribution-setup` | クロスプラットフォームアトリビューションエンジン（5 モデル + API 呼び出し + データ準備） |
| `high-concurrency` | 高並行処理 8 項目の最適化（読み書き分離/コネクションプール/メッセージキュー/水平スケール/CDN） |


## オープンソース開発の継続のため、ご支援をお願いします

| WeChat | Alipay |
|:---:|:---:|
| ![微信](./docs/weixinpay.png "微信") | ![支付宝](./docs/alipay.png "支付宝") |

### 全球转账打赏 (Global Transfer Donation)

**收款人信息 (Beneficiary)**

| 字段 | 值 |
|------|-----|
| 收款人姓名 (Name) | WANG KEXUN |
| 收款账户号码 (Account No.) | 881015918251 |

**收款银行 (Receiving Bank) — ZA Bank**

| 字段 | 值 |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| 银行名称 (Bank Name) | ZA Bank Limited |
| 银行编号 (Bank Code) | 387 |
| 银行地址 (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **跨境汇款代理银行（如需，Correspondent Bank）**：此为代理（中转）银行信息，非收款银行信息，请向汇款银行查询是否需要提供。
>
> - **港元、人民币及美元**：Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · 银行编号 006 · Hong Kong Branch（分行编号 391）· Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **其他币种**：THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

---

## ライセンス

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
