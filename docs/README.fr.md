# Ads Platform — Système de gestion publicitaire multi-plateformes

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## Vue d'ensemble

**Ads Platform** est un système de gestion publicitaire multi-plateformes qui intègre **29 plateformes publicitaires** (16 nationales + 13 internationales), offrant une gestion unifiée de la diffusion publicitaire et des rapports de données inter-plateformes.

- **Gestion des campagnes** — autorisation OAuth des comptes, gestion unifiée des campagnes/groupes d'annonces/annonces inter-plateformes
- **Rapports** — agrégation des métriques inter-plateformes, export CSV/Excel/PDF, attribution inter-plateformes à 5 modèles
- **Diffusion intelligente** — enchères automatiques, alertes budgétaires, calendrier de diffusion (Gantt), bibliothèque de créatifs
- **Surveillance & alertes** — moteur de règles d'alerte, notifications multi-canaux, synchronisation automatique planifiée
- **Accès multi-appareils** — admin Web (Vue 3), Flutter PC/Mobile, HarmonyOS
- **Stabilité & fiabilité** — circuit breaker/dégradation/timeout pour les appels plateforme, cache à 3 niveaux, optimisations haute concurrence, 22 protections de sécurité
- **Internationalisation** — documentation en 12 langues, interface bilingue (ZH/EN)

> Conception de l'architecture → [docs/architecture.fr.md](docs/architecture.fr.md)
> Modules fonctionnels → [docs/features.fr.md](docs/features.fr.md)
> Documentation API → [docs/api.fr.md](docs/api.fr.md) | hg/apidoc : `http://127.0.0.1:8788/apidoc`
> Comparaison des versions → [docs/versions.fr.md](docs/versions.fr.md) (Lite open source / Standard & Full contacter erik@erik.xyz)

### Plateformes prises en charge

#### Nationales (16)
| Plateforme | Adaptateur | Authentification |
|------|--------|------|
| 巨量引擎 | Juliang | OAuth2 Access-Token |
| 百度营销 | Baidu | OAuth2 + signature enveloppe |
| 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5 |
| 腾讯广告 | Tencent | OAuth2 + nonce |
| 快手磁力引擎 | Kuaishou | OAuth2 paramètre URL |
| 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer |
| 微博粉丝通 | Weibo | OAuth2 Bearer |
| B站花火 | Bilibili | OAuth2 Bearer |
| 优酷广告 | Youku | OAuth2 + MD5 |
| 美团广告 | Meituan | OAuth2 Bearer |
| 知乎广告 | Zhihu | OAuth2 Bearer |
| 360推广 | Qihoo360 | API Key + Sign |
| 搜狗推广 | Sogou | API Key + Sign |
| 友盟 | Umeng | API Key + MD5 |
| 京东京准通 | Jingdong | OAuth2 + MD5 |
| 拼多多广告 | Pinduoduo | OAuth2 + Sign personnalisé |

#### Internationales (13)
| Plateforme | Adaptateur | Authentification |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 paramètre URL |
| TikTok Ads | Tiktok | OAuth2 Access-Token |
| LinkedIn Ads | Linkedin | OAuth2 Bearer |
| Snapchat Ads | Snapchat | OAuth2 Bearer |
| Pinterest Ads | Pinterest | OAuth2 Bearer |
| Twitter/X Ads | Twitter | OAuth2 Bearer |
| Amazon Ads | Amazon | OAuth2 + Profile |
| The Trade Desk | TheTradeDesk | HMAC-SHA256 |
| Spotify Ads | Spotify | OAuth2 Bearer |
| Twitch Ads | Twitch | OAuth2 Bearer + ClientId |
| Netflix Ads | Netflix | OAuth2 client_credentials |

---

## Pile technologique

| Couche | Technologie | Description |
|----|------|------|
| Serveur | webman v2 + PHP 8.2+ | 7 plugins, 65+ points de terminaison API |
| Base de données | MySQL 8.0 | 28 tables, préfixe ads_, clés primaires BIGINT Snowflake |
| Cache | Redis 7 | Cache à trois niveaux (L1 mémoire / L2 APCu / L3 Redis), compteurs de limitation, Pub/Sub, file de messages |
| Recherche | Elasticsearch | Synchronisation d'index automatique webman-scout (configurée) |
| Admin | webman-admin v2 + Vue 3 + TypeScript + Element Plus | Backend PHP (port 8789), SPA accédant directement à l'API métier (port 8788), 19 pages, visualisation ECharts |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | Responsive PC/Mobile, mise en page Desktop Shell, 12 pages |
| HarmonyOS | ArkTS + ArkUI | 6 pages implémentées, client HTTP prêt |
| Déploiement | Docker + Nginx + GHCR | Démarrage en une commande avec Docker Compose, build et push automatiques via GitHub Actions |

## Schéma d'architecture

![Schéma d'architecture système](docs/diagrams/svg/architecture.fr.svg)

### Schéma du flux de requêtes

![Schéma du flux de requêtes](docs/diagrams/svg/request-flow.fr.svg)

### Schéma des modules fonctionnels

![Schéma des modules fonctionnels](docs/diagrams/svg/functional-modules.fr.svg)

### Schéma du cycle de vie des données

![Schéma du cycle de vie des données](docs/diagrams/svg/data-lifecycle.fr.svg)

> La version complète inclut toutes les annotations de détail, le pipeline Admin, le diagramme de Gantt des tâches planifiées et la machine d'état du cache → [docs/diagrams/](docs/diagrams/) |

> Explications détaillées de l'architecture, architecture de sécurité et conception haute concurrence dans [le document de conception de l'architecture](docs/architecture.fr.md) | Spécifications de conception historiques dans [design.md](docs/superpowers/specs/design.fr.md)

## Description de l'architecture

- **`service/`** — Service d'API métier côté utilisateur webman v2, écoute sur le port **8788**. Gère l'intégration des plateformes publicitaires, l'autorisation OAuth, la synchronisation des données, le moteur de rapports, la surveillance des alertes et autres logiques métier.
- **`admin/`** — Back-office indépendant webman-admin v2, écoute sur le port **8789**. Comprend un backend PHP (authentification, gestion des utilisateurs, configuration système) et un frontend SPA Vue 3.
- **Communication entre le back-office et le service métier** — Le SPA Vue se connecte directement à l'API service via axios (baseURL `/api`) ; les routes dédiées à l'admin (`/api/admin/*`) sont fournies par le backend PHP admin (8789), Nginx répartissant selon le chemin.
- **Mode développement** — Le serveur de dev Vite (port 5173) proxifie `/api` vers service:8788 ; le backend PHP admin fournit sur 8789 l'authentification par session et le service statique du SPA.
- **Mode production** — Nginx route `/` vers admin:8789 (SPA du back-office) et `/api/` vers service:8788 (API métier).

## Intégration Erik Stack

| Paquet | Utilisation |
|----|------|
| `erikwang2013/snowflake-php` | Génération d'IDs distribués Snowflake |
| `erikwang2013/hashids` | Chiffrement/déchiffrement des paramètres d'ID API |
| `erikwang2013/jwt-webman` | Jetons d'authentification JWT |
| `erikwang2013/encryption` | Chiffrement/déchiffrement des données sensibles au niveau API |
| `erikwang2013/encryptable` | Chiffrement/déchiffrement automatique au niveau champ DB |
| `erikwang2013/webman-scout` | Synchronisation des données Elasticsearch |
| `erikwang2013/season` | Indicateurs de drapeaux nationaux |
| `erikwang2013/poster-php` | Captcha à glissière (protection de connexion) |
| `hg/apidoc` | Génération automatique de documentation API (annotations + interface Web) |

## Internationalisation

Toutes les interfaces prennent en charge le basculement bilingue **Chinois (zh-CN)** / **English (en)** :

| Plateforme | Technologie | Méthode de bascule |
|----|------|---------|
| Admin | vue-i18n v9 | Menu déroulant de langue dans la TopBar, persistance localStorage |
| Service API | `erik\support\I18n` | En-tête de requête Accept-Language / paramètre `?lang=` |
| Flutter | AppLocalizations + Delegate | Détection automatique de la langue système |
| HarmonyOS | StringResources | Bascule via `setLang()` |

## Sécurité

### Côté Service (14 couches globales + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware (couche de routage)

### Côté Admin (10 couches globales + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck (couche de routage)

### Aperçu des capacités de protection (22 éléments)

| Catégorie | Élément de protection | Description |
|------|--------|------|
| Détection d'entrée | XSS (11 modèles) | script/iframe/event handler/javascript:/data: |
| | Traversée de chemin (7 modèles) | ../ / octet nul / /etc/passwd / .env / .git |
| | Injection d'en-têtes | Détection CRLF |
| | Limite de taille du corps | 10 MiB |
| | Liste blanche Content-Type | JSON/Form/Multipart/Plain |
| | Injection SQL | Détection des modèles UNION/DROP/ALTER |
| Authentification | Liaison du jeton JWT | Vérification du hash IP + User-Agent |
| | Renouvellement du jeton + liste noire | Invalidation automatique des anciens jetons |
| | Limitation de connexion | 5 échecs → verrouillage 15 minutes (Redis) |
| | Limitation des sessions concurrentes | Maximum 3 jetons actifs par utilisateur |
| | Captcha | Captcha à glissière (valable 5 min, tolérance 5px) |
| Validation des requêtes | Liste blanche CORS | Liste blanche de domaines en production |
| | Validation Origin/Referer | Vérification des origines inter-domaines |
| | Jeton CSRF | Vérification du jeton de session côté Admin |
| | Protection anti-rejeu | Nonce + Timestamp ±5 min (côté non-navigateur) |
| | Limitation d'API | Fenêtre glissante 60 requêtes/60 s |
| | Protection SSRF | Liste blanche des redirect_uri OAuth |
| En-têtes de réponse | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | Anti-clickjacking + imposition HTTPS |
| | X-Content-Type-Options | nosniff |
| Protection des données | Chiffrement en transit | EncryptionMiddleware (X-Encrypted) |
| | Chiffrement au repos | Encryptable (au niveau champ DB) |
| | Masquage des journaux | password/token/secret → \*\*\* |

### Schéma de l'architecture de sécurité

![Schéma de l'architecture de sécurité](docs/diagrams/svg/security.fr.svg)

**Défense en profondeur** : couche externe (Nginx) → garde d'entrée (5 couches de middleware) → authentification d'identité (7 éléments) → validation des entrées (4 éléments) → contrôle de fréquence → chiffrement des données → traçabilité d'audit

**Authentification** : le service et l'admin utilisent tous deux la table `admin_users` + hachage bcrypt, JWT 24h + rotation refresh

**Audit** : toutes les opérations enregistrent IP / User-Agent / Client-Platform / détails de l'opération

**Double confirmation** : les opérations de suppression/déliaison/par lots utilisent le mode « mot de confirmation » (`GlobalConfirm` + `useConfirmStore`)

---

## Fonctionnalités avancées

| Fonctionnalité | Description | Technologie |
|------|------|------|
| Bibliothèque de ressources | Gestion d'upload d'images/vidéos, aperçu galerie, copie d'URL | AssetController + galerie Vue |
| Alerte de budget | Suivi en temps réel de la consommation du budget journalier, alertes en trois paliers (50/80/100 %) | BudgetAlertService + Cron 15 min |
| Calendrier de diffusion | Diagramme de Gantt inter-plateformes, vues mensuelle/hebdomadaire, coloration par plateforme | CalendarService + Gantt Vue |
| Attribution inter-plateformes | Attribution à 5 modèles (first/last/linear/time_decay/position_based), fenêtre de 30 jours | AttributionEngine + ECharts |
| Résilience des appels plateforme | Machine à états circuit breaker par plateforme (5 échecs → OPEN → sonde half-open 30 s), dégradation fast-fail, vérification des timeouts des 29 adaptateurs | CircuitBreaker + GuardedAdapter |

---

## Haute concurrence

| Optimisation | Solution | Fichier |
|------|------|------|
| Séparation lecture/écriture DB | Base principale `shared` + réplique en lecture seule `read_replica`, routage automatique des SELECT vers la réplique | `config/database.php` |
| Pool de connexions DB | Connexions persistantes `PDO::ATTR_PERSISTENT` + préchauffage d'initialisation du fuseau horaire | `config/database.php` |
| Pool de connexions Redis | Connexions persistantes `persistent` + configuration lecture/écriture `readonly` | `config/redis.php` |
| Cache à trois niveaux | L1 mémoire processus → L2 APCu mémoire partagée → L3 Redis | `support/CacheService.php` |
| File de messages asynchrone | 4 canaux List Redis (sync/report/export/notification) | `support/AsyncJobService.php` |
| Limitation par paliers Nginx | 30 r/s + burst 20 + 20 connexions simultanées + keepalive 32 | `docker/nginx/admin.conf` |
| Extension horizontale | upstream multi-instances + bascule en cas de panne + sticky session | `docker/nginx/admin.conf` |
| Accélération CDN | Ressources statiques `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## Démarrage rapide

### Installation Web en un clic (recommandé)

Après le démarrage du service, accédez à `/install` dans le navigateur pour ouvrir l'assistant d'installation :

```bash
# Démarrer le back-office (port 8789)
cd admin && composer install && php start.php start

# Ouvrir le navigateur sur http://localhost:8789/install
# Renseigner les informations de base de données, le compte administrateur, puis cliquer sur « Démarrer l'installation »
```

L'assistant d'installation vous guidera pas à pas :
1. **Connexion base de données** — renseigner l'hôte MySQL, le port, le nom de la base, l'utilisateur et le mot de passe, avec test de connexion
2. **Configuration Redis** — renseigner les informations de connexion Redis (optionnel)
3. **Compte administrateur** — définir le nom d'utilisateur, le mot de passe et le nom d'affichage du back-office
4. **Installation en un clic** — création automatique de la base, exécution de `install.sql` pour créer les 28 tables avec les données de départ, mise à jour du mot de passe administrateur

Après l'installation, accédez à `/` pour ouvrir le back-office et connectez-vous avec le nom d'utilisateur et le mot de passe définis.

### Docker (recommandé pour la production)

```bash
# Démarrer tous les services (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# Initialiser la base de données (création des tables + données de départ)
make db-init

# Accès
# Back-office : http://localhost
# Assistant d'installation : http://localhost/install
# API : http://localhost/api (Header : X-API-Version: v1)
```

### Développement local

```bash
# Serveur (port 8788)
cd service && composer install && php start.php start

# Back-office (port 5173)
cd admin/public/web && npm install && npm run dev

# Application Flutter
cd apps/flutter && flutter run -d chrome  # Web PC
# Application HarmonyOS
# Ouvrir le répertoire apps/harmonyos avec DevEco Studio
cd apps/flutter && flutter run -d android # Mobile

# Vérification TypeScript
cd admin/public/web && npx vue-tsc --noEmit   # zéro erreur
```

---

## Structure du projet

```
ads-php/
├── service/                           # Service métier côté utilisateur (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (61 points de terminaison, routage par version)
│   │   │   ├── controller/v1/         # 17 contrôleurs
│   │   │   ├── middleware/            # 15 middlewares
│   │   │   ├── config/route.php       # Définition des routes
│   │   │   └── route_helpers.php      # Fonction d'aide versioned()
│   │   ├── ads-platform/              # Cœur des adaptateurs de plateformes
│   │   │   ├── adapter/               # 29 adaptateurs de plateformes
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # Migrations SQL + index de performance
│   │   ├── ads-account/               # Gestion des comptes OAuth
│   │   ├── ads-task/                  # Planification des tâches (6 cron)
│   │   ├── ads-alert/                 # Moteur de surveillance des alertes + alertes de budget
│   │   ├── ads-report/                # Moteur de rapports (CSV/Excel/PDF) + moteur d'attribution + calendrier de diffusion
│   │   └── ads-tenant/                # Gestion multi-locataires
│   ├── support/                       # Classes utilitaires Erik Stack
│   │   ├── ControllerTrait.php        # Trait commun des contrôleurs
│   │   ├── JwtService.php             # Wrapper JWT
│   │   ├── CacheService.php           # Service de cache Redis
│   │   ├── ExceptionHandler.php       # Gestionnaire d'exceptions API
│   │   └── ApiResponse.php            # Format de réponse unifié
│   ├── config/                        # Configuration globale (DB/Redis/Log/Middleware)
│   ├── tests/                         # Tests PHPUnit (265 tests)
│   │   ├── Unit/                      # Tests unitaires (Middleware, Task)
│   │   └── Integration/               # Tests d'intégration (Auth, Health)
│   └── start.php                      # Point d'entrée du service
├── admin/                             # Back-office indépendant (webman-admin v2 :8789)
│   ├── public/web/src/
│   │   ├── views/                     # 15 pages Vue
│   │   │   ├── dashboard/             # Tableau de bord (ECharts)
│   │   │   ├── campaign/              # Plans publicitaires
│   │   │   ├── adgroup/               # Groupes d'annonces
│   │   │   ├── creative/              # Créations publicitaires
│   │   │   ├── report/                # Analyse de rapports + export
│   │   │   ├── alert/                 # Règles d'alerte + journaux
│   │   │   ├── notification/          # Centre de notifications
│   │   │   ├── bid/                   # Règles d'enchères automatiques
│   │   │   └── system/                # Gestion des utilisateurs + journaux d'audit
│   │   ├── api/                       # 9 clients API
│   │   ├── stores/                    # 4 stores Pinia
│   │   └── components/                # Composants partagés (ListPageLayout, etc.)
│   ├── app/                           # Backend PHP (controller/middleware)
│   └── config/                        # Configuration Admin
├── apps/
│   ├── flutter/                       # Application Desktop Flutter
│   │   └── lib/
│   │       ├── features/              # 12 pages fonctionnelles + mise en page Shell
│   │       ├── config/menu_config.dart # Configuration du menu à deux niveaux
│   │       ├── router.dart            # GoRouter (ShellRoute + gardes de routes)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (client API prêt)
├── docker/                            # Configuration Docker & Nginx
├── .github/workflows/                 # CI (syntaxe→tests→TS→Docker) + CD (build push)
├── docs/                              # Documents de conception, plans d'implémentation, Skills
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## Points de terminaison API

> Toutes les définitions de points de terminaison API se trouvent dans [docs/api.fr.md](docs/api.fr.md) (avec exemples de requêtes/réponses, codes d'erreur, stratégies de limitation).
> Documentation en ligne hg/apidoc : après le démarrage du service, accéder à `http://127.0.0.1:8788/apidoc`

## Base de données

**Conventions de nommage** : préfixe de table `ads_`, clé primaire `BIGINT UNSIGNED PRIMARY KEY` (sans auto-incrément, ID Snowflake), moteur InnoDB, jeu de caractères utf8mb4

| Catégorie | Nom de table | Utilisation |
|------|------|------|
| Base | `ads_tenants` | Multi-locataires |
| Comptes | `ads_platform_accounts`, `ads_auth_tokens` | Comptes de plateformes OAuth |
| Diffusion | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | Hiérarchie de diffusion publicitaire |
| Rapports | `ads_report_metrics`, `ads_report_extras` | Métriques de rapport unifiées |
| Ressources | `ads_assets` | Bibliothèque de ressources créatives |
| Ciblage | `ads_targeting_templates` | Modèles de ciblage d'audience |
| Attribution | `ads_conversions`, `ads_attribution_results` | Suivi des conversions + résultats d'attribution |
| Enchères | `ads_bid_rules`, `ads_bid_logs` | Règles d'enchères automatiques + historique |
| Alertes | `ads_alert_rules`, `ads_alert_logs` | Surveillance des alertes |
| Notifications | `ads_notifications` | Notifications internes |
| Système | `ads_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | Erreurs de synchronisation, RBAC, audit |

---

## Tâches planifiées

| Tâche | Fréquence | Fonction |
|------|------|------|
| TokenRefreshTask | Toutes les 55 minutes | Analyse des jetons OAuth expirés, renouvellement automatique |
| DataSyncTask | Toutes les 10 minutes | Récupération des plans + groupes d'annonces + créations + rapports de chaque plateforme, écriture dans les tables unifiées, purge du cache |
| AlertCheckTask | Toutes les 5 minutes | Parcourt les règles d'alerte actives, évalue les seuils, déclenche les notifications |
| BidCheckTask | Toutes les 10 minutes | Parcourt les règles d'enchères automatiques, interroge les métriques, exécute les ajustements de budget / mises en pause |
| BudgetCheckTask | Toutes les 15 minutes | Parcourt les plans en diffusion, suivi de la consommation du budget journalier, alertes en trois paliers (50/80/100 %) |
| RetrySyncTask | Toutes les 3 minutes | Nouvelle tentative des tâches de synchronisation échouées (3 maximum, backoff exponentiel) |

---

## Tests

```bash
cd service && ./vendor/bin/phpunit
# 265 tests / 717 assertions
```

**Couverture** : middlewares (Version/SQLGuard/SecurityHeaders) · objets de données (CampaignData/FieldMapping/Hashids) · moteurs (ReportBuilder/AdapterRegistry) · tests d'intégration (Auth/Health)

```bash
# Vérification TypeScript
cd admin/public/web && npx vue-tsc --noEmit   # zéro erreur

# Analyse Dart
cd apps/flutter && dart analyze   # zéro erreur
```

## CI/CD

**CI** (`.github/workflows/ci.yml`) : pipeline automatique — **Syntaxe PHP → PHPUnit → TypeScript → Build Docker**

**CD** (`.github/workflows/deploy.yml`) : déclenchement manuel — **Docker Buildx → Push GHCR (service/admin/admin-php) → notification de déploiement**

`.github/dependabot.yml` met à jour automatiquement chaque semaine les dépendances Composer + npm + Docker.

---

## Skills

`docs/skills/` — 11 compétences de projet réutilisables :

| Skill | Description |
|------|------|
| `adapter-generator` | Génère un nouvel adaptateur de plateforme publicitaire (modèle de 14 méthodes) |
| `migration-generator` | Génère des fichiers de migration SQL (préfixe ads_ + PK BIGINT) |
| `erik-stack` | Guide d'intégration des 8 paquets Erik Stack |
| `admin-page-generator` | Génère des pages de back-office Vue 3 |
| `api-endpoint` | Ajoute des points de terminaison API RESTful |
| `tdd-workflow` | Processus de validation TDD (tests → implémentation → syntaxe → TypeScript → commit) |
| `security-middleware` | Ajoute des couches de middleware de sécurité (spécification d'interface + enregistrement + référence à la chaîne existante) |
| `version-split` | Découpage en trois versions Lite/Standard/Full (étapes d'opération + mise à jour de configuration) |
| `cache-strategy` | Stratégie de cache à trois niveaux (L1 mémoire / L2 APCu / L3 Redis + suggestions TTL) |
| `attribution-setup` | Moteur d'attribution inter-plateformes (5 modèles + appels API + préparation des données) |
| `high-concurrency` | 8 optimisations haute concurrence (séparation lecture/écriture / pool de connexions / file de messages / extension horizontale / CDN) |


## L'open source n'est pas facile, merci pour votre soutien

| WeChat | Alipay |
|:---:|:---:|
| ![WeChat](./docs/weixinpay.png "WeChat") | ![Alipay](./docs/alipay.png "Alipay") |

### Dons par virement international (Global Transfer Donation)

**Informations du bénéficiaire (Beneficiary)**

| Champ | Valeur |
|------|-----|
| Nom du bénéficiaire (Name) | WANG KEXUN |
| Numéro de compte du bénéficiaire (Account No.) | 881015918251 |

**Banque réceptrice (Receiving Bank) — ZA Bank**

| Champ | Valeur |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| Nom de la banque (Bank Name) | ZA Bank Limited |
| Code bancaire (Bank Code) | 387 |
| Adresse de la banque (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **Banque correspondante pour virements transfrontaliers (si nécessaire, Correspondent Bank)** : il s'agit des informations de la banque correspondante (intermédiaire), et non de la banque réceptrice. Veuillez vérifier auprès de votre banque émettrice si ces informations sont requises.
>
> - **Dollars hongkongais, renminbi et dollars américains** : Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · code bancaire 006 · Hong Kong Branch (code de succursale 391) · Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **Autres devises** : THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

---

## Licence

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

Tous droits réservés.
