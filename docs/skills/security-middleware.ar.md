# الوسيط الأمني (Security Middleware)

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

إضافة وسيط أمني جديد إلى خط معالجة الطلبات.

## واجهة الوسيط

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

## التسجيل

حرّر `service/config/middleware.php`، وأضف اسم الفئة بالترتيب في مصفوفة `global`:

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## سلسلة الوسائط الحالية

| المرحلة | الوسيط | المسؤولية |
|------|--------|------|
| الحدود | CorsMiddleware | قائمة CORS البيضاء |
| الحدود | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| الكشف | AttackGuardMiddleware | XSS/اجتياز المسار/حقن الترويسات |
| التحديد | ClientPlatformMiddleware | تحديد منصة المصدر |
| التوجيه | VersionMiddleware | توجيه إصدارات API |
| التحكم | RateLimitMiddleware | تحديد المعدل بالنافذة المنزلقة |
| التحكم | LoginThrottleMiddleware | تقييد سرعة تسجيل الدخول |
| الكشف | SqlGuardMiddleware | كشف حقن SQL |
| التنظيف | ValidationMiddleware | strip_tags + trim |
| المراقبة | ResponseTimeMiddleware | X-Response-Time |
| التشفير | EncryptionMiddleware | تشفير/فك تشفير X-Encrypted |

## إضافة وسيط للوحة الإدارة

توجد وسائط لوحة الإدارة في `admin/app/middleware/`، وتُسجل في `admin/config/middleware.php`.
