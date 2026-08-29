# アーキテクチャ設計ドキュメント

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. システム概要

マルチプラットフォーム広告管理システム。**29 の広告プラットフォーム**に連携し、配信管理、クロスプラットフォームレポート、アラート監視、自動入札、オーディエンスターゲティングをカバー。SaaS マルチテナント、代理運用、自社利用の 3 モードに対応。

---

## 2. デプロイアーキテクチャ

```
                         ┌──────────────────────────┐
                         │  クライアント             │
                         │  Vue Admin / Flutter      │
                         │  HarmonyOS / Browser      │
                         └──────────┬───────────────┘
                                    │ HTTP + JWT
                                    v
                         ┌──────────────────────────┐
                         │   Nginx :80               │
                         │   /          → admin:8789 │
                         │   /api       → service:8788│
                         └──────┬──────────┬────────┘
                                │          │
                   ┌────────────┘          └────────────┐
                   v                                    v
         ┌─────────────────┐                ┌─────────────────┐
         │  Admin :8789     │  ServiceProxy  │  Service :8788  │
         │  webman-admin v2 │───────────────→│  webman v2      │
         │  Vue 3 SPA       │   cURL HTTP    │  7 プラグイン   │
         └────────┬────────┘                └────────┬────────┘
                  │                                   │
                  └──────────────┬────────────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              v                  v                  v
        ┌──────────┐      ┌──────────┐      ┌───────────┐
        │ MySQL 8.0│      │ Redis 7  │      │    ES     │
        │ 18 テーブル│      │ キャッシュ/キュー│      │ 検索インデックス│
        └──────────┘      └──────────┘      └───────────┘
```

---

## 3. リクエスト処理パイプライン

### 3.1 Service 側 (15 層ミドルウェア)

```
Request
  → CorsMiddleware            (CORS ホワイトリスト、OPTIONS プリフライト)
  → OriginGuardMiddleware     (Origin/Referer 検証 + TRACE/DEBUG/CONNECT インターセプト)
  → SecurityHeadersMiddleware (CSP/X-Frame-Options/X-Content-Type-Options/HSTS)
  → AttackGuardMiddleware     (XSS/パストラバーサル/Headerインジェクション/Body 10MiB/Content-Typeホワイトリスト)
  → ClientPlatformMiddleware  (X-Client-Platform 8 端由来の識別)
  → ReplayGuardMiddleware     (Nonce+Timestamp リプレイ対策、非ブラウザー側は強検証)
  → VersionMiddleware         (X-API-Version バージョンルーティング)
  → RateLimitMiddleware       (Redis スライディングウィンドウ 60回/60s)
  → LoginThrottleMiddleware   (ログインスロットル 5回失敗→15分ロック)
  → SessionLimitMiddleware    (同時セッション制限 最大3つの有効Token)
  → SqlGuardMiddleware        (SQL インジェクションパターン検出)
  → ValidationMiddleware      (入力 trim + strip_tags)
  → ResponseTimeMiddleware    (X-Response-Time ヘッダー + 遅延リクエストログ)
  → EncryptionMiddleware      (X-Encrypted リクエスト復号/レスポンス暗号化)
  → AuthMiddleware            (JWT Bearer Token + IP/UA バインド)
  → Controller
```

### 3.2 Admin 側 (6 層ミドルウェア)

```
Request
  → AttackGuardMiddleware     (XSS/パストラバーサル/Headerインジェクション/Body制限/Content-Type)
  → LoginThrottleMiddleware   (ログインスロットル 5回失敗→15分)
  → ClientPlatformMiddleware  (X-Client-Platform 由来の識別)
  → CsrfMiddleware            (CSRF Token 検証)
  → VersionMiddleware         (API バージョン)
  → AuthCheck                 (Session + JWT デュアルチャネル)
  → Controller
```

---

## 4. ディレクトリ構成

```
ads-php/
├── service/                               # 業務 API サービス :8788
│   ├── config/                            # グローバル設定
│   │   ├── app.php, database.php, redis.php
│   │   ├── log.php                        # Monolog (JSON/Line デュアルモード)
│   │   ├── middleware.php                 # 11 層グローバルミドルウェア
│   │   ├── exception.php                  # API 例外ハンドラー
│   │   └── scout.php                      # ES 設定
│   ├── support/                           # 共有ユーティリティクラス (erik\support)
│   │   ├── ApiResponse.php                # 統一 JSON レスポンス
│   │   ├── ControllerTrait.php            # コントローラー共通 trait
│   │   ├── JwtService.php                 # JWT ラッパー (erikwang2013/jwt-webman)
│   │   ├── CacheService.php               # Redis キャッシュ
│   │   ├── HashidsService.php             # ID 暗号化/復号
│   │   ├── SnowflakeTrait.php             # Snowflake ID 生成
│   │   └── ExceptionHandler.php           # JSON 例外レンダリング
│   ├── plugin/
│   │   ├── ads-api/                       # REST API 層
│   │   │   ├── controller/v1/             # 14 コントローラー
│   │   │   ├── middleware/                # 7 ミドルウェア
│   │   │   ├── config/route.php           # 45+ ルート
│   │   │   └── route_helpers.php          # versioned() バージョンルーティング
│   │   ├── ads-platform/                  # プラットフォームアダプターコア
│   │   │   ├── adapter/                   # 29 プラットフォームアダプター
│   │   │   ├── src/                       # AdapterRegistry, CampaignData
│   │   │   ├── model/                     # Campaign, BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/                   # BidEngine
│   │   │   └── migration/                # SQL DDL + パフォーマンスインデックス
│   │   ├── ads-account/                   # OAuth アカウント + プラットフォームアカウント
│   │   ├── ads-task/                      # 5 の cron タスク
│   │   ├── ads-alert/                     # アラートエンジン + 通知
│   │   ├── ads-report/                    # レポートエンジン (CSV/Excel/PDF)
│   │   ├── ads-tenant/                    # マルチテナント
│   │   └── ads-storage/                   # ストレージ抽象化 (local/OSS/COS/S3) + CDN プロバイダー
│   ├── tests/                             # PHPUnit
│   │   ├── Unit/Middleware/               # ミドルウェアテスト
│   │   ├── Unit/Task/                     # タスクテスト (計画)
│   │   └── Integration/                   # コントローラー統合テスト
│   └── start.php                          # エントリ
├── admin/                                 # 管理バックエンド :8789
│   ├── app/
│   │   ├── controller/                    # Auth, AdminUser, AuditLog
│   │   ├── middleware/                    # AttackGuard, LoginThrottle, ClientPlatform, Csrf, Version, AuthCheck
│   │   ├── service/                       # AuditService, ServiceProxy
│   │   └── support/                       # HashidsService
│   ├── public/web/                        # Vue 3 + TS SPA
│   │   └── src/
│   │       ├── views/                     # 14 ページ (dashboard/campaign/adgroup/creative/report/alert/notification/bid/system)
│   │       ├── api/                       # 9 の API クライアント
│   │       ├── stores/                    # 4 の Pinia Store
│   │       └── components/                # ListPageLayout などの共有コンポーネント
│   └── config/                            # Admin 設定
├── apps/
│   ├── flutter/                           # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/                  # 12 機能ページ + Shell レイアウト
│   │       ├── config/menu_config.dart    # 2 段階メニュー + パンくず
│   │       ├── router.dart                # GoRouter + ShellRoute + ルートガード
│   │       ├── stores/auth_provider.dart  # Riverpod Auth
│   │       └── shared/api/api_client.dart # Dio + JWT + プラットフォーム検出
│   └── harmonyos/                         # HarmonyOS (API Client 準備完了)
├── docker/                                # Nginx 設定 + Dockerfiles
├── .github/workflows/                     # CI (構文→テスト→TS→Docker) + CD (ビルド・プッシュ)
└── docs/                                  # 設計ドキュメント
```

---

## 5. データモデル

### 5.1 テーブル分類

| 分類 | テーブル名 | 主キー | 用途 |
|------|------|------|------|
| 基盤 | `ads_tenants` | BIGINT Snowflake | マルチテナント |
| アカウント | `ads_platform_accounts`, `ads_auth_tokens` | BIGINT Snowflake | OAuth プラットフォームアカウント |
| 配信階層 | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | BIGINT Snowflake | 広告配信 |
| レポート | `ads_report_metrics`, `ads_report_extras` | BIGINT Snowflake | 統一指標 |
| アラート | `ads_alert_rules`, `ads_alert_logs` | BIGINT Snowflake | 監視アラート |
| 入札 | `ads_bid_rules`, `ads_bid_logs` | BIGINT Snowflake | 自動入札 |
| ターゲティング | `ads_targeting_templates` | BIGINT Snowflake | オーディエンステンプレート |
| 素材 | `ads_assets` | BIGINT Snowflake | クリエイティブ素材ライブラリ |
| CDN | `ads_cdn_providers` | BIGINT Snowflake | CDN プロバイダー設定 (フィールド単位暗号化の認証情報) |
| 通知 | `ads_notifications` | BIGINT Snowflake | サイト内通知 |
| アトリビューション | `ads_conversions`, `ads_attribution_results` | BIGINT Snowflake | コンバージョントラッキング + アトリビューション |
| システム | `ads_sync_errors` | BIGINT Snowflake | 同期エラー |
| 管理 | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + 監査 |

### 5.2 命名規則

- テーブルプレフィックス: `ads_`
- 主キー: `BIGINT UNSIGNED PRIMARY KEY` (オートインクリメントなし, Snowflake ID)
- エンジン: InnoDB, 文字セット: utf8mb4
- タイムスタンプ: `created_at`, `updated_at` (DATETIME)

---

## 6. セキュリティアーキテクチャ

### 6.1 防御レイヤー

| 層 | 仕組み | カバー範囲 |
|----|------|----------|
| 転送 | Nginx (SSL 終端) | 全量 |
| ネットワーク | CORS ホワイトリスト + Origin 検証 + HSTS | Service |
| 入力 | AttackGuard (XSS 11パターン/パストラバーサル 7パターン/Headerインジェクション) | Service + Admin |
| インジェクション | SQLGuard (SQL インジェクションパターン検出) | Service |
| サニタイズ | ValidationMiddleware (strip_tags) | Service |
| 認証 | JWT Bearer + bcrypt + IP/UA バインド + refresh ローテーション | Service |
| 認証 | Session + JWT デュアルチャネル + CSRF Token | Admin |
| 認可 | RBAC (ロール + 権限 JSON) | Admin |
| スロットル | RateLimit (スライディングウィンドウ) + LoginThrottle (5回→15分) | Service + Admin |
| セッション | SessionLimit (最大3つの有効Token) + ブラックリスト | Service |
| 暗号化 | EncryptionMiddleware (転送) + Encryptable (保存) | Service |
| リプレイ | ReplayGuard (Nonce+Timestamp ±5min, 非ブラウザー側) | Service + クライアント |
| 弾力性 | CircuitBreaker (プラットフォーム別: 5回失敗→OPEN→30秒ハーフオープン) + GuardedAdapter (降級 fast-fail) | Service |
| 監査 | 操作トレース (IP/UA/プラットフォーム) | Admin |
| 難読化 | ログの機密フィールド遮蔽 (password/token/secret → ***) | Service |

### 6.2 クライアントプラットフォーム識別

`X-Client-Platform` ヘッダー経由:

| 値 | 由来 |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | HarmonyOS App |

---

## 7. API バージョンルーティングの仕組み

バージョン番号は URL パスに出現しません。バージョンは `X-API-Version` ヘッダーで渡され、`VersionMiddleware` が読み取って `$request->apiVersion` に設定します。`versioned()` ヘルパー関数は実行時にコントローラークラス内のバージョンセグメントをリクエストバージョンに置き換えます。

```
リクエスト: GET /api/campaigns
Header: X-API-Version: v1

VersionMiddleware → $request->apiVersion = 'v1'
versioned(CampaignController::class, 'index')
  → controller\v1\CampaignController::index()
```

---

## 8. 定期タスクスケジューリング

| タスク | Cron | 機能 |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | 期限切れ OAuth Token をリフレッシュ |
| DataSyncTask | `*/10 * * * *` | Campaigns→AdGroups→Creatives→Reports→キャッシュクリアを同期 |
| AlertCheckTask | `*/5 * * * *` | アラートルールを評価し、通知をトリガー |
| BidCheckTask | `*/10 * * * *` | 入札ルールを評価し、予算調整/開始・停止を実行 |
| RetrySyncTask | `*/3 * * * *` | 失敗した同期を再試行（最大 3 回、指数バックオフ） |

---

## 9. Erik Stack パッケージ統合

| パッケージ | 統合位置 | 用途 |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10 の Model (SnowflakeTrait) + admin helpers.php | 主キー生成 |
| `erikwang2013/hashids` | ApiResponse + 2 の Admin Controller | ID エンコード |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | 認証トークン |
| `erikwang2013/encryption` | EncryptionMiddleware | 転送暗号化/復号 |
| `erikwang2013/encryptable` | PlatformAccount + AuthToken Model | DB フィールド暗号化 |
| `erikwang2013/webman-scout` | Campaign Model (Searchable trait) | ES 検索 |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | 国旗 |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | スライダー認証コード |
| `hg/apidoc` | アノテーション → ドキュメント生成 (Web UI: :8788/apidoc) | API ドキュメント |

---

## 10. 高並行アーキテクチャ

### 10.1 データベース層

| 最適化 | 説明 |
|------|------|
| 読み書き分離 | マスター `shared`（書き込み）+ 読み取り専用レプリカ `read_replica`（レポート/分析クエリ） |
| 永続接続 | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent` で頻繁な TCP ハンドシェイクを回避 |
| 接続ウォームアップ | worker 起動時に `SELECT 1` を実行し、コネクションプール準備完了後にリクエスト受信 |

### 10.2 キャッシュ層

```
L1: プロセスメモリ配列 (< 1µs, 最速だが最も局所的)
L2: APCu 共有メモリ (< 100µs, プロセス間共有)
L3: Redis (< 1ms, サーバー間共有, 永続化)
```

### 10.3 メッセージキュー

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 非同期処理 (HTTP レスポンスをブロックしない)
```

4 チャネル: `sync` | `report` | `export` | `notification`

### 10.4 水平スケール

```
                    ┌──────────────────┐
                    │   Nginx :80      │
                    │ upstream service │
                    └────────┬─────────┘
                             │
              ┌──────────────┼──────────────┐
              v              v              v
        ┌──────────┐  ┌──────────┐  ┌──────────┐
        │ php:8788 │  │ php2:8788│  │ php3:8788│
        │ worker 1 │  │ worker 2 │  │ worker 3 │
        └──────────┘  └──────────┘  └──────────┘
              │              │              │
              └──────────────┼──────────────┘
                             v
                    ┌──────────────────┐
                    │   MySQL + Redis  │
                    └──────────────────┘
```

- **keepalive**: 32 長接続の再利用
- **failover**: `proxy_next_upstream` 自動フェイルオーバー、2 回再試行
- **レート制限**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 静的リソース CDN

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — js/css ファイルの事前圧縮
- 本番環境は CDN に接続 (CloudFront/Aliyun CDN)

### 10.6 素材 CDN 高速化

素材 URL の組み立て・キャッシュ・パージ戦略は [第 12 章 素材ストレージと CDN 高速化](#12-素材ストレージと-cdn-高速化) を参照。

---

## 11. デプロイと CI/CD

### Docker サービス

| サービス | ポート | イメージ |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP Syntax → PHPUnit → TypeScript → Docker Build
- **CD** (`.github/workflows/deploy.yml`): Docker Buildx → GHCR Push (service/admin/admin-php) → Deploy

---

## 12. 素材ストレージと CDN 高速化

### 12.1 ストレージ抽象層

`service/plugin/ads-storage/` は統一 `Storage` ファサード + `StorageDriver` インターフェース (put/delete/signedUrl/publicUrl/putFile/deleteUrl/purge) を提供し、driver ごとに実装を切り替えます:

| driver | 実装 | 用途 |
|--------|------|------|
| `local` | LocalStorage | デフォルト、ローカル `public/uploads/assets/` |
| `oss` | AlibabaOssStorage | 阿里云 OSS |
| `cos` | TencentCosStorage | 腾讯云 COS (S3 プロトコル) |
| `s3` | S3CompatibleStorage | S3 互換: AWS S3 / Cloudflare R2 / MinIO |

配信時は DB のデフォルトプロバイダー (管理画面で設定) を優先し、なければ env/local にフォールバックします。

### 12.2 CDN プロバイダー管理

新テーブル `ads_cdn_providers` (name/driver/bucket/region/endpoint/access_key/secret_key/cdn_domain/cdn_driver/cdn_token/enabled/is_default/status):

- 認証情報 (access_key/secret_key/cdn_token) は `Erikwang2013\Encryptable` でフィールド単位暗号化して保存。API レスポンスはマスク済みフィールドのみ
- 管理できるのはプラットフォームのマスターテナント (tenantId=1) のみ (AdminMiddleware)。`/api/admin/cdn/providers` の 8 エンドポイント: 一覧/作成/更新/削除/デフォルト設定/有効・無効切替/接続テスト/キャッシュパージ
- purge は現状 `aliyun` cdn_driver で実装済み (OpenAPI 署名)。cloudflare/cloudfront は今後拡張

### 12.3 URL 組み立て戦略

`ads_assets.url` は常に相対パス (`/uploads/assets/...`) を保存し、読み出し時にデフォルトプロバイダーの `cdn_domain` を先頭に付けて完全な HTTPS URL (`https://{cdn_domain}/{url}`) を返します。CDN 未設定時はそのまま返します。

### 12.4 キャッシュ戦略

| 種別 | 戦略 |
|------|------|
| 画像 | `immutable` 長期キャッシュ (ファイル名ランダム・URL 一意で安全) |
| 動画 | 短期キャッシュ + Range 対応 (分割再生) |

素材削除時はその URL を CDN キャッシュから自動パージします。

### 12.5 マルチテナントのパス分離

素材 key はテナント分離プレフィックスを持ち、tenant_id ごとにグループ化。異なるテナントの素材は互いに見えません。

### 12.6 事前署名ダイレクトアップロードとバックフィル

- `POST /api/assets/presign`: 事前署名アップロード URL を取得 (50 MiB 動画などクライアントがオブジェクトストレージへ直接アップロード)。`key` 形式: `Ymd/32hex.拡張子`
- `POST /api/assets/register`: 直接アップロード済み素材を登録。key 形式を厳格検証しパストラバーサルを防止
- `local` driver では presign 非対応 (オブジェクトストレージ署名機能なし)
- `service/scripts/backfill-assets.php`: 既存のローカル素材をオブジェクトストレージへコピー (`--dry-run` プレビュー)。`url` カラムは不変

### 12.7 オリジンパス

`service/config/static.php` で webman の静的ファイル配信を有効化。`/uploads/assets` は 8788 で HTTP 直接配信され、CDN のオリジンパスになります。
