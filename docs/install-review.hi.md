# Ads-PHP सुरक्षा समीक्षा और सुधार रिपोर्ट (राउंड 3)

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**निर्माण समय**: 2026-08-04  
**समीक्षा दायरा**: सभी सुरक्षा मिडलवेयर, प्रमाणीकरण प्रवाह, इंस्टॉलेशन कंट्रोलर, कॉन्फ़िगरेशन फ़ाइलें  
**PHP वर्शन**: 8.3.7 | **फ्रेमवर्क**: webman v2  

---

## 一、सुधार अवलोकन

इस राउंड में दूसरे राउंड की सुरक्षा समीक्षा में पाए गए 6 समस्याओं का पूर्ण सुधार किया गया।

| # | समस्या | सुधार विधि | स्थिति |
|---|------|---------|:--:|
| 1 | admin एंड में 5 सुरक्षा मिडलवेयर की कमी | CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware नए बनाए | 已修复 |
| 2 | admin AuthCheck IP/UA बाइंडिंग नहीं करता | AuthController JWT payload में `_ip` / `_ua` जोड़ा गया, AuthCheck बाइंडिंग सत्यापित करता है | 已修复 |
| 3 | AttackGuardMiddleware ReDoS जोखिम | `maxStrLen=8192` प्री-चेक जोड़ा गया, बहुत लंबी स्ट्रिंग सीधे अस्वीकार | 已修复 |
| 4 | InstallController पासवर्ड विशेष वर्ण | `envQuote()` मेथड जोड़ा गया, स्वचालित उद्धरण + एस्केपिंग | 已修复 |
| 5 | admin मिडलवेयर कॉन्फ़िगरेशन अधूरा | 10-परत ग्लोबल मिडलवेयर स्टैक में अपडेट | 已修复 |
| 6 | README मिडलवेयर परत संख्या पुरानी | चीनी और अंग्रेज़ी README सिंक-अपडेट | 已修复 |

---

## 二、सिंटैक्स सत्यापन

| फ़ाइल | पंक्तियाँ | सिंटैक्स |
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

## 三、मिडलवेयर स्टैक (सुधार के बाद)

### Service एंड (14 ग्लोबल परतें + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（路由层）
```

### Admin एंड (10 ग्लोबल परतें + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（路由层）
```

### रूट मैट्रिक्स (admin एंड अपडेट के बाद)

| रूट | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (संरक्षित) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## 四、सुरक्षा सुधार विवरण

### 4.1 admin नए मिडलवेयर

| मिडलवेयर | फ़ाइल | ज़िम्मेदारी |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | CORS प्रीफ़्लाइट + प्रतिक्रिया हेडर, debug मोड में सब कुछ अनुमत, प्रोडक्शन व्हाइटलिस्ट |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Redis स्लाइडिंग विंडो रेट-लिमिट 60req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | SQL इंजेक्शन पैटर्न डिटेक्शन (UNION/DROP/ALTER/कमेंट वर्ण) |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | इनपुट trim + strip_tags (description/content/extra को छोड़कर) |

### 4.2 JWT Token बाइंडिंग

AuthController लॉगिन पर JWT payload में `_ip` और `_ua` जोड़ता है:

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

AuthCheck मिडलवेयर token सत्यापित करते समय IP और UA की संगति जाँचता है, असंगत होने पर एक्सेस अस्वीकार करता है।

### 4.3 ReDoS सुरक्षा

AttackGuardMiddleware (admin + service) में `maxStrLen = 8192` जोड़ा गया:

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 .env पासवर्ड एस्केपिंग

InstallController में `envQuote()` मेथड जोड़ा गया, पासवर्ड में विशेष वर्णों (स्पेस, `$`, `#`, उद्धरण, बैकस्लैश) का पता लगाकर स्वचालित रूप से डबल उद्धरण में लपेटकर एस्केप करता है।

---

## 五、फ़ाइल सूची

### नई (5 फ़ाइलें)

| फ़ाइल | पंक्तियाँ | विवरण |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | CORS मिडलवेयर |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | सुरक्षा प्रतिक्रिया हेडर |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | ग्लोबल रेट-लिमिट |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | SQL इंजेक्शन डिटेक्शन |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | इनपुट सफ़ाई |

### संशोधित (6 फ़ाइलें)

| फ़ाइल | परिवर्तन |
|------|------|
| `admin/config/middleware.php` | ग्लोबल मिडलवेयर 5→10 परतें |
| `admin/app/middleware/AttackGuardMiddleware.php` | ReDoS सुरक्षा जोड़ी गई (maxStrLen) |
| `admin/app/middleware/AuthCheck.php` | JWT IP/UA बाइंडिंग सत्यापन जोड़ा गया |
| `admin/app/controller/AuthController.php` | JWT payload में _ip/_ua जोड़ा गया |
| `admin/app/controller/InstallController.php` | envQuote() पासवर्ड एस्केपिंग जोड़ी गई |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | ReDoS सुरक्षा जोड़ी गई (maxStrLen) |

---

## 六、निष्कर्ष

सभी 6 सुरक्षा समस्याएँ ठीक हो गईं। admin एंड की सुरक्षा 5 परतों से बढ़ाकर 10-परत ग्लोबल मिडलवेयर कर दी गई, जिसमें सुरक्षा प्रतिक्रिया हेडर, रेट-लिमिट, SQL इंजेक्शन डिटेक्शन, इनपुट सफ़ाई, CORS 5 प्रमुख सुरक्षाएँ जोड़ी गईं। JWT token में IP/UA बाइंडिंग सत्यापन जोड़ा गया। ReDoS जोखिम और .env पासवर्ड विशेष वर्ण समस्या समाप्त हो गई। सभी फ़ाइलें PHP सिंटैक्स जाँच पास करती हैं।
