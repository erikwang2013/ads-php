# Rapport d'audit de sécurité et de corrections Ads-PHP (3e cycle)

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**Généré le** : 2026-08-04
**Périmètre de l'audit** : tous les middlewares de sécurité, le flux d'authentification, le contrôleur d'installation, les fichiers de configuration
**Version PHP** : 8.3.7 | **Framework** : webman v2

---

## I. Aperçu des corrections

Ce cycle a corrigé en profondeur les 6 problèmes découverts lors du 2e cycle d'audit de sécurité.

| # | Problème | Correctif | Statut |
|---|------|---------|:--:|
| 1 | 5 middlewares de sécurité manquants côté admin | Création de CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware | Corrigé |
| 2 | AuthCheck admin sans liaison IP/UA | Ajout de `_ip` / `_ua` dans le payload JWT d'AuthController, AuthCheck vérifie la liaison | Corrigé |
| 3 | Risque ReDoS dans AttackGuardMiddleware | Ajout d'une pré-vérification `maxStrLen=8192`, les chaînes trop longues sont rejetées directement | Corrigé |
| 4 | Caractères spéciaux du mot de passe dans InstallController | Ajout de la méthode `envQuote()`, mise entre guillemets et échappement automatiques | Corrigé |
| 5 | Configuration des middlewares admin incomplète | Mise à jour vers une pile de 10 couches globales de middlewares | Corrigé |
| 6 | Nombre de couches de middlewares obsolète dans le README | Mise à jour synchronisée des README chinois et anglais | Corrigé |

---

## II. Vérification de la syntaxe

| Fichier | Lignes | Syntaxe |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | OK |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | OK |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | OK |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | OK |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | OK |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | OK |
| `admin/app/middleware/AuthCheck.php` | 48 | OK |
| `admin/app/controller/AuthController.php` | 194 | OK |
| `admin/app/controller/InstallController.php` | 298 | OK |
| `admin/config/middleware.php` | 43 | OK |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | OK |

---

## III. Pile de middlewares (après correction)

### Côté Service (14 couches globales + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（路由层）
```

### Côté Admin (10 couches globales + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（路由层）
```

### Matrice des routes (côté admin, après mise à jour)

| Route | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (protégé) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## IV. Détails des améliorations de sécurité

### 4.1 Nouveaux middlewares admin

| Middleware | Fichier | Rôle |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | Pré-vol CORS + en-têtes de réponse, autorisation totale en mode debug, liste blanche en production |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Limitation par fenêtre glissante Redis 60 req/60 s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | Détection des modèles d'injection SQL (UNION/DROP/ALTER/commentaires) |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | trim + strip_tags des entrées (hors description/content/extra) |

### 4.2 Liaison du jeton JWT

AuthController ajoute `_ip` et `_ua` dans le payload JWT lors de la connexion :

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

Le middleware AuthCheck vérifie la cohérence IP et UA lors de la validation du jeton, et refuse l'accès en cas d'incohérence.

### 4.3 Protection ReDoS

AttackGuardMiddleware (admin + service) a ajouté `maxStrLen = 8192` :

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 Échappement du mot de passe .env

InstallController a ajouté la méthode `envQuote()`, qui détecte les caractères spéciaux du mot de passe (espaces, `$`, `#`, guillemets, barre oblique inverse) et les met automatiquement entre guillemets doubles avec échappement.

---

## V. Liste des fichiers

### Ajoutés (5 fichiers)

| Fichier | Lignes | Description |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | Middleware CORS |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | En-têtes de réponse de sécurité |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Limitation globale |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Détection d'injection SQL |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Nettoyage des entrées |

### Modifiés (6 fichiers)

| Fichier | Modification |
|------|------|
| `admin/config/middleware.php` | Middlewares globaux 5→10 couches |
| `admin/app/middleware/AttackGuardMiddleware.php` | Ajout de la protection ReDoS (maxStrLen) |
| `admin/app/middleware/AuthCheck.php` | Ajout de la validation de liaison JWT IP/UA |
| `admin/app/controller/AuthController.php` | Ajout de _ip/_ua dans le payload JWT |
| `admin/app/controller/InstallController.php` | Ajout de l'échappement de mot de passe envQuote() |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | Ajout de la protection ReDoS (maxStrLen) |

---

## VI. Conclusion

Les 6 problèmes de sécurité ont tous été corrigés. La défense côté admin est passée de 5 à 10 couches globales de middlewares, complétant 5 protections clés : en-têtes de réponse de sécurité, limitation, détection d'injection SQL, nettoyage des entrées et CORS. Le jeton JWT a ajouté la validation de liaison IP/UA. Les risques ReDoS et les caractères spéciaux du mot de passe .env ont été éliminés. Tous les fichiers passent la vérification de syntaxe PHP.
