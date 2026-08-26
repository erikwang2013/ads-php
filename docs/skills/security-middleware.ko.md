# 보안 미들웨어

[中文](docs/skills/security-middleware.md) | [English](docs/skills/security-middleware.en.md) | [한국어](docs/skills/security-middleware.ko.md) | [Русский](docs/skills/security-middleware.ru.md) | [Deutsch](docs/skills/security-middleware.de.md) | [Français](docs/skills/security-middleware.fr.md) | [Español](docs/skills/security-middleware.es.md) | [Português](docs/skills/security-middleware.pt.md) | [हिन्दी](docs/skills/security-middleware.hi.md) | [العربية](docs/skills/security-middleware.ar.md) | [বাংলা](docs/skills/security-middleware.bn.md) | [Bahasa Indonesia](docs/skills/security-middleware.id.md) | [日本語](docs/skills/security-middleware.ja.md)

요청 파이프라인에 새 보안 미들웨어를 추가합니다.

## 미들웨어 인터페이스

```php
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class YourMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 검출 로직
        if ($this->shouldBlock($request)) {
            return new Response(403, ['Content-Type' => 'application/json'],
                json_encode(['code' => 403, 'message' => 'Forbidden']));
        }
        return $handler($request);
    }
}
```

## 등록

`service/config/middleware.php`를 편집하여 `global` 배열에 순서대로 클래스 이름을 추가:

```php
'global' => [
    // 초기: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 중기: Version, RateLimit, LoginThrottle, SqlGuard
    // 후기: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## 기존 미들웨어 체인

| 단계 | 미들웨어 | 역할 |
|------|--------|------|
| 경계 | CorsMiddleware | CORS 화이트리스트 |
| 경계 | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| 검출 | AttackGuardMiddleware | XSS/경로 탐색/Header 주입 |
| 식별 | ClientPlatformMiddleware | 출처 단말 식별 |
| 라우팅 | VersionMiddleware | API 버전 라우팅 |
| 제어 | RateLimitMiddleware | 슬라이딩 윈도우 속도 제한 |
| 제어 | LoginThrottleMiddleware | 로그인 스로틀 |
| 검출 | SqlGuardMiddleware | SQL 주입 검출 |
| 정제 | ValidationMiddleware | strip_tags + trim |
| 모니터링 | ResponseTimeMiddleware | X-Response-Time |
| 암호화 | EncryptionMiddleware | X-Encrypted 암복호화 |

## Admin 측 미들웨어 추가

Admin 미들웨어는 `admin/app/middleware/`에 있으며, `admin/config/middleware.php`에 등록합니다.
