# API インターフェースドキュメント

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **hg/apidoc オンラインドキュメント**: サービス起動後 `http://127.0.0.1:8788/apidoc` にアクセス（Service + Admin デュアルアプリ切替）  
> 設定ファイル: `service/config/plugin/hg/apidoc/app.php`

---

## 共通仕様

### Base URL

```
http://your-domain.com/api
```

### 必須 Headers

| Header | 値 | 説明 |
|--------|----|------|
| `X-API-Version` | `v1` | API バージョン番号（必須、URL パスには出現しない） |
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | 操作の発生元（必須） |
| `Authorization` | `Bearer <token>` | JWT 認証トークン（ログイン/プラットフォームリスト/ヘルスチェック以外は必須） |

### リプレイ対策 Header（非ブラウザー側）

| Header | 説明 |
|--------|------|
| `X-Nonce` | ランダム文字列（リクエストごとに一意） |
| `X-Timestamp` | Unix 秒タイムスタンプ（±5 分ウィンドウ） |

### 任意 Headers

| Header | 説明 |
|--------|------|
| `X-Tenant-Id` | テナント ID（マルチテナントモード） |
| `X-Encrypted` | `1` = リクエストボディの復号が必要、レスポンスボディの暗号化が必要 |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| 値 | 説明 |
|----|------|
| `application/json` | JSON リクエストボディ（推奨） |
| `application/x-www-form-urlencoded` | フォームリクエスト |
| `multipart/form-data` | ファイルアップロード |

### レスポンス形式

**成功**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**ページネーション**:
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [ ... ],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

**エラー**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**ヘルスチェック**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### HTTP ステータスコード

| ステータスコード | 意味 |
|--------|------|
| 200 | 成功 |
| 204 | OPTIONS プリフライト成功 |
| 400 | リクエストパラメータエラー、未対応の API バージョン |
| 401 | 未認証、Token 期限切れ、Token の IP/UA 不一致 |
| 403 | アクセス禁止（XSS/パストラバーサル/CSRF/SQLインジェクション/Origin 不一致） |
| 404 | リソースが存在しない |
| 429 | リクエスト過多（レート制限/ログインスロットル/同時セッション制限） |
| 500 | サーバーエラー |
| 503 | サービス低下（DB または Redis が利用不可） |

### ページネーションパラメータ

| パラメータ | デフォルト値 | 最大値 | 説明 |
|------|--------|--------|------|
| `page` | 1 | — | ページ番号 |
| `per_page` | 20 | 100 | 1 ページあたりの件数（超過は自動的に切り捨て） |
| `sort` | `id` | — | ソートフィールド（ホワイトリスト内のみ） |

### キャッシュ戦略

| エンドポイント | TTL | 層 |
|------|-----|-----|
| `/api/platforms` | 1 時間 | L1 メモリ → L2 APCu → L3 Redis |
| `/api/accounts` + `/api/accounts/:id` | 5 分 | 同上 |
| `/api/reports/summary` | 5 分 | 同上 |
| `/api/alerts/rules` | 2 分 | 同上 |
| `/api/alerts/unread-count` | 30 秒 | 同上 |

---

## モジュール 1: システム

### GET /health — ヘルスチェック

```
GET /health
```

**レスポンス**:
```json
{
  "status": "healthy",
  "timestamp": "2026-05-22T00:00:00+08:00",
  "checks": {
    "database": "ok",
    "redis": "ok"
  }
}
```

- `status`: `healthy` (200) または `degraded` (503)
- 認証不要、バージョンルーティングは通らない

---

### GET /ping — 疎通確認

```
GET /ping
```

**レスポンス**: `{ "pong": true }`

---

### GET /docs — API ドキュメント

```
GET /docs
```

HTML 形式の API ドキュメントページを返す（認証不要）。

---

### GET /api/captcha/generate — 認証コード生成

認証不要。

**レスポンス**:
```json
{
  "code": 0,
  "data": {
    "captcha_token": "aes-encrypted-token",
    "background": "base64...",
    "puzzle": "base64..."
  }
}
```

- token の有効期限は 5 分
- オフセット許容差 5px

---

### POST /api/captcha/verify — 認証コード検証

認証不要。

**リクエスト**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**レスポンス**: `{ "code": 0, "message": "验证通过" }`

---

## モジュール 2: 認証

### POST /api/auth/login — ログイン

認証不要。

**リクエスト**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**レスポンス**:
```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "username": "admin",
      "name": "超级管理员",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

- JWT Token の有効期限は 24 時間
- Token に IP + User-Agent hash を内包
- 5 回失敗 → Redis で 15 分ロック

---

### GET /api/auth/me — 現在のユーザー

**リクエストヘッダー**: `Authorization: Bearer <token>`

**レスポンス**:
```json
{
  "code": 0,
  "data": {
    "id": 1,
    "username": "admin",
    "name": "超级管理员",
    "email": "admin@example.com",
    "role": "admin",
    "tenant_id": 1
  }
}
```

---

### POST /api/auth/refresh — Token リフレッシュ

**リクエストヘッダー**: `Authorization: Bearer <old_token>`

**レスポンス**:
```json
{
  "code": 0,
  "message": "Token 已刷新",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

- 旧 Token は自動的にブラックリストへ追加
- ユーザーごとに最大 3 つの有効 Token

---

## モジュール 3: プラットフォーム & アカウント

### GET /api/platforms — プラットフォームリスト

認証不要。1 時間キャッシュ。

**レスポンス**:
```json
{
  "code": 0,
  "data": [
    { "code": "juliang", "name": "巨量引擎", "flag": "🇨🇳", "capabilities": ["campaign", "report"] },
    { "code": "meta", "name": "Meta Ads", "flag": "🇺🇸", "capabilities": ["campaign", "report"] }
  ]
}
```

---

### GET /api/platforms/:code/oauth-url — OAuth 認可 URL

**パラメータ**: `?redirect_uri=https://your-domain.com/callback`

**レスポンス**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` は SSRF ホワイトリスト検証を通過する必要がある（`OAUTH_ALLOWED_REDIRECTS` 環境変数）

---

### POST /api/platforms/:code/callback — OAuth コールバック

**リクエスト**: `{ "state": "...", "code": "..." }`

**レスポンス**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/accounts — アカウントリスト

5 分キャッシュ。

**パラメータ**:

| パラメータ | 説明 |
|------|------|
| `platform` | プラットフォームコードでフィルタ |
| `page` | ページ番号 |
| `per_page` | 1 ページあたりの件数 |

**レスポンス**: ページネーション形式。list の各項目に `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at` を含む

---

### GET /api/accounts/:id — アカウント詳細

5 分キャッシュ。

---

### DELETE /api/accounts/:id — アカウント解除

---

### POST /api/accounts/:id/sync — 手動同期

---

## モジュール 4: 広告プラン

### GET /api/campaigns — プランリスト

**パラメータ**:

| パラメータ | 説明 | 選択可能な値 |
|------|------|--------|
| `platform` | プラットフォームでフィルタ | juliang, meta, google... |
| `status` | ステータスでフィルタ | enabled, paused |
| `keyword` | 名前検索 | 任意テキスト |
| `sort` | ソートフィールド | id, name, platform, daily_budget, status, created_at |
| `page` | ページ番号 | — |
| `per_page` | 1 ページあたりの件数 | ≤100 |

**レスポンス**: ページネーション形式 + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/campaigns — プラン作成

**リクエスト**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**レスポンス**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- `daily_budget` の単位: 分（20000 = ¥200.00）

---

### GET /api/campaigns/:id — プラン詳細

**レスポンス**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/campaigns/:id — プラン更新

**リクエスト**: `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/campaigns/:id/toggle — プランの開始・停止

**リクエスト**: `{ "enabled": false }`

---

### POST /api/campaigns/batch/toggle — 一括開始・停止

**リクエスト**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**レスポンス**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## モジュール 5: 広告グループ

### GET /api/ad-groups — 広告グループリスト

**パラメータ**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/ad-groups — 広告グループ作成

**リクエスト**:
```json
{
  "campaign_id": 1,
  "name": "测试广告组",
  "bid_amount": 100,
  "bid_type": "cpc",
  "targeting": { "age": { "min": 18, "max": 45 } },
  "targeting_template_id": "hashids-encoded-template-id"
}
```

- `targeting_template_id`: 任意。ターゲティングテンプレートから targeting JSON を読み込んでマージ

### GET /api/ad-groups/:id — 広告グループ詳細

### PUT /api/ad-groups/:id — 広告グループ更新

### POST /api/ad-groups/:id/toggle — 広告グループの開始・停止

---

## モジュール 6: クリエイティブ

### GET /api/creatives — クリエイティブリスト

**パラメータ**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/creatives/:id — クリエイティブ詳細

---

## モジュール 7: レポート

### GET /api/reports/summary — ダッシュボード集計

5 分キャッシュ。

**パラメータ**: `date_start`, `date_end`

**レスポンス**:
```json
{
  "code": 0,
  "data": {
    "overview": { "cost": 123456, "impressions": 10000, ... },
    "by_platform": [ ... ],
    "daily": [ ... ]
  }
}
```

---

### GET /api/reports/custom — カスタムレポート

**パラメータ**:

| パラメータ | 説明 |
|------|------|
| `dimensions[]` | ディメンション: date, platform, campaign |
| `metrics[]` | 指標: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | 開始日 |
| `date_end` | 終了日 |
| `platform` | プラットフォームでフィルタ |

---

### GET /api/reports/export — レポートエクスポート

**パラメータ**: `format=csv`, `date_start`, `date_end`, `metrics[]`

ファイルダウンロードを返す（CSV UTF-8 BOM または Excel .xls）。

---

### GET /api/reports/export-dashboard — ダッシュボード PDF エクスポート

---

### GET /api/reports/calendar — 配信カレンダー

**パラメータ**: `date_start`, `date_end`, `platform`

**レスポンス**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/reports/budget-alerts — 予算警告

**レスポンス**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/reports/attribution — アトリビューション分析

**パラメータ**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**レスポンス**:
```json
{
  "code": 0,
  "data": {
    "total_conversions": 42,
    "total_value": 123456.78,
    "by_campaign": [ { "campaign_id": 1, "credit": 5000.00 } ]
  }
}
```

---

### GET /api/reports/attribution/models — アトリビューションモデルリスト

**レスポンス**: `[{ code: "last_touch", name: "末次触点", description: "..." }]`

全 5 モデル。

---

## モジュール 8: アラート

### GET /api/alerts/rules — アラートルールリスト

2 分キャッシュ。

**パラメータ**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/alerts/rules — アラートルール作成

**リクエスト**:
```json
{
  "name": "花费超限",
  "metric": "cost",
  "condition": "gt",
  "threshold": 100000,
  "scope": "tenant",
  "platform": null,
  "campaign_id": null,
  "channels": ["web"]
}
```

### PUT /api/alerts/rules/:id — アラートルール更新

### DELETE /api/alerts/rules/:id — アラートルール削除

### GET /api/alerts/logs — アラート記録

**パラメータ**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/alerts/logs/:id/acknowledge — アラート確認

### GET /api/alerts/unread-count — 未読アラート数

30 秒キャッシュ。フロントエンドは 30s ポーリング。

---

## モジュール 9: 通知

### GET /api/notifications — 通知リスト

**パラメータ**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/notifications/unread-count — 未読通知数

### POST /api/notifications/:id/read — 既読マーク

### POST /api/notifications/read-all — 全既読

---

## モジュール 10: 自動入札

### GET /api/bid-rules — ルールリスト

### POST /api/bid-rules — ルール作成

**リクエスト**:
```json
{
  "name": "ROI 达标加预算",
  "metric": "roi",
  "condition": "gte",
  "threshold": 3.0,
  "action_type": "adjust_budget",
  "adjust_step": 5000,
  "budget_min": 0,
  "budget_max": 100000,
  "cooldown_minutes": 60
}
```

**フィールド説明**:

| フィールド | 型 | 説明 |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | 監視指標 |
| condition | gt/gte/lt/lte | 発火条件 |
| threshold | decimal | しきい値 |
| action_type | adjust_budget/toggle_pause/toggle_enable | アクションタイプ |
| adjust_step | int (分) | 予算調整ステップ（正=増加, 負=減少） |
| budget_min | int | 予算下限（分） |
| budget_max | int | 予算上限（分） |
| cooldown_minutes | int | クールダウン時間（デフォルト 60） |

### PUT /api/bid-rules/:id — ルール更新

### DELETE /api/bid-rules/:id — ルール削除

### GET /api/bid-rules/logs — 入札履歴

**パラメータ**: `rule_id`, `campaign_id`

---

## モジュール 11: ターゲティングテンプレート

### GET /api/targeting-templates — テンプレートリスト

**パラメータ**: `platform`

### GET /api/targeting-templates/:id — テンプレート詳細

### POST /api/targeting-templates — テンプレート作成

**リクエスト**:
```json
{
  "name": "核心受众",
  "platform": "",
  "targeting": {
    "age": { "min": 18, "max": 45 },
    "gender": "all",
    "interests": ["sports", "tech"],
    "devices": { "os": ["android", "ios"] }
  },
  "is_shared": 0
}
```

### PUT /api/targeting-templates/:id — テンプレート更新

### DELETE /api/targeting-templates/:id — テンプレート削除

---

## モジュール 12: 素材ライブラリ

### GET /api/assets — 素材リスト

**パラメータ**: `type`(image/video), `page`, `per_page`

### POST /api/assets/upload — 素材アップロード

**リクエスト**: `multipart/form-data`, フィールド `file`

- 画像: 最大 5 MB (jpeg/png/gif/webp)
- 動画: 最大 50 MB (mp4)

**レスポンス**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

- CDN 設定時、`url` はデフォルトプロバイダーの `cdn_domain` を付けて完全な HTTPS アドレスに組み立てられます

### POST /api/assets/presign — 事前署名アップロード URL を取得

**リクエスト**: `{ "filename": "demo.mp4", "mime_type": "video/mp4" }`

**レスポンス**: `{ "code": 0, "data": { "key": "20260829/ab12...cd34.mp4", "upload_url": "https://signed-url", "expires_in": 3600, "url": "https://cdn.example.com/uploads/assets/..." } }`

- `key` 形式: `Ymd/32hex.拡張子`。直接アップロード後 `/api/assets/register` に返却
- 50 MiB までの動画はクライアントがオブジェクトストレージへ直接アップロード。`local` driver では利用不可

### POST /api/assets/register — 直接アップロード済み素材を登録

**リクエスト**: `{ "key": "20260829/ab12...cd34.mp4", "filename": "demo.mp4", "mime_type": "video/mp4", "size": 52428800 }`

**レスポンス**: `{ "code": 0, "message": "Created", "data": { "id": "hashids", "url": "https://cdn.example.com/uploads/assets/...", "type": "video" } }`

- `key` を厳格検証 (`^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$`) — パストラバーサル防止

### GET /api/assets/:id — 素材詳細

### DELETE /api/assets/:id — 素材削除

---

## Admin エンドポイント（ポート 8789）

### POST /api/admin/login — 管理者ログイン

**リクエスト**: `{ "username": "admin", "password": "..." }`

**レスポンス**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Token は localStorage に保存
- `csrf_token` は後続の POST/PUT/DELETE リクエストの `X-CSRF-Token` header で送信する必要がある

### GET /api/admin/me — 現在の管理者

### POST /api/admin/logout — ログアウト

### GET /api/admin/users — ユーザーリスト

**パラメータ**: `keyword`, `role_id`, `page`, `per_page`

レスポンスの `id` と `role_id` は hashids でエンコードされる。

### POST /api/admin/users — ユーザー作成

### PUT /api/admin/users/:id — ユーザー更新

### DELETE /api/admin/users/:id — ユーザー無効化

### GET /api/admin/users/roles — ロールリスト

### GET /api/admin/audit-logs — 監査ログ

**パラメータ**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

### CDN プロバイダー管理 (プラットフォームのマスターテナント tenant 1 のみ、AdminMiddleware 検証)

### GET /api/admin/cdn/providers — プロバイダー一覧

### POST /api/admin/cdn/providers — プロバイダー作成

**リクエスト**: `{ "name": "Aliyun OSS", "driver": "oss", "bucket": "ads-assets", "region": "oss-cn-hangzhou", "endpoint": "https://oss-cn-hangzhou.aliyuncs.com", "access_key": "...", "secret_key": "...", "cdn_domain": "cdn.example.com", "cdn_driver": "aliyun", "cdn_token": "...", "is_default": 1 }`

- `driver`: `local` / `oss` (阿里云 OSS) / `cos` (腾讯云 COS、S3 プロトコル) / `s3` (S3 互換: AWS S3 / Cloudflare R2 / MinIO)
- 認証情報 (access_key/secret_key/cdn_token) は Encryptable でフィールド単位暗号化。レスポンスはマスク済みフィールドのみ

### PUT /api/admin/cdn/providers/:id — プロバイダー更新

### DELETE /api/admin/cdn/providers/:id — 削除 (デフォルトは次の enabled プロバイダーへ自動移行)

### PUT /api/admin/cdn/providers/:id/default — デフォルトに設定

### PUT /api/admin/cdn/providers/:id/toggle — 有効/無効切替 (デフォルト無効化時は自動移行)

### POST /api/admin/cdn/providers/:id/test — 接続テスト

**レスポンス**: `{ "code": 0, "data": { "ok": true, "driver": "oss", "status": "ok" } }`

### POST /api/admin/cdn/providers/:id/purge — キャッシュパージ

**リクエスト**: `{ "paths": ["/uploads/assets/20260829/xxx.mp4"] }`

- `cdn_driver` と `cdn_domain` が必要。`aliyun` は実装済み (OpenAPI 署名)、cloudflare/cloudfront は今後拡張

---

## エラーコードリファレンス

| code | HTTP | 説明 |
|------|------|------|
| 0 | 200 | 成功 |
| 1 | 200/400 | 一般的な業務エラー |
| 401 | 401 | 未認証 / Token 期限切れ / IP/UA 不一致 |
| 403 | 403 | アクセス禁止（セキュリティインターセプト） |
| 404 | 404 | リソースが存在しない |
| 422 | 422 | パラメータ検証失敗 |
| 429 | 429 | リクエスト過多 / ログインスロットル / 同時制限 |
| 1001 | 200 | 認証失敗（ユーザー名またはパスワード誤り） |

---

## セキュリティインターセプトレスポンス

リクエストがセキュリティミドルウェアにインターセプトされた場合、403 を返す：

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## レート制限レスポンス

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

`Retry-After` header に残り待機秒数が含まれる。
