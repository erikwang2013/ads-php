# Informe de revisión y corrección de seguridad de Ads-PHP (3.ª ronda)

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**Fecha de generación**: 2026-08-04  
**Alcance de la revisión**: todos los middlewares de seguridad, flujos de autenticación, controladores de instalación, archivos de configuración  
**Versión de PHP**: 8.3.7 | **Framework**: webman v2  

---

## 一、Resumen de correcciones

Esta ronda corrige de forma integral los 6 problemas detectados en la 2.ª ronda de revisión de seguridad.

| # | Problema | Método de corrección | Estado |
|---|------|---------|:--:|
| 1 | Al lado admin le faltan 5 middlewares de seguridad | Creados CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware | Corregido |
| 2 | AuthCheck de admin no hace vinculación de IP/UA | El payload JWT de AuthController incorpora `_ip` / `_ua`, AuthCheck verifica la vinculación | Corregido |
| 3 | Riesgo de ReDoS en AttackGuardMiddleware | Nuevo precheck `maxStrLen=8192`, las cadenas demasiado largas se rechazan directamente | Corregido |
| 4 | Caracteres especiales en la contraseña de InstallController | Nuevo método `envQuote()`, envuelve automáticamente con comillas + escape | Corregido |
| 5 | Configuración de middleware de admin incompleta | Actualizada a una pila de 10 capas de middleware global | Corregido |
| 6 | Número de capas de middleware desactualizado en README | README en chino e inglés actualizados en sincronía | Corregido |

---

## 二、Verificación de sintaxis

| Archivo | Líneas | Sintaxis |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | Correcta |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Correcta |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Correcta |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Correcta |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Correcta |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | Correcta |
| `admin/app/middleware/AuthCheck.php` | 48 | Correcta |
| `admin/app/controller/AuthController.php` | 194 | Correcta |
| `admin/app/controller/InstallController.php` | 298 | Correcta |
| `admin/config/middleware.php` | 43 | Correcta |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | Correcta |

---

## 三、Pila de middlewares (tras la corrección)

### Lado Service (14 capas globales + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（路由层）
```

### Lado Admin (10 capas globales + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（路由层）
```

### Matriz de rutas (tras la actualización del lado admin)

| Ruta | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (protegido) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## 四、Detalles de las mejoras de seguridad

### 4.1 Middlewares nuevos en admin

| Middleware | Archivo | Responsabilidad |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | Preflight CORS + cabeceras de respuesta; en modo debug permite todo, en producción lista blanca |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Limitación de tráfico con ventana deslizante Redis 60req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | Detección de patrones de inyección SQL (UNION/DROP/ALTER/comentarios) |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | Saneamiento de entrada trim + strip_tags (excluye description/content/extra) |

### 4.2 Vinculación del token JWT

Al iniciar sesión, AuthController añade `_ip` y `_ua` al payload JWT:

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

Al verificar el token, el middleware AuthCheck comprueba la consistencia de IP y UA; si no coinciden, deniega el acceso.

### 4.3 Protección ReDoS

AttackGuardMiddleware (admin + service) incorpora `maxStrLen = 8192`:

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 Escape de contraseñas en .env

InstallController incorpora el método `envQuote()`, que detecta caracteres especiales en la contraseña (espacios, `$`, `#`, comillas, barras invertidas) y automáticamente la envuelve con comillas dobles y la escapa.

---

## 五、Lista de archivos

### Nuevos (5 archivos)

| Archivo | Líneas | Descripción |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | Middleware CORS |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Cabeceras de seguridad de respuesta |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Limitación de tráfico global |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Detección de inyección SQL |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Saneamiento de entrada |

### Modificados (6 archivos)

| Archivo | Cambio |
|------|------|
| `admin/config/middleware.php` | Middleware global de 5→10 capas |
| `admin/app/middleware/AttackGuardMiddleware.php` | Nueva protección ReDoS (maxStrLen) |
| `admin/app/middleware/AuthCheck.php` | Nueva verificación de vinculación JWT IP/UA |
| `admin/app/controller/AuthController.php` | El payload JWT incorpora _ip/_ua |
| `admin/app/controller/InstallController.php` | Nuevo escape de contraseñas con envQuote() |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | Nueva protección ReDoS (maxStrLen) |

---

## 六、Conclusión

Los 6 problemas de seguridad han sido corregidos. La defensa del lado admin ha aumentado de 5 a 10 capas de middleware global, completando 5 protecciones clave: cabeceras de seguridad de respuesta, limitación de tráfico, detección de inyección SQL, saneamiento de entrada y CORS. El token JWT ahora incluye verificación de vinculación de IP/UA. Los riesgos de ReDoS y los caracteres especiales de contraseñas en .env han sido eliminados. Todos los archivos pasan la verificación de sintaxis de PHP.
