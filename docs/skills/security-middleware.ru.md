# Middleware безопасности

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

Добавление нового middleware безопасности в конвейер запросов.

## Интерфейс middleware

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

## Регистрация

Отредактируйте `service/config/middleware.php`, добавьте имя класса в массив `global` в нужном порядке:

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## Существующая цепочка middleware

| Этап | Middleware | Назначение |
|------|--------|------|
| Граница | CorsMiddleware | Белый список CORS |
| Граница | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| Обнаружение | AttackGuardMiddleware | XSS/обход путей/инъекция заголовков |
| Идентификация | ClientPlatformMiddleware | Определение источника |
| Маршрутизация | VersionMiddleware | Версионирование маршрутов API |
| Контроль | RateLimitMiddleware | Лимитирование скользящим окном |
| Контроль | LoginThrottleMiddleware | Троттлинг входа |
| Обнаружение | SqlGuardMiddleware | Обнаружение SQL-инъекций |
| Очистка | ValidationMiddleware | strip_tags + trim |
| Мониторинг | ResponseTimeMiddleware | X-Response-Time |
| Шифрование | EncryptionMiddleware | Шифрование/дешифрование X-Encrypted |

## Добавление middleware на стороне Admin

Middleware Admin находятся в `admin/app/middleware/`, регистрируются в `admin/config/middleware.php`.
