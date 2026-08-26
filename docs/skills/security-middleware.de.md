# Sicherheits-Middleware

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

Neue Sicherheits-Middleware zur Request-Pipeline hinzufügen.

## Middleware-Interface

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

## Registrierung

`service/config/middleware.php` bearbeiten, den Klassennamen in der Reihenfolge in das `global`-Array aufnehmen:

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## Bestehende Middleware-Kette

| Phase | Middleware | Aufgabe |
|------|--------|------|
| Grenze | CorsMiddleware | CORS-Whitelist |
| Grenze | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| Erkennung | AttackGuardMiddleware | XSS/Pfad-Traversal/Header-Injection |
| Identifikation | ClientPlatformMiddleware | Client-Endpunkt-Erkennung |
| Routing | VersionMiddleware | API-Versionsrouting |
| Steuerung | RateLimitMiddleware | Ratenbegrenzung mit gleitendem Fenster |
| Steuerung | LoginThrottleMiddleware | Login-Drosselung |
| Erkennung | SqlGuardMiddleware | SQL-Injection-Erkennung |
| Bereinigung | ValidationMiddleware | strip_tags + trim |
| Überwachung | ResponseTimeMiddleware | X-Response-Time |
| Verschlüsselung | EncryptionMiddleware | X-Encrypted Ver-/Entschlüsselung |

## Admin-Middleware hinzufügen

Die Admin-Middleware liegt in `admin/app/middleware/` und wird in `admin/config/middleware.php` registriert.
