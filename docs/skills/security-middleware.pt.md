# Middlewares de segurança

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

Adicionar novos middlewares de segurança ao pipeline de requisições.

## Interface do middleware

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

Editar `service/config/middleware.php` e adicionar o nome da classe em ordem no array `global`:

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## Cadeia de middlewares existente

| Fase | Middleware | Responsabilidade |
|------|--------|------|
| Limite | CorsMiddleware | Lista de permissões CORS |
| Limite | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| Detecção | AttackGuardMiddleware | XSS/Path traversal/Injeção de Header |
| Identificação | ClientPlatformMiddleware | Identificação do cliente de origem |
| Rota | VersionMiddleware | Roteamento por versão da API |
| Controle | RateLimitMiddleware | Limitação por janela deslizante |
| Controle | LoginThrottleMiddleware | Throttle de login |
| Detecção | SqlGuardMiddleware | Detecção de injeção de SQL |
| Limpeza | ValidationMiddleware | strip_tags + trim |
| Monitoramento | ResponseTimeMiddleware | X-Response-Time |
| Criptografia | EncryptionMiddleware | Criptografia/descriptografia X-Encrypted |

## Adicionar middlewares no Admin

Os middlewares do Admin ficam em `admin/app/middleware/` e são registrados em `admin/config/middleware.php`.

