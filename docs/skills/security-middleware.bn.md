# সিকিউরিটি মিডলওয়্যার

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

রিকোয়েস্ট পাইপলাইনে নতুন সিকিউরিটি মিডলওয়্যার যোগ করুন।

## মিডলওয়্যার ইন্টারফেস

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

## রেজিস্ট্রেশন

`service/config/middleware.php` এডিট করুন, `global` অ্যারেতে ক্রম অনুযায়ী ক্লাস নাম যোগ করুন：

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## বিদ্যমান মিডলওয়্যার চেইন

| পর্যায় | মিডলওয়্যার | দায়িত্ব |
|------|--------|------|
| বাউন্ডারি | CorsMiddleware | CORS হোয়াইটলিস্ট |
| বাউন্ডারি | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| ডিটেকশন | AttackGuardMiddleware | XSS/পাথ ট্রাভার্সাল/Header ইনজেকশন |
| আইডেন্টিফিকেশন | ClientPlatformMiddleware | উৎস এন্ড আইডেন্টিফিকেশন |
| রাউটিং | VersionMiddleware | API ভার্সন রাউটিং |
| কন্ট্রোল | RateLimitMiddleware | স্লাইডিং উইন্ডো রেট লিমিট |
| কন্ট্রোল | LoginThrottleMiddleware | লগইন থ্রটলিং |
| ডিটেকশন | SqlGuardMiddleware | SQL ইনজেকশন ডিটেকশন |
| ক্লিনিং | ValidationMiddleware | strip_tags + trim |
| মনিটরিং | ResponseTimeMiddleware | X-Response-Time |
| এনক্রিপশন | EncryptionMiddleware | X-Encrypted এনক্রিপশন/ডিক্রিপশন |

## Admin এন্ড মিডলওয়্যার যোগ

Admin মিডলওয়্যার `admin/app/middleware/`-এ থাকে, রেজিস্ট্রেশন হয় `admin/config/middleware.php`-এ।
