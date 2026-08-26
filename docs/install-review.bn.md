# Ads-PHP সিকিউরিটি রিভিউ ও ফিক্স রিপোর্ট（৩য় রাউন্ড）

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**জেনারেশন সময়**: 2026-08-04  
**রিভিউ সুযোগ**: সব সিকিউরিটি মিডলওয়্যার、অথেনটিকেশন ফ্লো、ইনস্টল কন্ট্রোলার、কনফিগ ফাইল  
**PHP ভার্সন**: 8.3.7 | **ফ্রেমওয়ার্ক**: webman v2  

---

## এক、ফিক্স ওভারভিউ

এই রাউন্ডে ২য় রাউন্ডের সিকিউরিটি রিভিউতে পাওয়া 6টি সমস্যা সম্পূর্ণভাবে ফিক্স করা হয়েছে।

| # | সমস্যা | ফিক্স পদ্ধতি | স্ট্যাটাস |
|---|------|---------|:--:|
| 1 | admin এন্ডে 5টি সিকিউরিটি মিডলওয়্যার নেই | নতুন CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware | ফিক্সড |
| 2 | admin AuthCheck IP/UA বাইন্ডিং করে না | AuthController JWT payload-এ `_ip` / `_ua` যোগ, AuthCheck বাইন্ডিং ভ্যালিডেট করে | ফিক্সড |
| 3 | AttackGuardMiddleware-এ ReDoS ঝুঁকি | নতুন `maxStrLen=8192` প্রি-চেক, অতিরিক্ত লম্বা স্ট্রিং সরাসরি রিজেক্ট | ফিক্সড |
| 4 | InstallController পাসওয়ার্ড স্পেশাল ক্যারেক্টার | নতুন `envQuote()` মেথড, অটো কোয়োট র্যাপ + এস্কেপ | ফিক্সড |
| 5 | admin মিডলওয়্যার কনফিগ অসম্পূর্ণ | 10 লেয়ার গ্লোবাল মিডলওয়্যার স্ট্যাকে আপডেট | ফিক্সড |
| 6 | README-এ মিডলওয়্যার লেয়ার সংখ্যা পুরনো | ইংরেজি ও চাইনিজ README সিঙ্ক আপডেট | ফিক্সড |

---

## দুই、সিনট্যাক্স ভ্যালিডেশন

| ফাইল | লাইন সংখ্যা | সিনট্যাক্স |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | পাস |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | পাস |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | পাস |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | পাস |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | পাস |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | পাস |
| `admin/app/middleware/AuthCheck.php` | 48 | পাস |
| `admin/app/controller/AuthController.php` | 194 | পাস |
| `admin/app/controller/InstallController.php` | 298 | পাস |
| `admin/config/middleware.php` | 43 | পাস |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | পাস |

---

## তিন、মিডলওয়্যার স্ট্যাক（ফিক্সের পরে）

### Service এন্ড (14 লেয়ার গ্লোবাল + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（路由层）
```

### Admin এন্ড (10 লেয়ার গ্লোবাল + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（路由层）
```

### রাউট ম্যাট্রিক্স（admin এন্ড আপডেটের পরে）

| রাউট | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (প্রোটেক্টেড) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## চার、সিকিউরিটি ইমপ্রুভমেন্ট ডিটেইল

### 4.1 admin নতুন মিডলওয়্যার

| মিডলওয়্যার | ফাইল | দায়িত্ব |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | CORS প্রি-ফ্লাইট + রেসপন্স হেডার, debug মোডে সব অনুমোদন, প্রোডাকশন হোয়াইটলিস্ট |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Redis স্লাইডিং উইন্ডো রেট লিমিট 60req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | SQL ইনজেকশন প্যাটার্ন ডিটেকশন（UNION/DROP/ALTER/কমেন্ট ক্যারেক্টার） |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | ইনপুট trim + strip_tags（description/content/extra বাদে） |

### 4.2 JWT Token বাইন্ডিং

AuthController লগইনে JWT payload-এ `_ip` এবং `_ua` যোগ করে:

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

AuthCheck মিডলওয়্যার token ভ্যালিডেট করার সময় IP এবং UA সামঞ্জস্য চেক করে, অসামঞ্জস্য হলে অ্যাক্সেস রিজেক্ট করে।

### 4.3 ReDoS প্রোটেকশন

AttackGuardMiddleware（admin + service）নতুন `maxStrLen = 8192`:

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 .env পাসওয়ার্ড এস্কেপ

InstallController নতুন `envQuote()` মেথড, পাসওয়ার্ডের স্পেশাল ক্যারেক্টার（স্পেস、`$`、`#`、কোয়োট、ব্যাকস্ল্যাশ）ডিটেক্ট করে, অটো ডাবল কোয়োট র্যাপ ও এস্কেপ করে।

---

## পাঁচ、ফাইল লিস্ট

### নতুন (5 ফাইল)

| ফাইল | লাইন সংখ্যা | বিবরণ |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | CORS মিডলওয়্যার |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | সিকিউরিটি রেসপন্স হেডার |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | গ্লোবাল রেট লিমিট |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | SQL ইনজেকশন ডিটেকশন |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | ইনপুট ক্লিনিং |

### মডিফাইড (6 ফাইল)

| ফাইল | পরিবর্তন |
|------|------|
| `admin/config/middleware.php` | গ্লোবাল মিডলওয়্যার 5→10 লেয়ার |
| `admin/app/middleware/AttackGuardMiddleware.php` | নতুন ReDoS প্রোটেকশন（maxStrLen） |
| `admin/app/middleware/AuthCheck.php` | নতুন JWT IP/UA বাইন্ডিং ভ্যালিডেশন |
| `admin/app/controller/AuthController.php` | JWT payload-এ _ip/_ua যোগ |
| `admin/app/controller/InstallController.php` | নতুন envQuote() পাসওয়ার্ড এস্কেপ |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | নতুন ReDoS প্রোটেকশন（maxStrLen） |

---

## ছয়、উপসংহার

সব 6টি সিকিউরিটি সমস্যা ফিক্স করা হয়েছে। admin এন্ডের ডিফেন্স 5 লেয়ার থেকে 10 লেয়ার গ্লোবাল মিডলওয়্যারে বাড়ানো হয়েছে, সিকিউরিটি রেসপন্স হেডার、রেট লিমিট、SQL ইনজেকশন ডিটেকশন、ইনপুট ক্লিনিং、CORS — 5টি গুরুত্বপূর্ণ প্রোটেকশন পূরণ হয়েছে। JWT token-এ IP/UA বাইন্ডিং ভ্যালিডেশন যোগ হয়েছে। ReDoS ঝুঁকি এবং .env পাসওয়ার্ড স্পেশাল ক্যারেক্টার সমস্যা দূর হয়েছে। সব ফাইল PHP সিনট্যাক্স চেক পাস করেছে।
