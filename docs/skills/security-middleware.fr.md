# Middleware de sécurité

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

Ajouter de nouveaux middlewares de sécurité au pipeline de requêtes.

## Interface des middlewares

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

## Enregistrement

Modifier `service/config/middleware.php`, ajouter le nom de classe dans le tableau `global` dans l'ordre :

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## Chaîne de middlewares existante

| Étape | Middleware | Rôle |
|------|--------|------|
| Frontière | CorsMiddleware | Liste blanche CORS |
| Frontière | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| Détection | AttackGuardMiddleware | XSS/traversée de chemin/injection d'en-têtes |
| Identification | ClientPlatformMiddleware | Identification de la plateforme d'origine |
| Routage | VersionMiddleware | Routage par version API |
| Contrôle | RateLimitMiddleware | Limitation par fenêtre glissante |
| Contrôle | LoginThrottleMiddleware | Limitation de connexion |
| Détection | SqlGuardMiddleware | Détection d'injection SQL |
| Nettoyage | ValidationMiddleware | strip_tags + trim |
| Surveillance | ResponseTimeMiddleware | X-Response-Time |
| Chiffrement | EncryptionMiddleware | Chiffrement/déchiffrement X-Encrypted |

## Ajout d'un middleware côté Admin

Les middlewares Admin se trouvent dans `admin/app/middleware/`, enregistrés dans `admin/config/middleware.php`.
