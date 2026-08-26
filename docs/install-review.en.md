# Ads-PHP Security Review & Fix Report (Round 3)

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**Generated**: 2026-08-04  
**Review scope**: All security middleware, auth flows, install controller, config files  
**PHP version**: 8.3.7 | **Framework**: webman v2

---

## 1. Fix Overview

This round comprehensively fixed the 6 issues found in the Round 2 security review.

| # | Issue | Fix | Status |
|---|-------|-----|:--:|
| 1 | Admin missing 5 security middleware | Created CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware | Fixed |
| 2 | Admin AuthCheck does not bind IP/UA | AuthController adds `_ip` / `_ua` to JWT payload, AuthCheck validates binding | Fixed |
| 3 | AttackGuardMiddleware ReDoS risk | Added `maxStrLen=8192` pre-check, rejects overly long strings directly | Fixed |
| 4 | InstallController password special chars | Added `envQuote()` method, auto-wraps with quotes + escaping | Fixed |
| 5 | Incomplete admin middleware config | Updated to 10-layer global middleware stack | Fixed |
| 6 | Outdated README middleware layer counts | Synced updates in Chinese and English READMEs | Fixed |

---

## 2. Syntax Verification

| File | Lines | Syntax |
|------|-------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | Pass |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Pass |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Pass |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Pass |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Pass |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | Pass |
| `admin/app/middleware/AuthCheck.php` | 48 | Pass |
| `admin/app/controller/AuthController.php` | 194 | Pass |
| `admin/app/controller/InstallController.php` | 298 | Pass |
| `admin/config/middleware.php` | 43 | Pass |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | Pass |

---

## 3. Middleware Stack (after fixes)

### Service (14 global + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（路由层）
```

### Admin (10 global + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（路由层）
```

### Route Matrix (admin, after update)

| Route | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|-------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (protected) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## 4. Security Improvement Details

### 4.1 New Admin Middleware

| Middleware | File | Responsibility |
|------------|------|----------------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | CORS preflight + response headers, allow all in debug mode, whitelist in production |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Redis sliding-window rate limit 60req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | SQL injection pattern detection (UNION/DROP/ALTER/comment chars) |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | Input trim + strip_tags (excluding description/content/extra) |

### 4.2 JWT Token Binding

AuthController adds `_ip` and `_ua` to the JWT payload on login:

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

The AuthCheck middleware verifies IP and UA consistency when validating the token, denying access on mismatch.

### 4.3 ReDoS Protection

AttackGuardMiddleware (admin + service) adds `maxStrLen = 8192`:

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 .env Password Escaping

InstallController adds an `envQuote()` method that detects special characters in passwords (spaces, `$`, `#`, quotes, backslashes) and automatically wraps them in double quotes with escaping.

---

## 5. File List

### New (5 files)

| File | Lines | Description |
|------|-------|-------------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | CORS middleware |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Security response headers |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Global rate limiting |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | SQL injection detection |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Input sanitization |

### Modified (6 files)

| File | Change |
|------|--------|
| `admin/config/middleware.php` | Global middleware 5→10 layers |
| `admin/app/middleware/AttackGuardMiddleware.php` | Added ReDoS protection (maxStrLen) |
| `admin/app/middleware/AuthCheck.php` | Added JWT IP/UA binding validation |
| `admin/app/controller/AuthController.php` | Added _ip/_ua to JWT payload |
| `admin/app/controller/InstallController.php` | Added envQuote() password escaping |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | Added ReDoS protection (maxStrLen) |

---

## 6. Conclusion

All 6 security issues have been fixed. Admin defense increased from 5 to 10 global middleware layers, adding 5 key protections: security response headers, rate limiting, SQL injection detection, input sanitization, and CORS. JWT tokens now include IP/UA binding validation. The ReDoS risk and .env password special-character issues have been eliminated. All files pass PHP syntax checks.
