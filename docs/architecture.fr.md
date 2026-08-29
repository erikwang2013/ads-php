# Document de conception de l'architecture

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. Vue d'ensemble du système

Système de gestion publicitaire multi-plateformes, intégrant **29 plateformes publicitaires**, couvrant la gestion de la diffusion, les rapports inter-plateformes, la surveillance des alertes, les enchères automatiques et le ciblage d'audience. Prend en charge trois modes : SaaS multi-locataires, gestion déléguée et usage autonome.

---

## 2. Architecture de déploiement

```
                         ┌──────────────────────────┐
                         │  客户端                   │
                         │  Vue Admin / Flutter      │
                         │  HarmonyOS / Browser      │
                         └──────────┬───────────────┘
                                    │ HTTP + JWT
                                    v
                         ┌──────────────────────────┐
                         │   Nginx :80               │
                         │   /          → admin:8789 │
                         │   /api       → service:8788│
                         └──────┬──────────┬────────┘
                                │          │
                   ┌────────────┘          └────────────┐
                   v                                    v
         ┌─────────────────┐                ┌─────────────────┐
         │  Admin :8789     │  ServiceProxy  │  Service :8788  │
         │  webman-admin v2 │───────────────→│  webman v2      │
         │  Vue 3 SPA       │   cURL HTTP    │  7 插件         │
         └────────┬────────┘                └────────┬────────┘
                  │                                   │
                  └──────────────┬────────────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              v                  v                  v
        ┌──────────┐      ┌──────────┐      ┌───────────┐
        │ MySQL 8.0│      │ Redis 7  │      │    ES     │
        │ 18 张表  │      │ 缓存/队列│      │ 搜索索引  │
        └──────────┘      └──────────┘      └───────────┘
```

---

## 3. Pipeline de traitement des requêtes

### 3.1 Côté Service (15 couches de middleware)

```
Request
  → CorsMiddleware            (CORS 白名单、OPTIONS 预检)
  → OriginGuardMiddleware     (Origin/Referer 校验 + 拦截 TRACE/DEBUG/CONNECT)
  → SecurityHeadersMiddleware (CSP/X-Frame-Options/X-Content-Type-Options/HSTS)
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body 10MiB/Content-Type白名单)
  → ClientPlatformMiddleware  (X-Client-Platform 8端来源识别)
  → ReplayGuardMiddleware     (Nonce+Timestamp 防重放, 非浏览器端强校验)
  → VersionMiddleware         (X-API-Version 版本路由)
  → RateLimitMiddleware       (Redis 滑动窗口 60次/60s)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟锁定)
  → SessionLimitMiddleware    (并发会话限制 最大3个活跃Token)
  → SqlGuardMiddleware        (SQL 注入模式检测)
  → ValidationMiddleware      (输入 trim + strip_tags)
  → ResponseTimeMiddleware    (X-Response-Time 头 + 慢请求日志)
  → EncryptionMiddleware      (X-Encrypted 请求解密/响应加密)
  → AuthMiddleware            (JWT Bearer Token + IP/UA 绑定)
  → Controller
```

### 3.2 Côté Admin (6 couches de middleware)

```
Request
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body限制/Content-Type)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟)
  → ClientPlatformMiddleware  (X-Client-Platform 来源识别)
  → CsrfMiddleware            (CSRF Token 验证)
  → VersionMiddleware         (API 版本)
  → AuthCheck                 (Session + JWT 双通道)
  → Controller
```

---

## 4. Structure du répertoire

```
ads-php/
├── service/                               # 业务 API 服务 :8788
│   ├── config/                            # 全局配置
│   │   ├── app.php, database.php, redis.php
│   │   ├── log.php                        # Monolog (JSON/Line 双模式)
│   │   ├── middleware.php                 # 11 层全局中间件
│   │   ├── exception.php                  # API 异常处理器
│   │   └── scout.php                      # ES 配置
│   ├── support/                           # 共享工具类 (erik\support)
│   │   ├── ApiResponse.php                # 统一 JSON 响应
│   │   ├── ControllerTrait.php            # 控制器公共 trait
│   │   ├── JwtService.php                 # JWT 包装 (erikwang2013/jwt-webman)
│   │   ├── CacheService.php               # Redis 缓存
│   │   ├── HashidsService.php             # ID 加解密
│   │   ├── SnowflakeTrait.php             # Snowflake ID 生成
│   │   └── ExceptionHandler.php           # JSON 异常渲染
│   ├── plugin/
│   │   ├── ads-api/                       # REST API 层
│   │   │   ├── controller/v1/             # 14 个控制器
│   │   │   ├── middleware/                # 7 个中间件
│   │   │   ├── config/route.php           # 45+ 路由
│   │   │   └── route_helpers.php          # versioned() 版本路由
│   │   ├── ads-platform/                  # 平台适配器核心
│   │   │   ├── adapter/                   # 29 个平台适配器
│   │   │   ├── src/                       # AdapterRegistry, CampaignData
│   │   │   ├── model/                     # Campaign, BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/                   # BidEngine
│   │   │   └── migration/                # SQL DDL + 性能索引
│   │   ├── ads-account/                   # OAuth 账户 + 平台账户
│   │   ├── ads-task/                      # 5 个 cron 任务
│   │   ├── ads-alert/                     # 告警引擎 + 通知
│   │   ├── ads-report/                    # 报表引擎 (CSV/Excel/PDF)
│   │   ├── ads-tenant/                    # 多租户
│   │   └── ads-storage/                   # Abstraction de stockage (local/OSS/COS/S3) + fournisseurs CDN
│   ├── tests/                             # PHPUnit
│   │   ├── Unit/Middleware/               # 中间件测试
│   │   ├── Unit/Task/                     # 任务测试 (规划)
│   │   └── Integration/                   # 控制器集成测试
│   └── start.php                          # 入口
├── admin/                                 # 管理后台 :8789
│   ├── app/
│   │   ├── controller/                    # Auth, AdminUser, AuditLog
│   │   ├── middleware/                    # AttackGuard, LoginThrottle, ClientPlatform, Csrf, Version, AuthCheck
│   │   ├── service/                       # AuditService, ServiceProxy
│   │   └── support/                       # HashidsService
│   ├── public/web/                        # Vue 3 + TS SPA
│   │   └── src/
│   │       ├── views/                     # 14 页面 (dashboard/campaign/adgroup/creative/report/alert/notification/bid/system)
│   │       ├── api/                       # 9 个 API 客户端
│   │       ├── stores/                    # 4 个 Pinia Store
│   │       └── components/                # ListPageLayout 等共享组件
│   └── config/                            # Admin 配置
├── apps/
│   ├── flutter/                           # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/                  # 12 功能页面 + Shell 布局
│   │       ├── config/menu_config.dart    # 两级菜单 + 面包屑
│   │       ├── router.dart                # GoRouter + ShellRoute + 路由守卫
│   │       ├── stores/auth_provider.dart  # Riverpod Auth
│   │       └── shared/api/api_client.dart # Dio + JWT + 平台检测
│   └── harmonyos/                         # HarmonyOS (API Client 就绪)
├── docker/                                # Nginx 配置 + Dockerfiles
├── .github/workflows/                     # CI (语法→测试→TS→Docker) + CD (构建推送)
└── docs/                                  # 设计文档
```

---

## 5. Modèle de données

### 5.1 Catégories de tables

| Catégorie | Nom de table | Clé primaire | Utilisation |
|------|------|------|------|
| Base | `ads_tenants` | BIGINT Snowflake | Multi-locataires |
| Comptes | `ads_platform_accounts`, `ads_auth_tokens` | BIGINT Snowflake | Comptes de plateformes OAuth |
| Hiérarchie de diffusion | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | BIGINT Snowflake | Diffusion publicitaire |
| Rapports | `ads_report_metrics`, `ads_report_extras` | BIGINT Snowflake | Métriques unifiées |
| Alertes | `ads_alert_rules`, `ads_alert_logs` | BIGINT Snowflake | Surveillance des alertes |
| Enchères | `ads_bid_rules`, `ads_bid_logs` | BIGINT Snowflake | Enchères automatiques |
| Ciblage | `ads_targeting_templates` | BIGINT Snowflake | Modèles d'audience |
| Ressources | `ads_assets` | BIGINT Snowflake | Bibliothèque de ressources créatives |
| CDN | `ads_cdn_providers` | BIGINT Snowflake | Configuration fournisseur CDN (identifiants chiffrés par champ) |
| Notifications | `ads_notifications` | BIGINT Snowflake | Notifications internes |
| Attribution | `ads_conversions`, `ads_attribution_results` | BIGINT Snowflake | Suivi des conversions + attribution |
| Système | `ads_sync_errors` | BIGINT Snowflake | Erreurs de synchronisation |
| Gestion | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + audit |

### 5.2 Conventions de nommage

- Préfixe de table : `ads_`
- Clé primaire : `BIGINT UNSIGNED PRIMARY KEY` (sans auto-incrément, ID Snowflake)
- Moteur : InnoDB, jeu de caractères : utf8mb4
- Horodatages : `created_at`, `updated_at` (DATETIME)

---

## 6. Architecture de sécurité

### 6.1 Couches de protection

| Couche | Mécanisme | Couverture |
|----|------|----------|
| Transport | Nginx (terminaison SSL) | Toutes |
| Réseau | Liste blanche CORS + validation Origin + HSTS | Service |
| Entrée | AttackGuard (XSS 11 modèles / traversée de chemin 7 modèles / injection d'en-têtes) | Service + Admin |
| Injection | SQLGuard (détection des modèles d'injection SQL) | Service |
| Nettoyage | ValidationMiddleware (strip_tags) | Service |
| Authentification | JWT Bearer + bcrypt + liaison IP/UA + rotation refresh | Service |
| Authentification | Session + JWT double canal + jeton CSRF | Admin |
| Autorisation | RBAC (rôles + permissions JSON) | Admin |
| Limitation | RateLimit (fenêtre glissante) + LoginThrottle (5 échecs → 15 min) | Service + Admin |
| Session | SessionLimit (max 3 jetons actifs) + liste noire | Service |
| Chiffrement | EncryptionMiddleware (transport) + Encryptable (stockage) | Service |
| Rejeu | ReplayGuard (Nonce+Timestamp ±5 min, côté non-navigateur) | Service + clients |
| Résilience | CircuitBreaker (par plateforme : 5 échecs → OPEN → 30 s semi-ouvert) + GuardedAdapter (échec rapide en dégradation) | Service |
| Audit | Traces d'opérations (IP/UA/plateforme) | Admin |
| Masquage | Masquage des champs sensibles des journaux (password/token/secret → ***) | Service |

### 6.2 Identification de la plateforme cliente

Via l'en-tête `X-Client-Platform` :

| Valeur | Source |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | Application HarmonyOS |

---

## 7. Mécanisme de routage par version API

Le numéro de version n'apparaît pas dans le chemin URL. La version est transmise via l'en-tête `X-API-Version`, lue par `VersionMiddleware` qui définit `$request->apiVersion`. La fonction d'aide `versioned()` remplace à l'exécution le segment de version dans la classe de contrôleur par la version de la requête.

```
请求: GET /api/campaigns
Header: X-API-Version: v1

VersionMiddleware → $request->apiVersion = 'v1'
versioned(CampaignController::class, 'index')
  → controller\v1\CampaignController::index()
```

---

## 8. Planification des tâches

| Tâche | Cron | Fonction |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | Rafraîchit les jetons OAuth expirés |
| DataSyncTask | `*/10 * * * *` | Synchronise Campaigns→AdGroups→Creatives→Reports→purge du cache |
| AlertCheckTask | `*/5 * * * *` | Évalue les règles d'alerte, déclenche les notifications |
| BidCheckTask | `*/10 * * * *` | Évalue les règles d'enchères, exécute les ajustements de budget / mises en pause |
| RetrySyncTask | `*/3 * * * *` | Nouvelle tentative des synchronisations échouées (3 max, backoff exponentiel) |

---

## 9. Intégration des paquets Erik Stack

| Paquet | Emplacement d'intégration | Utilisation |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10 modèles (SnowflakeTrait) + admin helpers.php | Génération de clés primaires |
| `erikwang2013/hashids` | ApiResponse + 2 contrôleurs Admin | Encodage des ID |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | Jetons d'authentification |
| `erikwang2013/encryption` | EncryptionMiddleware | Chiffrement/déchiffrement en transit |
| `erikwang2013/encryptable` | Modèles PlatformAccount + AuthToken | Chiffrement des champs DB |
| `erikwang2013/webman-scout` | Modèle Campaign (trait Searchable) | Recherche ES |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | Drapeaux nationaux |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | Captcha à glissière |
| `hg/apidoc` | Annotations → génération de documentation (interface Web : :8788/apidoc) | Documentation API |

---

## 10. Architecture haute concurrence

### 10.1 Couche base de données

| Optimisation | Description |
|------|------|
| Séparation lecture/écriture | Base principale `shared` (écriture) + réplique en lecture seule `read_replica` (rapports/requêtes d'analyse) |
| Connexions persistantes | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent` pour éviter les poignées de main TCP fréquentes |
| Préchauffage des connexions | Exécution de `SELECT 1` au démarrage du worker, le pool de connexions est prêt avant de recevoir les requêtes |

### 10.2 Couche cache

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 File de messages

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4 canaux : `sync` | `report` | `export` | `notification`

### 10.4 Extension horizontale

```
                    ┌──────────────────┐
                    │   Nginx :80      │
                    │ upstream service │
                    └────────┬─────────┘
                             │
              ┌──────────────┼──────────────┐
              v              v              v
        ┌──────────┐  ┌──────────┐  ┌──────────┐
        │ php:8788 │  │ php2:8788│  │ php3:8788│
        │ worker 1 │  │ worker 2 │  │ worker 3 │
        └──────────┘  └──────────┘  └──────────┘
              │              │              │
              └──────────────┼──────────────┘
                             v
                    ┌──────────────────┐
                    │   MySQL + Redis  │
                    └──────────────────┘
```

- **keepalive** : 32 connexions longues réutilisées
- **failover** : bascule automatique `proxy_next_upstream`, 2 nouvelles tentatives
- **limitation** : `limit_req_zone` 30 r/s + burst 20 + `limit_conn` 20

### 10.5 CDN des ressources statiques

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — fichiers js/css précompressés
- Intégration CDN en production (CloudFront/Aliyun CDN)

### 10.6 Accélération CDN des ressources

Assemblage des URL, stratégies de cache et de purge : voir [chapitre 12 Stockage des ressources & accélération CDN](#12-stockage-des-ressources--accélération-cdn).

---

## 11. Déploiement et CI/CD

### Services Docker

| Service | Port | Image |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`) : Syntaxe PHP → PHPUnit → TypeScript → Build Docker
- **CD** (`.github/workflows/deploy.yml`) : Docker Buildx → Push GHCR (service/admin/admin-php) → Déploiement

---

## 12. Stockage des ressources & accélération CDN

### 12.1 Couche d'abstraction de stockage

`service/plugin/ads-storage/` fournit une façade `Storage` unifiée + une interface `StorageDriver` (put/delete/signedUrl/publicUrl/putFile/deleteUrl/purge), avec implémentation selon le driver :

| driver | implémentation | usage |
|--------|----------------|-------|
| `local` | LocalStorage | Par défaut, local `public/uploads/assets/` |
| `oss` | AlibabaOssStorage | Alibaba Cloud OSS |
| `cos` | TencentCosStorage | Tencent Cloud COS (protocole S3) |
| `s3` | S3CompatibleStorage | Compatible S3 : AWS S3 / Cloudflare R2 / MinIO |

La diffusion privilégie le fournisseur par défaut en base (configurable dans l'admin), sinon repli sur env/local.

### 12.2 Gestion des fournisseurs CDN

Nouvelle table `ads_cdn_providers` (name/driver/bucket/region/endpoint/access_key/secret_key/cdn_domain/cdn_driver/cdn_token/enabled/is_default/status) :

- Les identifiants (access_key/secret_key/cdn_token) sont chiffrés champ par champ via `Erikwang2013\Encryptable` ; l'API ne renvoie que des champs masqués
- Seul le locataire principal de la plateforme (tenantId=1) peut gérer (AdminMiddleware) ; 8 points de terminaison `/api/admin/cdn/providers` : liste/création/modification/suppression/défaut/activer-désactiver/test de connectivité/purge du cache
- purge est réellement implémenté pour le cdn_driver `aliyun` (signature OpenAPI) ; cloudflare/cloudfront à venir

### 12.3 Assemblage des URL

`ads_assets.url` stocke toujours un chemin relatif (`/uploads/assets/...`) ; à la lecture, le `cdn_domain` du fournisseur par défaut est préfixé pour former une URL HTTPS complète (`https://{cdn_domain}/{url}`) ; sans CDN, renvoyé tel quel.

### 12.4 Stratégie de cache

| type | stratégie |
|------|-----------|
| images | cache long `immutable` (noms de fichiers aléatoires, URL uniques — sûr) |
| vidéo | cache court + support Range (lecture segmentée) |

La suppression d'une ressource purge automatiquement son URL du cache CDN.

### 12.5 Isolation des chemins multi-locataires

Les clés de ressources portent un préfixe d'isolation par locataire et sont groupées par tenant_id ; les ressources de locataires différents sont mutuellement invisibles.

### 12.6 Téléversement direct pré-signé & backfill

- `POST /api/assets/presign` : obtention d'une URL de téléversement pré-signée (le client téléverse directement dans le stockage objet, ex. vidéos de 50 Mio) ; format de `key` : `Ymd/32hex.extension`
- `POST /api/assets/register` : enregistrement d'une ressource téléversée directement ; format de key strictement validé contre le path traversal
- presign indisponible avec le driver `local` (pas de signature de stockage objet)
- `service/scripts/backfill-assets.php` : copie les ressources locales existantes vers le stockage objet (`--dry-run` pour prévisualiser) ; la colonne `url` reste inchangée

### 12.7 Chemin d'origine

`service/config/static.php` active le service de fichiers statiques webman ; `/uploads/assets` est servi directement en HTTP sur 8788 comme chemin d'origine CDN.
