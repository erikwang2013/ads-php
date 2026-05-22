# 安全中间件

向请求管道添加新的安全中间件。

## 中间件接口

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

## 注册

编辑 `service/config/middleware.php`，在 `global` 数组中按顺序添加类名：

```php
'global' => [
    // 早期: CORS, SecurityHeaders, AttackGuard, ClientPlatform
    // 中期: Version, RateLimit, LoginThrottle, SqlGuard
    // 后期: Validation, ResponseTime, Encryption
    plugin\ads_api\middleware\YourMiddleware::class,
],
```

## 现有中间件链

| 阶段 | 中间件 | 职责 |
|------|--------|------|
| 边界 | CorsMiddleware | CORS 白名单 |
| 边界 | SecurityHeadersMiddleware | X-Frame/CSP/HSTS |
| 检测 | AttackGuardMiddleware | XSS/路径遍历/Header注入 |
| 识别 | ClientPlatformMiddleware | 来源端识别 |
| 路由 | VersionMiddleware | API 版本路由 |
| 控制 | RateLimitMiddleware | 滑动窗口限流 |
| 控制 | LoginThrottleMiddleware | 登录节流 |
| 检测 | SqlGuardMiddleware | SQL 注入检测 |
| 清洗 | ValidationMiddleware | strip_tags + trim |
| 监控 | ResponseTimeMiddleware | X-Response-Time |
| 加密 | EncryptionMiddleware | X-Encrypted 加解密 |

## 添加 Admin 端中间件

Admin 中间件位于 `admin/app/middleware/`，注册在 `admin/config/middleware.php`。
