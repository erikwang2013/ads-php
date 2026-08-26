# تقرير المراجعة الأمنية والإصلاح لـ Ads-PHP (الجولة الثالثة)

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**وقت الإنشاء**: 2026-08-04  
**نطاق المراجعة**: جميع الوسائط الأمنية، تدفق المصادقة، وحدة تحكم التثبيت، ملفات التكوين  
**إصدار PHP**: 8.3.7 | **الإطار**: webman v2  

---

## أولاً: نظرة عامة على الإصلاحات

عالجت هذه الجولة بشكل شامل المشكلات الست التي كشفتها المراجعة الأمنية للجولة الثانية.

| # | المشكلة | طريقة الإصلاح | الحالة |
|---|------|---------|:--:|
| 1 | نقص 5 وسائط أمنية في طرف admin | إنشاء CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware | تم الإصلاح |
| 2 | AuthCheck في admin لا يربط IP/UA | إضافة `_ip` / `_ua` إلى حمولة JWT في AuthController، وربط التحقق في AuthCheck | تم الإصلاح |
| 3 | مخاطرة ReDoS في AttackGuardMiddleware | إضافة فحص مسبق `maxStrLen=8192`، ورفض السلاسل الطويلة جدًا مباشرة | تم الإصلاح |
| 4 | الأحرف الخاصة في كلمات مرور InstallController | إضافة دالة `envQuote()`، تغليف تلقائي بعلامات اقتباس + إفلات | تم الإصلاح |
| 5 | تكوين وسائط admin غير مكتمل | التحديث إلى كومة وسائط عامة من 10 طبقات | تم الإصلاح |
| 6 | عدد طبقات الوسائط في README قديم | تحديث متزامن لـ README بالصينية والإنجليزية | تم الإصلاح |

---

## ثانيًا: التحقق من الصياغة

| الملف | عدد الأسطر | الصياغة |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | ناجحة |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | ناجحة |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | ناجحة |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | ناجحة |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | ناجحة |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | ناجحة |
| `admin/app/middleware/AuthCheck.php` | 48 | ناجحة |
| `admin/app/controller/AuthController.php` | 194 | ناجحة |
| `admin/app/controller/InstallController.php` | 298 | ناجحة |
| `admin/config/middleware.php` | 43 | ناجحة |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | ناجحة |

---

## ثالثًا: كومة الوسائط (بعد الإصلاح)

### طرف Service (14 طبقة عامة + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（路由层）
```

### طرف Admin (10 طبقة عامة + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（路由层）
```

### مصفوفة المسارات (بعد تحديث طرف admin)

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

## رابعًا: تفاصيل التحسينات الأمنية

### 4.1 وسائط admin الجديدة

| الوسيط | الملف | المسؤولية |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | فحص CORS المسبق + ترويسات الاستجابة، تمرير الكل في وضع debug، قائمة بيضاء في الإنتاج |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | تقييد معدل بنافذة منزلقة عبر Redis 60req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | كشف أنماط حقن SQL (UNION/DROP/ALTER/رموز التعليق) |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | تقليم المدخلات + strip_tags (باستثناء description/content/extra) |

### 4.2 ربط JWT Token

يضيف AuthController `_ip` و`_ua` إلى حمولة JWT عند تسجيل الدخول:

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

يتحقق وسيط AuthCheck عند التحقق من token من تطابق IP وUA، ويرفض الوصول عند عدم التطابق.

### 4.3 حماية ReDoS

أضاف AttackGuardMiddleware (admin + service) `maxStrLen = 8192`:

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 إفلات كلمات مرور .env

أضاف InstallController دالة `envQuote()`، تكتشف الأحرف الخاصة في كلمات المرور (مسافات، `$`، `#`، علامات اقتباس، شرطة مائلة عكسية)، وتلتف تلقائيًا بعلامات اقتباس مزدوجة وتُفلت.

---

## خامسًا: قائمة الملفات

### الجديدة (5 ملفات)

| الملف | عدد الأسطر | الوصف |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | وسيط CORS |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | ترويسات استجابة أمنية |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | تقييد معدل عام |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | كشف حقن SQL |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | تنظيف المدخلات |

### المعدلة (6 ملفات)

| الملف | التغيير |
|------|------|
| `admin/config/middleware.php` | الوسائط العامة من 5 إلى 10 طبقات |
| `admin/app/middleware/AttackGuardMiddleware.php` | إضافة حماية ReDoS (maxStrLen) |
| `admin/app/middleware/AuthCheck.php` | إضافة ربط تحقق IP/UA في JWT |
| `admin/app/controller/AuthController.php` | إضافة _ip/_ua إلى حمولة JWT |
| `admin/app/controller/InstallController.php` | إضافة إفلات كلمات المرور envQuote() |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | إضافة حماية ReDoS (maxStrLen) |

---

## سادسًا: الخلاصة

تم إصلاح جميع المشكلات الأمنية الست. ارتفعت حماية طرف admin من 5 طبقات إلى 10 طبقات وسائط عامة، مع سد الفجوات الخمس الحرجة: ترويسات الاستجابة الأمنية، وتقييد المعدل، وكشف حقن SQL، وتنظيف المدخلات، وCORS. أُضيف ربط تحقق IP/UA إلى JWT token. أُزيلت مخاطرة ReDoS ومشكلة الأحرف الخاصة في كلمات مرور .env. جميع الملفات اجتازت فحص صياغة PHP.
