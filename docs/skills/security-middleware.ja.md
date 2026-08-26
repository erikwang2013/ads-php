# セキュリティミドルウェア

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

リクエストパイプラインに新しいセキュリティミドルウェアを追加します。

## ミドルウェアインターフェース

```php
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class YourMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 检测逻辑
        if ($this->shouldBlock($request)) {
            return new Response(403, ['Content-Type' => 'application/json'],
                json_encode(['code' => 403, 'message' => 'Forbidden']));
        }
        return $handler($request);
    }
}
```

## 登録

`service/config/middleware.php` を編集し、`global` 配列に順序どおりクラス名を追加します：

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## 既存のミドルウェアチェーン

| 段階 | ミドルウェア | 役割 |
|------|--------|------|
| 境界 | CorsMiddleware | CORS ホワイトリスト |
| 境界 | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| 検出 | AttackGuardMiddleware | XSS/パストラバーサル/Headerインジェクション |
| 識別 | ClientPlatformMiddleware | 発生元の識別 |
| ルーティング | VersionMiddleware | API バージョンルーティング |
| 制御 | RateLimitMiddleware | スライディングウィンドウ限流 |
| 制御 | LoginThrottleMiddleware | ログインスロットル |
| 検出 | SqlGuardMiddleware | SQL インジェクション検出 |
| サニタイズ | ValidationMiddleware | strip_tags + trim |
| 監視 | ResponseTimeMiddleware | X-Response-Time |
| 暗号化 | EncryptionMiddleware | X-Encrypted 暗号化/復号 |

## Admin 側ミドルウェアの追加

Admin ミドルウェアは `admin/app/middleware/` にあり、`admin/config/middleware.php` に登録します。
