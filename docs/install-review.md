# Ads-PHP 安全审查与修复报告（第 3 轮）

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**生成时间**: 2026-08-04  
**审查范围**: 全部安全中间件、认证流程、安装控制器、配置文件  
**PHP 版本**: 8.3.7 | **框架**: webman v2  

---

## 一、修复概览

本轮针对第 2 轮安全审查发现的 6 个问题进行了全面修复。

| # | 问题 | 修复方式 | 状态 |
|---|------|---------|:--:|
| 1 | admin 端缺少 5 个安全中间件 | 新建 CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware | 已修复 |
| 2 | admin AuthCheck 不做 IP/UA 绑定 | AuthController JWT payload 加入 `_ip` / `_ua`，AuthCheck 校验绑定 | 已修复 |
| 3 | AttackGuardMiddleware ReDoS 风险 | 新增 `maxStrLen=8192` 预检查，超长字符串直接拒绝 | 已修复 |
| 4 | InstallController 密码特殊字符 | 新增 `envQuote()` 方法，自动引号包裹 + 转义 | 已修复 |
| 5 | admin 中间件配置不完整 | 更新为 10 层全局中间件栈 | 已修复 |
| 6 | README 中间件层数过时 | 中英文 README 同步更新 | 已修复 |

---

## 二、语法验证

| 文件 | 行数 | 语法 |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | 通过 |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | 通过 |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | 通过 |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | 通过 |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | 通过 |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | 通过 |
| `admin/app/middleware/AuthCheck.php` | 48 | 通过 |
| `admin/app/controller/AuthController.php` | 194 | 通过 |
| `admin/app/controller/InstallController.php` | 298 | 通过 |
| `admin/config/middleware.php` | 43 | 通过 |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | 通过 |

---

## 三、中间件栈（修复后）

### Service 端 (14 层全局 + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（路由层）
```

### Admin 端 (10 层全局 + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（路由层）
```

### 路由矩阵（admin 端更新后）

| 路由 | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (保护) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## 四、安全改进详情

### 4.1 admin 新增中间件

| 中间件 | 文件 | 职责 |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | CORS 预检 + 响应头，debug 模式放行所有，生产白名单 |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Redis 滑动窗口限流 60req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | SQL 注入模式检测（UNION/DROP/ALTER/注释符） |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | 输入 trim + strip_tags（排除 description/content/extra） |

### 4.2 JWT Token 绑定

AuthController 登录时在 JWT payload 中加入 `_ip` 和 `_ua`：

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

AuthCheck 中间件验证 token 时检查 IP 和 UA 一致性，不一致则拒绝访问。

### 4.3 ReDoS 防护

AttackGuardMiddleware（admin + service）新增 `maxStrLen = 8192`：

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 .env 密码转义

InstallController 新增 `envQuote()` 方法，检测密码中的特殊字符（空格、`$`、`#`、引号、反斜杠），自动用双引号包裹并转义。

---

## 五、文件清单

### 新增（5 文件）

| 文件 | 行数 | 说明 |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | CORS 中间件 |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | 安全响应头 |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | 全局限流 |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | SQL 注入检测 |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | 输入清洗 |

### 修改（6 文件）

| 文件 | 变更 |
|------|------|
| `admin/config/middleware.php` | 全局中间件 5→10 层 |
| `admin/app/middleware/AttackGuardMiddleware.php` | 新增 ReDoS 防护（maxStrLen） |
| `admin/app/middleware/AuthCheck.php` | 新增 JWT IP/UA 绑定校验 |
| `admin/app/controller/AuthController.php` | JWT payload 加入 _ip/_ua |
| `admin/app/controller/InstallController.php` | 新增 envQuote() 密码转义 |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 新增 ReDoS 防护（maxStrLen） |

---

## 六、结论

全部 6 个安全问题已修复。admin 端防御从 5 层增加到 10 层全局中间件，补齐了安全响应头、限流、SQL 注入检测、输入清洗、CORS 5 个关键防护。JWT token 增加了 IP/UA 绑定验证。ReDoS 风险和 .env 密码特殊字符问题已消除。所有文件通过 PHP 语法检查。
