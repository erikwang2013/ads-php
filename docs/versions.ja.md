# バージョン比較

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| バージョン | ライセンス | 入手方法 |
|------|------|----------|
| **簡易版 (Lite)** | オープンソース (MIT) | GitHub 公開リポジトリ |
| **標準版 (Standard)** | 商用ライセンス | erik@erik.xyz に連絡 |
| **完全版 (Full)** | 商用ライセンス | erik@erik.xyz に連絡 |

---

## 機能比較

### 基本機能

| 機能 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 認証 (ログイン/Tokenリフレッシュ/現在のユーザー) | ✅ | ✅ | ✅ |
| プラットフォーム管理 (29 プラットフォームリスト + OAuth) | ✅ | ✅ | ✅ |
| アカウント管理 (CRUD + 同期) | ✅ | ✅ | ✅ |
| 広告プラン (CRUD + 開始・停止 + 一括) | ✅ | ✅ | ✅ |
| レポート (ダッシュボード + カスタム + エクスポート CSV/Excel/PDF) | ✅ | ✅ | ✅ |
| ヘルスチェック + API ドキュメント + 認証コード | ✅ | ✅ | ✅ |
| データ同期 (Campaign + Report) | ✅ | ✅ | ✅ |

### 配信管理

| 機能 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 広告グループ (CRUD + 開始・停止) | — | ✅ | ✅ |
| 広告クリエイティブ (リスト + 詳細) | — | ✅ | ✅ |
| 広告グループ/クリエイティブのデータ同期 | — | ✅ | ✅ |

### 監視と通知

| 機能 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| アラートルールエンジン (7 指標/4 条件/3 範囲) | — | ✅ | ✅ |
| アラート記録 + 確認 + 未読数 | — | ✅ | ✅ |
| 通知センター (リスト/既読/全既読) | — | ✅ | ✅ |

### 高度な機能

| 機能 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 自動入札ルールエンジン (3 アクション/クールダウン) | — | — | ✅ |
| オーディエンスターゲティングテンプレート (共通 JSON Schema) | — | — | ✅ |
| 広告素材ライブラリ (アップロード/ギャラリー/プレビュー) | — | — | ✅ |
| 予算警告 (3 段階アラート 50/80/100%) | — | — | ✅ |
| 配信カレンダー (Gantt 可視化) | — | — | ✅ |
| クロスプラットフォームアトリビューション (5 モデル/30 日遡及) | — | — | ✅ |

---

## セキュリティ防御比較

| 防御項目 | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| CORS ホワイトリスト | ✅ | ✅ | ✅ |
| セキュリティレスポンスヘッダー (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| バージョンルーティング (X-API-Version) | ✅ | ✅ | ✅ |
| インターフェースレート制限 (スライディングウィンドウ) | ✅ | ✅ | ✅ |
| SQL インジェクション検出 (パターンマッチ) | ✅ | ✅ | ✅ |
| 入力フィルタ (strip_tags + trim) | ✅ | ✅ | ✅ |
| 転送暗号化/復号 (X-Encrypted) | ✅ | ✅ | ✅ |
| JWT Bearer 認証 | ✅ | ✅ | ✅ |
| XSS 攻撃検出 (11 パターン) | — | ✅ | ✅ |
| パストラバーサル検出 (7 パターン) | — | ✅ | ✅ |
| Header インジェクション検出 | — | ✅ | ✅ |
| Body サイズ制限 (10 MiB) | — | ✅ | ✅ |
| Content-Type ホワイトリスト | — | ✅ | ✅ |
| クライアント由来の識別 (8 端) | — | ✅ | ✅ |
| ログインスロットル (5回→15分) | — | ✅ | ✅ |
| レスポンス時間監視 (X-Response-Time) | — | ✅ | ✅ |
| Origin/Referer 検証 | — | — | ✅ |
| リプレイ攻撃対策 (Nonce+Timestamp) | — | — | ✅ |
| 同時セッション制限 (最大3つ) | — | — | ✅ |
| CSRF Token (Admin側) | — | — | ✅ |
| SSRF 対策 (OAuth ホワイトリスト) | — | — | ✅ |
| ログデータ難読化 | — | — | ✅ |
| JWT IP/UA バインド | — | — | ✅ |

---

## ミドルウェアチェーン比較

### Service 側

| Lite (7 層) | Standard (11 層) | Full (15 層) |
|-------------|-----------------|-------------|
| CorsMiddleware | CorsMiddleware | CorsMiddleware |
| — | — | OriginGuardMiddleware |
| SecurityHeadersMiddleware | SecurityHeadersMiddleware | SecurityHeadersMiddleware |
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | ReplayGuardMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |
| RateLimitMiddleware | RateLimitMiddleware | RateLimitMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | — | SessionLimitMiddleware |
| SqlGuardMiddleware | SqlGuardMiddleware | SqlGuardMiddleware |
| ValidationMiddleware | ValidationMiddleware | ValidationMiddleware |
| — | ResponseTimeMiddleware | ResponseTimeMiddleware |
| EncryptionMiddleware | EncryptionMiddleware | EncryptionMiddleware |

### Admin 側

| Lite (1 層) | Standard (4 層) | Full (5 層) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## 定期タスク比較

| タスク | 頻度 | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55min | ✅ | ✅ | ✅ |
| DataSyncTask | 10min | ✅ (Campaign+Report のみ) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5min | — | ✅ | ✅ |
| BidCheckTask | 10min | — | — | ✅ |
| BudgetCheckTask | 15min | — | — | ✅ |

---

## データベーステーブル比較

| 分類 | テーブル名 | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| 基盤 | ads_tenants | ✅ | ✅ | ✅ |
| アカウント | ads_platform_accounts | ✅ | ✅ | ✅ |
| | ads_auth_tokens | ✅ | ✅ | ✅ |
| 配信 | ads_campaigns | ✅ | ✅ | ✅ |
| | ads_report_metrics | ✅ | ✅ | ✅ |
| | ads_report_extras | ✅ | ✅ | ✅ |
| | ads_ad_groups | — | ✅ | ✅ |
| | ads_creatives | — | ✅ | ✅ |
| アラート | ads_alert_rules | — | ✅ | ✅ |
| | ads_alert_logs | — | ✅ | ✅ |
| 通知 | ads_notifications | — | ✅ | ✅ |
| 入札 | ads_bid_rules | — | — | ✅ |
| | ads_bid_logs | — | — | ✅ |
| ターゲティング | ads_targeting_templates | — | — | ✅ |
| 素材 | ads_assets | — | — | ✅ |
| CDN | ads_cdn_providers | — | — | ✅ |
| アトリビューション | ads_conversions | — | — | ✅ |
| | ads_attribution_results | — | — | ✅ |
| システム | ads_sync_errors | ✅ | ✅ | ✅ |
| 管理 | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **合計** | | **8** | **13** | **19** |

---

## フロントエンドページ比較

### Vue Admin SPA

| ページ | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| ログイン | ✅ | ✅ | ✅ |
| ダッシュボード | ✅ | ✅ | ✅ |
| アカウントリスト + バインド | ✅ | ✅ | ✅ |
| 広告プラン | ✅ | ✅ | ✅ |
| レポートエクスポート | ✅ | ✅ | ✅ |
| ユーザー管理 | ✅ | ✅ | ✅ |
| 監査ログ | ✅ | ✅ | ✅ |
| 広告グループ | — | ✅ | ✅ |
| 広告クリエイティブ | — | ✅ | ✅ |
| レポート分析 (ECharts) | — | ✅ | ✅ |
| アラートルール | — | ✅ | ✅ |
| アラート記録 | — | ✅ | ✅ |
| 通知センター | — | ✅ | ✅ |
| 自動入札 | — | — | ✅ |
| 素材ライブラリ | — | — | ✅ |
| CDN プロバイダー | — | — | ✅ |
| 配信カレンダー | — | — | ✅ |
| アトリビューション分析 | — | — | ✅ |
| **合計** | **7** | **13** | **18** |

### Flutter

| ページ | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| ログイン | ✅ | ✅ | ✅ |
| ダッシュボード | ✅ | ✅ | ✅ |
| 広告プラン (リスト+詳細) | ✅ | ✅ | ✅ |
| データレポート | ✅ | ✅ | ✅ |
| プラットフォームアカウント | ✅ | ✅ | ✅ |
| アラート管理 | ✅ | ✅ | ✅ |
| 広告グループ | — | ✅ | ✅ |
| 広告クリエイティブ | — | ✅ | ✅ |
| レポート分析 | — | ✅ | ✅ |
| 通知センター | — | ✅ | ✅ |
| 自動入札 | — | — | ✅ |
| **合計** | **6** | **10** | **11** |

---

## API エンドポイント比較

| モジュール | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| システム (health/ping/docs/captcha) | 6 | 6 | 6 |
| 認証 (login/me/refresh) | 3 | 3 | 3 |
| プラットフォーム (list/oauthUrl/callback) | 3 | 3 | 3 |
| アカウント (index/show/destroy/sync) | 4 | 4 | 4 |
| 広告プラン (CRUD/toggle/batch) | 6 | 6 | 6 |
| 広告グループ (CRUD/toggle) | — | 5 | 5 |
| クリエイティブ (index/show) | — | 2 | 2 |
| レポート (summary/custom/export×2) | 4 | 4 | 4 |
| レポート (calendar/budget/attribution/models) | — | — | 4 |
| アラート (rules CRUD + logs + acknowledge + unread) | — | 7 | 7 |
| 通知 (index/unread/read/readAll) | — | 4 | 4 |
| 自動入札 (CRUD + logs) | — | — | 5 |
| ターゲティングテンプレート (CRUD) | — | — | 5 |
| 素材ライブラリ (index/upload/show/destroy/presign/register) | — | — | 6 |
| CDN プロバイダー (list/create/update/delete/default/toggle/test/purge) | — | — | 8 |
| **合計** | **26** | **44** | **70** |

---

## 技術スタック

3 つのバージョンは統一技術スタックを共有：

| 層 | 技術 |
|----|------|
| バックエンドフレームワーク | webman v2, PHP 8.2+ |
| データベース | MySQL 8.0 (InnoDB, utf8mb4) |
| キャッシュ | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| 認証 | erikwang2013/jwt-webman |
| ID 生成 | erikwang2013/snowflake-php |
| ID エンコード | erikwang2013/hashids |
| フロントエンド | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| デプロイ | Docker + Nginx + Docker Compose |

---

## アップグレードパス

```
Lite (オープンソース)
  │
  ├─→ Standard へアップグレード (erik@erik.xyz に連絡)
  │     │
  │     └─→ 追加: 広告グループ/クリエイティブ管理、アラートエンジン、通知センター、
  │              AttackGuard/XSS/パストラバーサル/ログインスロットル/レスポンス時間監視
  │
  └─→ Full へアップグレード (erik@erik.xyz に連絡)
        │
        └─→ 追加: Standard の全機能 + 自動入札、ターゲティングテンプレート、素材ライブラリ、
                  予算警告、配信カレンダー、クロスプラットフォームアトリビューション、リプレイ対策/同時制限/CSRF/SSRF
```
