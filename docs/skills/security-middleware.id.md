# Middleware Keamanan

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

Tambahkan middleware keamanan baru ke pipeline request.

## Antarmuka Middleware

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

## Registrasi

Edit `service/config/middleware.php`, tambahkan nama kelas di array `global` secara berurutan:

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## Rantai Middleware yang Ada

| Tahap | Middleware | Tanggung jawab |
|------|--------|------|
| Batas | CorsMiddleware | Whitelist CORS |
| Batas | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| Deteksi | AttackGuardMiddleware | XSS/path traversal/Header injection |
| Identifikasi | ClientPlatformMiddleware | Identifikasi ujung sumber |
| Rute | VersionMiddleware | Rute versi API |
| Kontrol | RateLimitMiddleware | Rate limit sliding window |
| Kontrol | LoginThrottleMiddleware | Throttle login |
| Deteksi | SqlGuardMiddleware | Deteksi SQL injection |
| Pembersihan | ValidationMiddleware | strip_tags + trim |
| Pemantauan | ResponseTimeMiddleware | X-Response-Time |
| Enkripsi | EncryptionMiddleware | Enkripsi/dekripsi X-Encrypted |

## Menambahkan Middleware Sisi Admin

Middleware Admin berada di `admin/app/middleware/`, didaftarkan di `admin/config/middleware.php`.
