# Ads-PHP Sicherheits-Review- und Reparaturbericht (Runde 3)

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**Erstellungszeit**: 2026-08-04  
**Prüfumfang**: Alle Sicherheits-Middleware, Authentifizierungsabläufe, Installations-Controller, Konfigurationsdateien  
**PHP-Version**: 8.3.7 | **Framework**: webman v2

---

## 1. Reparaturübersicht

In dieser Runde wurden die 6 in der zweiten Sicherheitsprüfungsrunde entdeckten Probleme umfassend behoben.

| # | Problem | Lösungsweg | Status |
|---|------|---------|:--:|
| 1 | Admin-Seite fehlen 5 Sicherheits-Middleware | Neu erstellt: CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware | Behoben |
| 2 | admin AuthCheck ohne IP/UA-Bindung | AuthController fügt JWT-Payload `_ip` / `_ua` hinzu, AuthCheck prüft die Bindung | Behoben |
| 3 | AttackGuardMiddleware ReDoS-Risiko | Neue `maxStrLen=8192`-Vorprüfung, überlange Zeichenketten werden direkt abgelehnt | Behoben |
| 4 | InstallController Passwort-Sonderzeichen | Neue Methode `envQuote()`, automatisches Anführungszeichen + Escaping | Behoben |
| 5 | admin Middleware-Konfiguration unvollständig | Aktualisiert auf einen 10-stufigen globalen Middleware-Stack | Behoben |
| 6 | README-Middleware-Ebenen veraltet | README auf Chinesisch und Englisch synchron aktualisiert | Behoben |

---

## 2. Syntaxprüfung

| Datei | Zeilen | Syntax |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | Bestanden |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Bestanden |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Bestanden |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Bestanden |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Bestanden |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | Bestanden |
| `admin/app/middleware/AuthCheck.php` | 48 | Bestanden |
| `admin/app/controller/AuthController.php` | 194 | Bestanden |
| `admin/app/controller/InstallController.php` | 298 | Bestanden |
| `admin/config/middleware.php` | 43 | Bestanden |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | Bestanden |

---

## 3. Middleware-Stack (nach der Reparatur)

### Service-Seite (14 globale Ebenen + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（Routen-Ebene）
```

### Admin-Seite (10 globale Ebenen + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（Routen-Ebene）
```

### Routen-Matrix (Admin-Seite nach der Aktualisierung)

| Route | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (geschützt) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## 4. Details der Sicherheitsverbesserungen

### 4.1 Neue Middleware auf der Admin-Seite

| Middleware | Datei | Aufgabe |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | CORS-Preflight + Response-Header, im Debug-Modus alles erlauben, in Produktion Whitelist |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Redis-Gleitfenster-Limiting 60 req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | SQL-Injection-Mustererkennung (UNION/DROP/ALTER/Kommentarzeichen) |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | Eingabe trim + strip_tags (ausgenommen description/content/extra) |

### 4.2 JWT-Token-Bindung

Der AuthController fügt beim Login `_ip` und `_ua` in das JWT-Payload ein:

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

Die AuthCheck-Middleware prüft bei der Token-Verifizierung die Konsistenz von IP und UA; bei Abweichung wird der Zugriff verweigert.

### 4.3 ReDoS-Schutz

AttackGuardMiddleware (admin + service) hat neue `maxStrLen = 8192`:

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 .env-Passwort-Escaping

Der InstallController hat die neue Methode `envQuote()`: erkennt Sonderzeichen im Passwort (Leerzeichen, `$`, `#`, Anführungszeichen, Backslash) und umschließt sie automatisch mit doppelten Anführungszeichen samt Escaping.

---

## 5. Dateiliste

### Neu (5 Dateien)

| Datei | Zeilen | Beschreibung |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | CORS-Middleware |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Sicherheits-Response-Header |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Globales Rate-Limiting |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | SQL-Injection-Erkennung |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Eingabebereinigung |

### Geändert (6 Dateien)

| Datei | Änderung |
|------|------|
| `admin/config/middleware.php` | Globale Middleware 5→10 Ebenen |
| `admin/app/middleware/AttackGuardMiddleware.php` | Neuer ReDoS-Schutz (maxStrLen) |
| `admin/app/middleware/AuthCheck.php` | Neue JWT-IP/UA-Bindungsprüfung |
| `admin/app/controller/AuthController.php` | JWT-Payload mit _ip/_ua |
| `admin/app/controller/InstallController.php` | Neue envQuote()-Passwort-Escaping |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | Neuer ReDoS-Schutz (maxStrLen) |

---

## 6. Fazit

Alle 6 Sicherheitsprobleme wurden behoben. Die Abwehr der Admin-Seite wurde von 5 auf 10 globale Middleware-Ebenen ausgebaut; damit wurden die 5 wichtigsten Schutzmechanismen ergänzt: Sicherheits-Response-Header, Rate-Limiting, SQL-Injection-Erkennung, Eingabebereinigung und CORS. Das JWT-Token verfügt nun über eine IP/UA-Bindungsprüfung. Das ReDoS-Risiko und das Problem der Sonderzeichen in .env-Passwörtern wurden beseitigt. Alle Dateien bestehen die PHP-Syntaxprüfung.
