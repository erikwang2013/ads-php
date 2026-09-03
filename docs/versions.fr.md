# Comparaison des versions

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| Version | Licence | Obtention |
|------|------|----------|
| **Lite (简化版)** | Open source (MIT) | Dépôt public GitHub |
| **Standard (标准版)** | Licence commerciale | Contacter erik@erik.xyz |
| **Full (完整版)** | Licence commerciale | Contacter erik@erik.xyz |

---

## Comparaison des fonctionnalités

### Fonctionnalités de base

| Fonctionnalité | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Authentification (connexion/rafraîchissement de jeton/utilisateur courant) | ✅ | ✅ | ✅ |
| Gestion des plateformes (liste des 29 plateformes + OAuth) | ✅ | ✅ | ✅ |
| Gestion des comptes (CRUD + synchronisation) | ✅ | ✅ | ✅ |
| Plans publicitaires (CRUD + activation/pause + en masse) | ✅ | ✅ | ✅ |
| Rapports (tableau de bord + personnalisé + export CSV/Excel/PDF) | ✅ | ✅ | ✅ |
| Vérification de santé + documentation API + captcha | ✅ | ✅ | ✅ |
| Synchronisation des données (Campaign + Report) | ✅ | ✅ | ✅ |

### Gestion de la diffusion

| Fonctionnalité | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Groupes d'annonces (CRUD + activation/pause) | — | ✅ | ✅ |
| Créations publicitaires (liste + détail) | — | ✅ | ✅ |
| Synchronisation des données des groupes/créations | — | ✅ | ✅ |

### Surveillance et notifications

| Fonctionnalité | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Moteur de règles d'alerte (7 indicateurs/4 conditions/3 portées) | — | ✅ | ✅ |
| Journaux d'alerte + confirmation + non lus | — | ✅ | ✅ |
| Centre de notifications (liste/lues/tout marquer comme lu) | — | ✅ | ✅ |

### Fonctionnalités avancées

| Fonctionnalité | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Moteur de règles d'enchères automatiques (3 actions/refroidissement) | — | — | ✅ |
| Modèles de ciblage d'audience (schéma JSON générique) | — | — | ✅ |
| Bibliothèque de ressources publicitaires (upload/galerie/aperçu) | — | — | ✅ |
| Alerte de budget (alertes en trois paliers 50/80/100 %) | — | — | ✅ |
| Calendrier de diffusion (visualisation Gantt) | — | — | ✅ |
| Attribution inter-plateformes (5 modèles/remontée 30 jours) | — | — | ✅ |

---

## Comparaison des protections de sécurité

| Élément de protection | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| Liste blanche CORS | ✅ | ✅ | ✅ |
| En-têtes de sécurité (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| Routage par version (/api/v1) | ✅ | ✅ | ✅ |
| Limitation d'API (fenêtre glissante) | ✅ | ✅ | ✅ |
| Détection d'injection SQL (correspondance de modèles) | ✅ | ✅ | ✅ |
| Filtrage des entrées (strip_tags + trim) | ✅ | ✅ | ✅ |
| Chiffrement/déchiffrement en transit (X-Encrypted) | ✅ | ✅ | ✅ |
| Authentification JWT Bearer | ✅ | ✅ | ✅ |
| Détection d'attaques XSS (11 modèles) | — | ✅ | ✅ |
| Détection de traversée de chemin (7 modèles) | — | ✅ | ✅ |
| Détection d'injection d'en-têtes | — | ✅ | ✅ |
| Limite de taille du corps (10 MiB) | — | ✅ | ✅ |
| Liste blanche Content-Type | — | ✅ | ✅ |
| Identification de la plateforme cliente (8 sources) | — | ✅ | ✅ |
| Limitation de connexion (5 échecs → 15 minutes) | — | ✅ | ✅ |
| Surveillance du temps de réponse (X-Response-Time) | — | ✅ | ✅ |
| Validation Origin/Referer | — | — | ✅ |
| Protection anti-rejeu (Nonce+Timestamp) | — | — | ✅ |
| Limitation des sessions simultanées (max 3) | — | — | ✅ |
| Jeton CSRF (côté Admin) | — | — | ✅ |
| Protection SSRF (liste blanche OAuth) | — | — | ✅ |
| Masquage des données des journaux | — | — | ✅ |
| Liaison JWT IP/UA | — | — | ✅ |

---

## Comparaison des chaînes de middleware

### Côté Service

| Lite (7 couches) | Standard (11 couches) | Full (15 couches) |
|-------------|-----------------|-------------|
| CorsMiddleware | CorsMiddleware | CorsMiddleware |
| — | — | OriginGuardMiddleware |
| SecurityHeadersMiddleware | SecurityHeadersMiddleware | SecurityHeadersMiddleware |
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | ReplayGuardMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |
| RateLimitMiddleware | RateLimitMiddleware | RateLimitMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | — | SessionLimitMiddleware |
| SqlGuardMiddleware | SqlGuardMiddleware | SqlGuardMiddleware |
| ValidationMiddleware | ValidationMiddleware | ValidationMiddleware |
| — | ResponseTimeMiddleware | ResponseTimeMiddleware |
| EncryptionMiddleware | EncryptionMiddleware | EncryptionMiddleware |

### Côté Admin

| Lite (1 couche) | Standard (4 couches) | Full (5 couches) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## Comparaison des tâches planifiées

| Tâche | Fréquence | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55 min | ✅ | ✅ | ✅ |
| DataSyncTask | 10 min | ✅ (Campaign+Report uniquement) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3 min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5 min | — | ✅ | ✅ |
| BidCheckTask | 10 min | — | — | ✅ |
| BudgetCheckTask | 15 min | — | — | ✅ |

---

## Comparaison des tables de base de données

| Catégorie | Nom de table | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| Base | ads_tenants | ✅ | ✅ | ✅ |
| Comptes | ads_platform_accounts | ✅ | ✅ | ✅ |
| | ads_auth_tokens | ✅ | ✅ | ✅ |
| Diffusion | ads_campaigns | ✅ | ✅ | ✅ |
| | ads_report_metrics | ✅ | ✅ | ✅ |
| | ads_report_extras | ✅ | ✅ | ✅ |
| | ads_ad_groups | — | ✅ | ✅ |
| | ads_creatives | — | ✅ | ✅ |
| Alertes | ads_alert_rules | — | ✅ | ✅ |
| | ads_alert_logs | — | ✅ | ✅ |
| Notifications | ads_notifications | — | ✅ | ✅ |
| Enchères | ads_bid_rules | — | — | ✅ |
| | ads_bid_logs | — | — | ✅ |
| Ciblage | ads_targeting_templates | — | — | ✅ |
| Ressources | ads_assets | — | — | ✅ |
| CDN | ads_cdn_providers | — | — | ✅ |
| Attribution | ads_conversions | — | — | ✅ |
| | ads_attribution_results | — | — | ✅ |
| Système | ads_sync_errors | ✅ | ✅ | ✅ |
| Gestion | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **Total** | | **8** | **13** | **19** |

---

## Comparaison des pages frontend

### SPA Vue Admin

| Page | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Connexion | ✅ | ✅ | ✅ |
| Tableau de bord | ✅ | ✅ | ✅ |
| Liste des comptes + liaison | ✅ | ✅ | ✅ |
| Plans publicitaires | ✅ | ✅ | ✅ |
| Export de rapports | ✅ | ✅ | ✅ |
| Gestion des utilisateurs | ✅ | ✅ | ✅ |
| Journaux d'audit | ✅ | ✅ | ✅ |
| Groupes d'annonces | — | ✅ | ✅ |
| Créations publicitaires | — | ✅ | ✅ |
| Analyse de rapports (ECharts) | — | ✅ | ✅ |
| Règles d'alerte | — | ✅ | ✅ |
| Journaux d'alerte | — | ✅ | ✅ |
| Centre de notifications | — | ✅ | ✅ |
| Enchères automatiques | — | — | ✅ |
| Bibliothèque de ressources | — | — | ✅ |
| Fournisseurs CDN | — | — | ✅ |
| Calendrier de diffusion | — | — | ✅ |
| Analyse d'attribution | — | — | ✅ |
| **Total** | **7** | **13** | **18** |

### Flutter

| Page | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Connexion | ✅ | ✅ | ✅ |
| Tableau de bord | ✅ | ✅ | ✅ |
| Plans publicitaires (liste+détail) | ✅ | ✅ | ✅ |
| Rapports de données | ✅ | ✅ | ✅ |
| Comptes de plateformes | ✅ | ✅ | ✅ |
| Gestion des alertes | ✅ | ✅ | ✅ |
| Groupes d'annonces | — | ✅ | ✅ |
| Créations publicitaires | — | ✅ | ✅ |
| Analyse de rapports | — | ✅ | ✅ |
| Centre de notifications | — | ✅ | ✅ |
| Enchères automatiques | — | — | ✅ |
| **Total** | **6** | **10** | **11** |

---

## Comparaison des points de terminaison API

| Module | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Système (health/ping/docs/captcha) | 6 | 6 | 6 |
| Authentification (login/me/refresh) | 3 | 3 | 3 |
| Plateformes (list/oauthUrl/callback) | 3 | 3 | 3 |
| Comptes (index/show/destroy/sync) | 4 | 4 | 4 |
| Plans publicitaires (CRUD/toggle/batch) | 6 | 6 | 6 |
| Groupes d'annonces (CRUD/toggle) | — | 5 | 5 |
| Créations (index/show) | — | 2 | 2 |
| Rapports (summary/custom/export×2) | 4 | 4 | 4 |
| Rapports (calendar/budget/attribution/models) | — | — | 4 |
| Alertes (CRUD de règles + logs + acknowledge + unread) | — | 7 | 7 |
| Notifications (index/unread/read/readAll) | — | 4 | 4 |
| Enchères automatiques (CRUD + logs) | — | — | 5 |
| Modèles de ciblage (CRUD) | — | — | 5 |
| Bibliothèque de ressources (index/upload/show/destroy/presign/register) | — | — | 6 |
| Fournisseurs CDN (list/create/update/delete/default/toggle/test/purge) | — | — | 8 |
| **Total** | **26** | **44** | **70** |

---

## Pile technologique

Les trois versions partagent une pile technologique unifiée :

| Couche | Technologie |
|----|------|
| Framework backend | webman v2, PHP 8.2+ |
| Base de données | MySQL 8.0 (InnoDB, utf8mb4) |
| Cache | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| Authentification | erikwang2013/jwt-webman |
| Génération d'ID | erikwang2013/snowflake-php |
| Encodage d'ID | erikwang2013/hashids |
| Frontend | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| Déploiement | Docker + Nginx + Docker Compose |

---

## Parcours de mise à niveau

```
Lite (开源)
  │
  ├─→ 升级到 Standard (联系 erik@erik.xyz)
  │     │
  │     └─→ 新增: 广告组/创意管理、告警引擎、通知中心、
  │              AttackGuard/XSS/路径遍历/登录节流/响应时间监控
  │
  └─→ 升级到 Full (联系 erik@erik.xyz)
        │
        └─→ 新增: Standard 全部 + 自动出价、定向模板、素材库、
                  预算预警、投放日历、跨平台归因、防重放/并发限制/CSRF/SSRF
```
