# सुरक्षा मिडलवेयर

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

अनुरोध पाइपलाइन में नया सुरक्षा मिडलवेयर जोड़ें।

## मिडलवेयर इंटरफ़ेस

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

## पंजीकरण

`service/config/middleware.php` संपादित करें, `global` ऐरे में क्रम के अनुसार क्लास नाम जोड़ें:

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## मौजूदा मिडलवेयर चेन

| चरण | मिडलवेयर | ज़िम्मेदारी |
|------|--------|------|
| बाउंड्री | CorsMiddleware | CORS व्हाइटलिस्ट |
| बाउंड्री | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| डिटेक्शन | AttackGuardMiddleware | XSS/पाथ ट्रैवर्सल/Header इंजेक्शन |
| पहचान | ClientPlatformMiddleware | स्रोत एंड पहचान |
| रूटिंग | VersionMiddleware | API वर्शन रूटिंग |
| नियंत्रण | RateLimitMiddleware | स्लाइडिंग विंडो रेट-लिमिट |
| नियंत्रण | LoginThrottleMiddleware | लॉगिन थ्रॉटलिंग |
| डिटेक्शन | SqlGuardMiddleware | SQL इंजेक्शन डिटेक्शन |
| सफ़ाई | ValidationMiddleware | strip_tags + trim |
| मॉनिटरिंग | ResponseTimeMiddleware | X-Response-Time |
| एन्क्रिप्शन | EncryptionMiddleware | X-Encrypted एन्क्रिप्शन/डिक्रिप्शन |

## Admin एंड मिडलवेयर जोड़ना

Admin मिडलवेयर `admin/app/middleware/` में स्थित हैं, `admin/config/middleware.php` में पंजीकृत होते हैं।
