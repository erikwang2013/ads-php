# Security Middleware

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

Add new security middleware to the request pipeline.

## Middleware Interface

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

## Registration

Edit `service/config/middleware.php` and add the class name in order to the `global` array:

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## Existing Middleware Chain

| Stage | Middleware | Responsibility |
|-------|------------|----------------|
| Boundary | CorsMiddleware | CORS whitelist |
| Boundary | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| Detection | AttackGuardMiddleware | XSS/path traversal/header injection |
| Identification | ClientPlatformMiddleware | Client source identification |
| Routing | VersionMiddleware | API version routing |
| Control | RateLimitMiddleware | Sliding-window rate limiting |
| Control | LoginThrottleMiddleware | Login throttling |
| Detection | SqlGuardMiddleware | SQL injection detection |
| Sanitization | ValidationMiddleware | strip_tags + trim |
| Monitoring | ResponseTimeMiddleware | X-Response-Time |
| Encryption | EncryptionMiddleware | X-Encrypted encryption/decryption |

## Adding Admin Middleware

Admin middleware lives in `admin/app/middleware/`, registered in `admin/config/middleware.php`.
