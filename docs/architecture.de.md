# Architektur-Design-Dokument

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. Systemübersicht

Multi-Plattform-Werbe-Verwaltungssystem, angebunden an **29 Werbeplattformen**, mit Schaltungsverwaltung, plattformübergreifenden Berichten, Alarmüberwachung, automatischem Gebot und Zielgruppen-Targeting. Unterstützt drei Betriebsmodi: SaaS-Multi-Tenant, Betrieb im Auftrag (代运营) und Selbstnutzung.

---

## 2. Bereitstellungsarchitektur

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

## 3. Anfrageverarbeitungs-Pipeline

### 3.1 Service-Seite (15 Middleware-Ebenen)

```
Request
  → CorsMiddleware            (CORS 白名单、OPTIONS 预检)
  → OriginGuardMiddleware     (Origin/Referer 校验 + 拦截 TRACE/DEBUG/CONNECT)
  → SecurityHeadersMiddleware (CSP/X-Frame-Options/X-Content-Type-Options/HSTS)
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body 10MiB/Content-Type白名单)
  → ClientPlatformMiddleware  (X-Client-Platform 8端来源识别)
  → ReplayGuardMiddleware     (Nonce+Timestamp 防重放, 非浏览器端强校验)
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

### 3.2 Admin-Seite (6 Middleware-Ebenen)

```
Request
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body限制/Content-Type)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟)
  → ClientPlatformMiddleware  (X-Client-Platform 来源识别)
  → CsrfMiddleware            (CSRF Token 验证)
  → AuthCheck                 (Session + JWT 双通道)
  → Controller
```

---

## 4. Verzeichnisstruktur

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
│   │   └── ads-storage/                   # Speicherabstraktion (local/OSS/COS/S3) + CDN-Anbieter
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

## 5. Datenmodell

### 5.1 Tabellenklassifizierung

| Kategorie | Tabellenname | Primärschlüssel | Verwendung |
|------|------|------|------|
| Basis | `ads_tenants` | BIGINT Snowflake | Multi-Tenant |
| Konten | `ads_platform_accounts`, `ads_auth_tokens` | BIGINT Snowflake | OAuth-Plattformkonten |
| Schaltungsebene | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | BIGINT Snowflake | Werbeschaltung |
| Berichte | `ads_report_metrics`, `ads_report_extras` | BIGINT Snowflake | Einheitliche Kennzahlen |
| Alarm | `ads_alert_rules`, `ads_alert_logs` | BIGINT Snowflake | Überwachung und Alarmierung |
| Gebote | `ads_bid_rules`, `ads_bid_logs` | BIGINT Snowflake | Automatisches Gebot |
| Targeting | `ads_targeting_templates` | BIGINT Snowflake | Zielgruppen-Vorlagen |
| Material | `ads_assets` | BIGINT Snowflake | Kreativ-Materialbibliothek |
| CDN | `ads_cdn_providers` | BIGINT Snowflake | CDN-Anbieterkonfiguration (feldweise verschlüsselte Zugangsdaten) |
| Benachrichtigungen | `ads_notifications` | BIGINT Snowflake | In-Site-Benachrichtigungen |
| Attribution | `ads_conversions`, `ads_attribution_results` | BIGINT Snowflake | Conversion-Tracking + Attribution |
| System | `ads_sync_errors` | BIGINT Snowflake | Synchronisierungsfehler |
| Verwaltung | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + Audit |

### 5.2 Namenskonvention

- Tabellenpräfix: `ads_`
- Primärschlüssel: `BIGINT UNSIGNED PRIMARY KEY` (ohne Auto-Increment, Snowflake-ID)
- Engine: InnoDB, Zeichensatz: utf8mb4
- Zeitstempel: `created_at`, `updated_at` (DATETIME)

---

## 6. Sicherheitsarchitektur

### 6.1 Schutzebenen

| Ebene | Mechanismus | Abdeckung |
|----|------|----------|
| Transport | Nginx (SSL-Terminierung) | Vollständig |
| Netzwerk | CORS-Whitelist + Origin-Prüfung + HSTS | Service |
| Eingabe | AttackGuard (XSS 11 Muster/Path-Traversal 7 Muster/Header-Injection) | Service + Admin |
| Injection | SQLGuard (SQL-Injection-Mustererkennung) | Service |
| Bereinigung | ValidationMiddleware (strip_tags) | Service |
| Authentifizierung | JWT Bearer + bcrypt + IP/UA-Bindung + Refresh-Rotation | Service |
| Authentifizierung | Session + JWT-Doppelkanal + CSRF-Token | Admin |
| Autorisierung | RBAC (Rollen + Berechtigungs-JSON) | Admin |
| Drosselung | RateLimit (gleitendes Fenster) + LoginThrottle (5-mal → 15 Minuten) | Service + Admin |
| Session | SessionLimit (max. 3 aktive Token) + Blacklist | Service |
| Verschlüsselung | EncryptionMiddleware (Transport) + Encryptable (Speicher) | Service |
| Replay | ReplayGuard (Nonce+Timestamp ±5min, nicht-Browser) | Service + Client |
| Resilienz | CircuitBreaker (pro Plattform: 5 Fehler → OPEN → 30s halboffen) + GuardedAdapter (Fast-Fail bei Degradierung) | Service |
| Audit | Operations-Trace (IP/UA/Plattform) | Admin |
| Maskierung | Log-Maskierung sensibler Felder (password/token/secret → ***) | Service |

### 6.2 Client-Plattformerfassung

Über den `X-Client-Platform`-Header:

| Wert | Quelle |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | HarmonyOS-App |

---

## 7. API-Versions-Routing

Die API-Versionsnummer ist fest im URL-Pfad verankert (`/api/v1/...`), und die Routen sind statisch an `plugin\ads_api\controller\v1\*` gebunden; sie wird nicht im Header übertragen. Beim Hinzufügen einer neuen Version wird eine eigene Routengruppe registriert (z. B. `/api/v2` → `controller\v2\*`).

```
请求: GET /api/v1/campaigns

路由 /api/v1/campaigns
  → controller\v1\CampaignController::index()
```

---

## 8. Geplante Aufgabenplanung

| Aufgabe | Cron | Funktion |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | Abgelaufene OAuth-Token aktualisieren |
| DataSyncTask | `*/10 * * * *` | Campaigns→AdGroups→Creatives→Reports synchronisieren→Cache leeren |
| AlertCheckTask | `*/5 * * * *` | Alarmregeln bewerten, Benachrichtigungen auslösen |
| BidCheckTask | `*/10 * * * *` | Gebotsregeln bewerten, Budgetanpassung/Start-Stopp ausführen |
| RetrySyncTask | `*/3 * * * *` | Fehlgeschlagene Synchronisierungen erneut versuchen (max. 3-mal, exponentielles Backoff) |

---

## 9. Erik-Stack-Paketintegration

| Paket | Integrationsort | Verwendung |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10 Modelle (SnowflakeTrait) + admin helpers.php | Primärschlüsselgenerierung |
| `erikwang2013/hashids` | ApiResponse + 2 Admin-Controller | ID-Codierung |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | Authentifizierungstoken |
| `erikwang2013/encryption` | EncryptionMiddleware | Transport-Ver-/Entschlüsselung |
| `erikwang2013/encryptable` | PlatformAccount + AuthToken-Model | DB-Feldverschlüsselung |
| `erikwang2013/webman-scout` | Campaign-Model (Searchable-Trait) | ES-Suche |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | Nationalflaggen |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | Slider-Captcha |
| `hg/apidoc` | Annotationen → Dokumentgenerierung (Web-UI: :8788/apidoc) | API-Dokumentation |

---

## 10. Hochverfügbarkeitsarchitektur

### 10.1 Datenbankebene

| Optimierung | Beschreibung |
|------|------|
| Lese-/Schreibtrennung | Hauptdatenbank `shared` (Schreiben) + Read-Only-Replikat `read_replica` (Berichts-/Analyseabfragen) |
| Persistente Verbindungen | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent`, um häufige TCP-Handshakes zu vermeiden |
| Verbindungsvorwärmung | Beim Worker-Start `SELECT 1` ausführen, erst nach Verbindungspool-Bereitschaft Anfragen annehmen |

### 10.2 Cache-Ebene

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 Message-Queue

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4 Kanäle: `sync` | `report` | `export` | `notification`

### 10.4 Horizontale Skalierung

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

- **keepalive**: 32 lange Verbindungen wiederverwenden
- **failover**: `proxy_next_upstream` automatischer Failover, 2 Wiederholungsversuche
- **Limiting**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 CDN für statische Ressourcen

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — vorab komprimierte js/css-Dateien
- In Produktion CDN anbinden (CloudFront/Aliyun CDN)

### 10.6 CDN-Beschleunigung von Assets

URL-Aufbau, Cache- und Purge-Strategie für Assets: siehe [Kapitel 12 Asset-Speicherung & CDN-Beschleunigung](#12-asset-speicherung--cdn-beschleunigung).

---

## 11. Bereitstellung und CI/CD

### Docker-Dienste

| Dienst | Port | Image |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP-Syntax → PHPUnit → TypeScript → Docker-Build
- **CD** (`.github/workflows/deploy.yml`): Docker-Buildx → GHCR-Push (service/admin/admin-php) → Deploy

---

## 12. Asset-Speicherung & CDN-Beschleunigung

### 12.1 Speicherabstraktionsschicht

`service/plugin/ads-storage/` bietet eine einheitliche `Storage`-Fassade + `StorageDriver`-Schnittstelle (put/delete/signedUrl/publicUrl/putFile/deleteUrl/purge) und wechselt die Implementierung je nach driver:

| driver | Implementierung | Einsatz |
|--------|-----------------|---------|
| `local` | LocalStorage | Standard, lokal `public/uploads/assets/` |
| `oss` | AlibabaOssStorage | Alibaba Cloud OSS |
| `cos` | TencentCosStorage | Tencent Cloud COS (S3-Protokoll) |
| `s3` | S3CompatibleStorage | S3-kompatibel: AWS S3 / Cloudflare R2 / MinIO |

Auslieferung bevorzugt den Standard-Anbieter aus der DB (in der Verwaltung konfigurierbar), sonst Fallback auf env/local.

### 12.2 CDN-Anbieterverwaltung

Neue Tabelle `ads_cdn_providers` (name/driver/bucket/region/endpoint/access_key/secret_key/cdn_domain/cdn_driver/cdn_token/enabled/is_default/status):

- Zugangsdaten (access_key/secret_key/cdn_token) werden über `Erikwang2013\Encryptable` feldweise verschlüsselt; API-Antworten enthalten nur maskierte Felder
- Nur der Master-Tenant der Plattform (tenantId=1) darf verwalten (AdminMiddleware); 8 Endpunkte unter `/api/admin/cdn/providers`: Liste/Anlegen/Update/Löschen/Standard/An-Aus/Erreichbarkeitstest/Cache-Purge
- purge ist für `aliyun` cdn_driver real implementiert (OpenAPI-Signatur); cloudflare/cloudfront folgen später

### 12.3 URL-Aufbau

`ads_assets.url` speichert immer einen relativen Pfad (`/uploads/assets/...`); beim Lesen wird die `cdn_domain` des Standard-Anbieters vorangestellt → vollständige HTTPS-URL (`https://{cdn_domain}/{url}`); ohne CDN unverändert.

### 12.4 Cache-Strategie

| Typ | Strategie |
|-----|-----------|
| Bilder | `immutable` Langzeit-Cache (zufällige Dateinamen, eindeutige URLs — sicher) |
| Video | kurzer Cache + Range-Unterstützung (Segmentwiedergabe) |

Beim Löschen eines Assets wird dessen URL automatisch aus dem CDN-Cache entfernt (purge).

### 12.5 Multi-Tenant-Pfadisolierung

Asset-Keys tragen einen tenant-isolierten Präfix und werden nach tenant_id gruppiert; Assets verschiedener Tenants sind füreinander unsichtbar.

### 12.6 Vorsigniertes Direkt-Upload & Backfill

- `POST /api/assets/presign`: vorsignierte Upload-URL abrufen (Client lädt direkt in den Objektspeicher, z. B. 50-MiB-Videos); `key`-Format `Ymd/32hex.Endung`
- `POST /api/assets/register`: direkt hochgeladenes Asset registrieren; key-Format wird strikt gegen Path-Traversal geprüft
- presign beim `local`-Driver nicht verfügbar (keine Objektspeicher-Signierung)
- `service/scripts/backfill-assets.php`: kopiert vorhandene lokale Assets in den Objektspeicher (`--dry-run`-Vorschau); Spalte `url` bleibt unverändert

### 12.7 Ursprungspfad

`service/config/static.php` aktiviert die statische Dateiauslieferung von webman; `/uploads/assets` wird direkt über HTTP auf 8788 ausgeliefert und dient als CDN-Ursprungspfad.
