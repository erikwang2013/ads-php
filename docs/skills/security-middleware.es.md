# 安全中间件

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

Añade nuevos middlewares de seguridad al pipeline de solicitudes.

## Interfaz de middleware

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

## Registro

Edita `service/config/middleware.php` y añade el nombre de la clase en el array `global` en orden:

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## Cadena de middlewares existente

| Fase | Middleware | Responsabilidad |
|------|--------|------|
| Frontera | CorsMiddleware | Lista blanca CORS |
| Frontera | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| Detección | AttackGuardMiddleware | XSS/path traversal/inyección de cabeceras |
| Identificación | ClientPlatformMiddleware | Identificación del extremo de origen |
| Enrutamiento | VersionMiddleware | Enrutamiento por versión de API |
| Control | RateLimitMiddleware | Limitación con ventana deslizante |
| Control | LoginThrottleMiddleware | Limitación de inicio de sesión |
| Detección | SqlGuardMiddleware | Detección de inyección SQL |
| Saneamiento | ValidationMiddleware | strip_tags + trim |
| Monitoreo | ResponseTimeMiddleware | X-Response-Time |
| Cifrado | EncryptionMiddleware | Cifrado/descifrado X-Encrypted |

## Añadir middleware en el lado Admin

Los middlewares de Admin están en `admin/app/middleware/` y se registran en `admin/config/middleware.php`。
