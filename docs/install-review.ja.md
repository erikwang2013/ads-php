# Ads-PHP セキュリティ審査と修正レポート（第 3 ラウンド）

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**生成日時**: 2026-08-04  
**審査範囲**: 全セキュリティミドルウェア、認証フロー、インストールコントローラー、設定ファイル  
**PHP バージョン**: 8.3.7 | **フレームワーク**: webman v2

---

## 一、修正概要

今回のラウンドでは、第 2 ラウンドのセキュリティ審査で発見された 6 つの問題を全面的に修正しました。

| # | 問題 | 修正方法 | ステータス |
|---|------|---------|:--:|
| 1 | admin 側に 5 つのセキュリティミドルウェアが不足 | CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware を新規作成 | 修正済み |
| 2 | admin の AuthCheck が IP/UA バインドを行わない | AuthController の JWT payload に `_ip` / `_ua` を追加、AuthCheck でバインドを検証 | 修正済み |
| 3 | AttackGuardMiddleware の ReDoS リスク | `maxStrLen=8192` の事前チェックを追加、超長文字列は直接拒否 | 修正済み |
| 4 | InstallController のパスワード特殊文字 | `envQuote()` メソッドを追加、自動クォート + エスケープ | 修正済み |
| 5 | admin のミドルウェア設定が不完全 | 10 層グローバルミドルウェアスタックに更新 | 修正済み |
| 6 | README のミドルウェア層数が古い | 中英 README を同期更新 | 修正済み |

---

## 二、構文検証

| ファイル | 行数 | 構文 |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | 通過 |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | 通過 |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | 通過 |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | 通過 |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | 通過 |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | 通過 |
| `admin/app/middleware/AuthCheck.php` | 48 | 通過 |
| `admin/app/controller/AuthController.php` | 194 | 通過 |
| `admin/app/controller/InstallController.php` | 298 | 通過 |
| `admin/config/middleware.php` | 43 | 通過 |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | 通過 |

---

## 三、ミドルウェアスタック（修正後）

### Service 側 (14 層グローバル + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（ルート層）
```

### Admin 側 (10 層グローバル + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（ルート層）
```

### ルートマトリクス（admin 側更新後）

| ルート | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (保護) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## 四、セキュリティ改善の詳細

### 4.1 admin の新規ミドルウェア

| ミドルウェア | ファイル | 役割 |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | CORS プリフライト + レスポンスヘッダー、debug モードは全て許可、本番はホワイトリスト |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Redis スライディングウィンドウ限流 60req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | SQL インジェクションパターン検出（UNION/DROP/ALTER/コメント記号） |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | 入力 trim + strip_tags（description/content/extra は除外） |

### 4.2 JWT Token バインド

AuthController はログイン時に JWT payload へ `_ip` と `_ua` を追加：

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

AuthCheck ミドルウェアは token 検証時に IP と UA の整合性をチェックし、不一致ならアクセスを拒否します。

### 4.3 ReDoS 対策

AttackGuardMiddleware（admin + service）に `maxStrLen = 8192` を追加：

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 .env パスワードエスケープ

InstallController に `envQuote()` メソッドを追加。パスワード内の特殊文字（スペース、`$`、`#`、クォート、バックスラッシュ）を検出し、自動的にダブルクォートで包んでエスケープします。

---

## 五、ファイルリスト

### 新規（5 ファイル）

| ファイル | 行数 | 説明 |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | CORS ミドルウェア |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | セキュリティレスポンスヘッダー |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | グローバルレート制限 |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | SQL インジェクション検出 |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | 入力サニタイズ |

### 修正（6 ファイル）

| ファイル | 変更内容 |
|------|------|
| `admin/config/middleware.php` | グローバルミドルウェア 5→10 層 |
| `admin/app/middleware/AttackGuardMiddleware.php` | ReDoS 対策を追加（maxStrLen） |
| `admin/app/middleware/AuthCheck.php` | JWT IP/UA バインド検証を追加 |
| `admin/app/controller/AuthController.php` | JWT payload に _ip/_ua を追加 |
| `admin/app/controller/InstallController.php` | envQuote() パスワードエスケープを追加 |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | ReDoS 対策を追加（maxStrLen） |

---

## 六、結論

6 つのセキュリティ問題はすべて修正済みです。admin 側の防御は 5 層から 10 層のグローバルミドルウェアに増強され、セキュリティレスポンスヘッダー、レート制限、SQL インジェクション検出、入力サニタイズ、CORS の 5 つの重要防御が補完されました。JWT token には IP/UA バインド検証が追加されました。ReDoS リスクと .env パスワード特殊文字の問題は解消されました。全ファイルが PHP 構文チェックを通過しています。
