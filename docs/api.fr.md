# Documentation des interfaces API

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **Documentation en ligne hg/apidoc** : après le démarrage du service, accéder à `http://127.0.0.1:8788/apidoc` (bascule entre les deux applications Service + Admin)
> Fichier de configuration : `service/config/plugin/hg/apidoc/app.php`

---

## Règles générales

### Base URL

```
http://your-domain.com/api
```

### Headers requis

| Header | Valeur | Description |
|--------|----|------|
| `X-API-Version` | `v1` | Numéro de version de l'API (obligatoire, n'apparaît pas dans le chemin URL) |
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | Plateforme d'origine de l'opération (obligatoire) |
| `Authorization` | `Bearer <token>` | Jeton d'authentification JWT (obligatoire sauf connexion/liste des plateformes/vérification de santé) |

### Header anti-rejeu (côté non-navigateur)

| Header | Description |
|--------|------|
| `X-Nonce` | Chaîne aléatoire (unique par requête) |
| `X-Timestamp` | Horodatage Unix en secondes (fenêtre de ±5 minutes) |

### Headers optionnels

| Header | Description |
|--------|------|
| `X-Tenant-Id` | ID du locataire (mode multi-locataires) |
| `X-Encrypted` | `1` = le corps de la requête doit être déchiffré, le corps de la réponse chiffré |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| Valeur | Description |
|----|------|
| `application/json` | Corps de requête JSON (recommandé) |
| `application/x-www-form-urlencoded` | Requête de formulaire |
| `multipart/form-data` | Upload de fichier |

### Format de réponse

**Succès** :
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**Pagination** :
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [ ... ],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

**Erreur** :
```json
{ "code": 401, "message": "Unauthorized" }
```

**Vérification de santé** :
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### Codes de statut HTTP

| Statut | Signification |
|--------|------|
| 200 | Succès |
| 204 | Pré-vol OPTIONS réussi |
| 400 | Erreur de paramètres de requête, version API non prise en charge |
| 401 | Non authentifié, jeton expiré, jeton IP/UA ne correspondant pas |
| 403 | Accès interdit (XSS/traversée de chemin/CSRF/injection SQL/Origin ne correspondant pas) |
| 404 | Ressource inexistante |
| 429 | Trop de requêtes (limitation/connexion/sessions simultanées) |
| 500 | Erreur serveur |
| 503 | Service dégradé (DB ou Redis indisponible) |

### Paramètres de pagination

| Paramètre | Valeur par défaut | Maximum | Description |
|------|--------|--------|------|
| `page` | 1 | — | Numéro de page |
| `per_page` | 20 | 100 | Nombre d'éléments par page (tronqué automatiquement au-delà) |
| `sort` | `id` | — | Champ de tri (doit être dans la liste blanche) |

### Stratégie de cache

| Point de terminaison | TTL | Couche |
|------|-----|-----|
| `/api/platforms` | 1 heure | L1 mémoire → L2 APCu → L3 Redis |
| `/api/accounts` + `/api/accounts/:id` | 5 minutes | idem |
| `/api/reports/summary` | 5 minutes | idem |
| `/api/alerts/rules` | 2 minutes | idem |
| `/api/alerts/unread-count` | 30 secondes | idem |

---

## Module 1 : Système

### GET /health — Vérification de santé

```
GET /health
```

**Réponse** :
```json
{
  "status": "healthy",
  "timestamp": "2026-05-22T00:00:00+08:00",
  "checks": {
    "database": "ok",
    "redis": "ok"
  }
}
```

- `status` : `healthy` (200) ou `degraded` (503)
- Aucune exigence d'authentification, ne passe pas par le routage de version

---

### GET /ping — Test de présence

```
GET /ping
```

**Réponse** : `{ "pong": true }`

---

### GET /docs — Documentation API

```
GET /docs
```

Renvoie la page de documentation API au format HTML (sans authentification).

---

### GET /api/captcha/generate — Générer un captcha

Sans authentification.

**Réponse** :
```json
{
  "code": 0,
  "data": {
    "captcha_token": "aes-encrypted-token",
    "background": "base64...",
    "puzzle": "base64..."
  }
}
```

- Le jeton est valable 5 minutes
- Tolérance de décalage 5 px

---

### POST /api/captcha/verify — Vérifier le captcha

Sans authentification.

**Requête** :
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**Réponse** : `{ "code": 0, "message": "验证通过" }`

---

## Module 2 : Authentification

### POST /api/auth/login — Connexion

Sans authentification.

**Requête** :
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**Réponse** :
```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "username": "admin",
      "name": "超级管理员",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

- Le jeton JWT est valable 24 heures
- Le jeton embarque le hash IP + User-Agent
- 5 échecs → verrouillage Redis 15 minutes

---

### GET /api/auth/me — Utilisateur courant

**En-tête** : `Authorization: Bearer <token>`

**Réponse** :
```json
{
  "code": 0,
  "data": {
    "id": 1,
    "username": "admin",
    "name": "超级管理员",
    "email": "admin@example.com",
    "role": "admin",
    "tenant_id": 1
  }
}
```

---

### POST /api/auth/refresh — Rafraîchir le jeton

**En-tête** : `Authorization: Bearer <old_token>`

**Réponse** :
```json
{
  "code": 0,
  "message": "Token 已刷新",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

- L'ancien jeton est automatiquement ajouté à la liste noire
- Maximum 3 jetons actifs par utilisateur

---

## Module 3 : Plateformes et comptes

### GET /api/platforms — Liste des plateformes

Sans authentification. Cache 1 heure.

**Réponse** :
```json
{
  "code": 0,
  "data": [
    { "code": "juliang", "name": "巨量引擎", "flag": "🇨🇳", "capabilities": ["campaign", "report"] },
    { "code": "meta", "name": "Meta Ads", "flag": "🇺🇸", "capabilities": ["campaign", "report"] }
  ]
}
```

---

### GET /api/platforms/:code/oauth-url — URL d'autorisation OAuth

**Paramètres** : `?redirect_uri=https://your-domain.com/callback`

**Réponse** : `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` doit passer la validation de la liste blanche SSRF (variable d'environnement `OAUTH_ALLOWED_REDIRECTS`)

---

### POST /api/platforms/:code/callback — Callback OAuth

**Requête** : `{ "state": "...", "code": "..." }`

**Réponse** : `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/accounts — Liste des comptes

Cache 5 minutes.

**Paramètres** :

| Paramètre | Description |
|------|------|
| `platform` | Filtre par code de plateforme |
| `page` | Numéro de page |
| `per_page` | Nombre d'éléments par page |

**Réponse** : format paginé, chaque élément de la liste contient `id` (hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at`

---

### GET /api/accounts/:id — Détail du compte

Cache 5 minutes.

---

### DELETE /api/accounts/:id — Délier le compte

---

### POST /api/accounts/:id/sync — Synchronisation manuelle

---

## Module 4 : Plans publicitaires

### GET /api/campaigns — Liste des plans

**Paramètres** :

| Paramètre | Description | Valeurs possibles |
|------|------|--------|
| `platform` | Filtre par plateforme | juliang, meta, google... |
| `status` | Filtre par statut | enabled, paused |
| `keyword` | Recherche par nom | Texte libre |
| `sort` | Champ de tri | id, name, platform, daily_budget, status, created_at |
| `page` | Numéro de page | — |
| `per_page` | Nombre d'éléments par page | ≤100 |

**Réponse** : format paginé + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/campaigns — Créer un plan

**Requête** :
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**Réponse** : `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- `daily_budget` en centimes (20000 = ¥200.00)

---

### GET /api/campaigns/:id — Détail du plan

**Réponse** : `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/campaigns/:id — Mettre à jour le plan

**Requête** : `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/campaigns/:id/toggle — Activer/pauser le plan

**Requête** : `{ "enabled": false }`

---

### POST /api/campaigns/batch/toggle — Activer/pauser en masse

**Requête** : `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**Réponse** : `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## Module 5 : Groupes d'annonces

### GET /api/ad-groups — Liste des groupes d'annonces

**Paramètres** : `platform`, `campaign_id`, `status`, `sort` (id/name/status/bid_amount), `page`, `per_page`

### POST /api/ad-groups — Créer un groupe d'annonces

**Requête** :
```json
{
  "campaign_id": 1,
  "name": "测试广告组",
  "bid_amount": 100,
  "bid_type": "cpc",
  "targeting": { "age": { "min": 18, "max": 45 } },
  "targeting_template_id": "hashids-encoded-template-id"
}
```

- `targeting_template_id` : optionnel, charge le JSON de ciblage depuis le modèle de ciblage et le fusionne

### GET /api/ad-groups/:id — Détail du groupe d'annonces

### PUT /api/ad-groups/:id — Mettre à jour le groupe d'annonces

### POST /api/ad-groups/:id/toggle — Activer/pauser le groupe d'annonces

---

## Module 6 : Créations

### GET /api/creatives — Liste des créations

**Paramètres** : `platform`, `ad_group_id`, `campaign_id`, `media_type` (image/video/text), `sort`, `page`, `per_page`

### GET /api/creatives/:id — Détail de la création

---

## Module 7 : Rapports

### GET /api/reports/summary — Récapitulatif du tableau de bord

Cache 5 minutes.

**Paramètres** : `date_start`, `date_end`

**Réponse** :
```json
{
  "code": 0,
  "data": {
    "overview": { "cost": 123456, "impressions": 10000, ... },
    "by_platform": [ ... ],
    "daily": [ ... ]
  }
}
```

---

### GET /api/reports/custom — Rapport personnalisé

**Paramètres** :

| Paramètre | Description |
|------|------|
| `dimensions[]` | Dimensions : date, platform, campaign |
| `metrics[]` | Indicateurs : cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | Date de début |
| `date_end` | Date de fin |
| `platform` | Filtre par plateforme |

---

### GET /api/reports/export — Exporter le rapport

**Paramètres** : `format=csv`, `date_start`, `date_end`, `metrics[]`

Renvoie un téléchargement de fichier (CSV UTF-8 BOM ou Excel .xls).

---

### GET /api/reports/export-dashboard — Exporter le tableau de bord en PDF

---

### GET /api/reports/calendar — Calendrier de diffusion

**Paramètres** : `date_start`, `date_end`, `platform`

**Réponse** : `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/reports/budget-alerts — Alertes de budget

**Réponse** : `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level` : yellow (≥50 %), orange (≥80 %), red (≥100 %)

---

### GET /api/reports/attribution — Analyse d'attribution

**Paramètres** : `model` (first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**Réponse** :
```json
{
  "code": 0,
  "data": {
    "total_conversions": 42,
    "total_value": 123456.78,
    "by_campaign": [ { "campaign_id": 1, "credit": 5000.00 } ]
  }
}
```

---

### GET /api/reports/attribution/models — Liste des modèles d'attribution

**Réponse** : `[{ code: "last_touch", name: "末次触点", description: "..." }]`

5 modèles au total.

---

## Module 8 : Alertes

### GET /api/alerts/rules — Liste des règles d'alerte

Cache 2 minutes.

**Paramètres** : `platform`, `enabled` (0/1), `metric`, `page`, `per_page`

### POST /api/alerts/rules — Créer une règle d'alerte

**Requête** :
```json
{
  "name": "花费超限",
  "metric": "cost",
  "condition": "gt",
  "threshold": 100000,
  "scope": "tenant",
  "platform": null,
  "campaign_id": null,
  "channels": ["web"]
}
```

### PUT /api/alerts/rules/:id — Mettre à jour la règle d'alerte

### DELETE /api/alerts/rules/:id — Supprimer la règle d'alerte

### GET /api/alerts/logs — Journaux d'alerte

**Paramètres** : `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/alerts/logs/:id/acknowledge — Confirmer l'alerte

### GET /api/alerts/unread-count — Nombre d'alertes non lues

Cache 30 secondes. Interrogation toutes les 30 s par le frontend.

---

## Module 9 : Notifications

### GET /api/notifications — Liste des notifications

**Paramètres** : `type` (alert/system), `is_read` (0/1), `page`, `per_page`

### GET /api/notifications/unread-count — Nombre de notifications non lues

### POST /api/notifications/:id/read — Marquer comme lue

### POST /api/notifications/read-all — Tout marquer comme lu

---

## Module 10 : Enchères automatiques

### GET /api/bid-rules — Liste des règles

### POST /api/bid-rules — Créer une règle

**Requête** :
```json
{
  "name": "ROI 达标加预算",
  "metric": "roi",
  "condition": "gte",
  "threshold": 3.0,
  "action_type": "adjust_budget",
  "adjust_step": 5000,
  "budget_min": 0,
  "budget_max": 100000,
  "cooldown_minutes": 60
}
```

**Description des champs** :

| Champ | Type | Description |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Indicateur surveillé |
| condition | gt/gte/lt/lte | Condition de déclenchement |
| threshold | decimal | Seuil |
| action_type | adjust_budget/toggle_pause/toggle_enable | Type d'action |
| adjust_step | int (centimes) | Pas d'ajustement du budget (positif = augmentation, négatif = réduction) |
| budget_min | int | Budget minimum (centimes) |
| budget_max | int | Budget maximum (centimes) |
| cooldown_minutes | int | Temps de refroidissement (par défaut 60) |

### PUT /api/bid-rules/:id — Mettre à jour la règle

### DELETE /api/bid-rules/:id — Supprimer la règle

### GET /api/bid-rules/logs — Historique des enchères

**Paramètres** : `rule_id`, `campaign_id`

---

## Module 11 : Modèles de ciblage

### GET /api/targeting-templates — Liste des modèles

**Paramètres** : `platform`

### GET /api/targeting-templates/:id — Détail du modèle

### POST /api/targeting-templates — Créer un modèle

**Requête** :
```json
{
  "name": "核心受众",
  "platform": "",
  "targeting": {
    "age": { "min": 18, "max": 45 },
    "gender": "all",
    "interests": ["sports", "tech"],
    "devices": { "os": ["android", "ios"] }
  },
  "is_shared": 0
}
```

### PUT /api/targeting-templates/:id — Mettre à jour le modèle

### DELETE /api/targeting-templates/:id — Supprimer le modèle

---

## Module 12 : Bibliothèque de ressources

### GET /api/assets — Liste des ressources

**Paramètres** : `type` (image/video), `page`, `per_page`

### POST /api/assets/upload — Uploader une ressource

**Requête** : `multipart/form-data`, champ `file`

- Image : maximum 5 Mo (jpeg/png/gif/webp)
- Vidéo : maximum 50 Mo (mp4)

**Réponse** : `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

- Avec CDN configuré, `url` est assemblé avec le `cdn_domain` du fournisseur par défaut en une adresse HTTPS complète

### POST /api/assets/presign — Obtenir une URL de téléversement pré-signée

**Requête** : `{ "filename": "demo.mp4", "mime_type": "video/mp4" }`

**Réponse** : `{ "code": 0, "data": { "key": "20260829/ab12...cd34.mp4", "upload_url": "https://signed-url", "expires_in": 3600, "url": "https://cdn.example.com/uploads/assets/..." } }`

- Format `key` : `Ymd/32hex.extension` ; à renvoyer à `/api/assets/register` après téléversement direct
- Pour les vidéos jusqu'à 50 Mio, le client téléverse directement dans le stockage objet ; indisponible avec le driver `local`

### POST /api/assets/register — Enregistrer une ressource téléversée directement

**Requête** : `{ "key": "20260829/ab12...cd34.mp4", "filename": "demo.mp4", "mime_type": "video/mp4", "size": 52428800 }`

**Réponse** : `{ "code": 0, "message": "Created", "data": { "id": "hashids", "url": "https://cdn.example.com/uploads/assets/...", "type": "video" } }`

- `key` strictement validé (`^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$`) contre le path traversal

### GET /api/assets/:id — Détail de la ressource

### DELETE /api/assets/:id — Supprimer la ressource

---

## Points de terminaison Admin (port 8789)

### POST /api/admin/login — Connexion administrateur

**Requête** : `{ "username": "admin", "password": "..." }`

**Réponse** : `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Le jeton est stocké dans localStorage
- `csrf_token` doit être transporté dans l'en-tête `X-CSRF-Token` des requêtes POST/PUT/DELETE suivantes

### GET /api/admin/me — Administrateur courant

### POST /api/admin/logout — Déconnexion

### GET /api/admin/users — Liste des utilisateurs

**Paramètres** : `keyword`, `role_id`, `page`, `per_page`

Les `id` et `role_id` de la réponse sont encodés avec hashids.

### POST /api/admin/users — Créer un utilisateur

### PUT /api/admin/users/:id — Mettre à jour un utilisateur

### DELETE /api/admin/users/:id — Désactiver un utilisateur

### GET /api/admin/users/roles — Liste des rôles

### GET /api/admin/audit-logs — Journaux d'audit

**Paramètres** : `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

### Gestion des fournisseurs CDN (locataire principal uniquement tenant 1, AdminMiddleware)

### GET /api/admin/cdn/providers — Liste des fournisseurs

### POST /api/admin/cdn/providers — Créer un fournisseur

**Requête** : `{ "name": "Aliyun OSS", "driver": "oss", "bucket": "ads-assets", "region": "oss-cn-hangzhou", "endpoint": "https://oss-cn-hangzhou.aliyuncs.com", "access_key": "...", "secret_key": "...", "cdn_domain": "cdn.example.com", "cdn_driver": "aliyun", "cdn_token": "...", "is_default": 1 }`

- `driver` : `local` / `oss` (Alibaba Cloud OSS) / `cos` (Tencent Cloud COS, protocole S3) / `s3` (compatible S3 : AWS S3 / Cloudflare R2 / MinIO)
- Identifiants (access_key/secret_key/cdn_token) chiffrés champ par champ via Encryptable ; réponses avec champs masqués uniquement

### PUT /api/admin/cdn/providers/:id — Modifier un fournisseur

### DELETE /api/admin/cdn/providers/:id — Supprimer (le défaut passe automatiquement au fournisseur enabled suivant)

### PUT /api/admin/cdn/providers/:id/default — Définir par défaut

### PUT /api/admin/cdn/providers/:id/toggle — Activer/Désactiver (le défaut est transféré automatiquement)

### POST /api/admin/cdn/providers/:id/test — Test de connectivité

**Réponse** : `{ "code": 0, "data": { "ok": true, "driver": "oss", "status": "ok" } }`

### POST /api/admin/cdn/providers/:id/purge — Purge du cache CDN

**Requête** : `{ "paths": ["/uploads/assets/20260829/xxx.mp4"] }`

- Nécessite `cdn_driver` et `cdn_domain` ; `aliyun` réellement implémenté (signature OpenAPI), cloudflare/cloudfront à venir

---

## Référence des codes d'erreur

| code | HTTP | Description |
|------|------|------|
| 0 | 200 | Succès |
| 1 | 200/400 | Erreur métier générique |
| 401 | 401 | Non authentifié / jeton expiré / IP-UA ne correspondant pas |
| 403 | 403 | Accès interdit (interception de sécurité) |
| 404 | 404 | Ressource inexistante |
| 422 | 422 | Échec de la validation des paramètres |
| 429 | 429 | Trop de requêtes / limitation de connexion / limite de concurrence |
| 1001 | 200 | Échec d'authentification (nom d'utilisateur ou mot de passe incorrect) |

---

## Réponses d'interception de sécurité

Lorsqu'une requête est interceptée par un middleware de sécurité, un code 403 est renvoyé :

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## Réponse de limitation

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

L'en-tête `Retry-After` contient le nombre de secondes restantes.
