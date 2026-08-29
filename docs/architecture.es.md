# Documento de diseño de arquitectura

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. Resumen del sistema

Sistema de gestión de anuncios multiplataforma, integrado con **29 plataformas publicitarias**, que cubre gestión de campañas, reportes multiplataforma, monitoreo de alertas, ofertas automáticas y segmentación de audiencia. Soporta tres modos: SaaS multi-tenant, operación delegada y uso propio.

---

## 2. Arquitectura de despliegue

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

## 3. Pipeline de procesamiento de solicitudes

### 3.1 Lado Service (15 capas de middleware)

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

### 3.2 Lado Admin (6 capas de middleware)

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

## 4. Estructura de directorios

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
│   │   └── ads-storage/                   # Abstracción de almacenamiento (local/OSS/COS/S3) + proveedores CDN
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

## 5. Modelo de datos

### 5.1 Clasificación de tablas

| Categoría | Nombre de tabla | Clave primaria | Uso |
|------|------|------|------|
| Base | `ads_tenants` | BIGINT Snowflake | Multi-tenant |
| Cuentas | `ads_platform_accounts`, `ads_auth_tokens` | BIGINT Snowflake | Cuentas de plataforma OAuth |
| Jerarquía de campañas | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | BIGINT Snowflake | Entrega de anuncios |
| Reportes | `ads_report_metrics`, `ads_report_extras` | BIGINT Snowflake | Métricas unificadas |
| Alertas | `ads_alert_rules`, `ads_alert_logs` | BIGINT Snowflake | Monitoreo de alertas |
| Ofertas | `ads_bid_rules`, `ads_bid_logs` | BIGINT Snowflake | Ofertas automáticas |
| Segmentación | `ads_targeting_templates` | BIGINT Snowflake | Plantillas de audiencia |
| Materiales | `ads_assets` | BIGINT Snowflake | Biblioteca de materiales creativos |
| CDN | `ads_cdn_providers` | BIGINT Snowflake | Configuración de proveedor CDN (credenciales cifradas por campo) |
| Notificaciones | `ads_notifications` | BIGINT Snowflake | Notificaciones dentro del sitio |
| Atribución | `ads_conversions`, `ads_attribution_results` | BIGINT Snowflake | Seguimiento de conversiones + atribución |
| Sistema | `ads_sync_errors` | BIGINT Snowflake | Errores de sincronización |
| Administración | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + auditoría |

### 5.2 Convención de nombres

- Prefijo de tabla: `ads_`
- Clave primaria: `BIGINT UNSIGNED PRIMARY KEY` (sin autoincremento, ID Snowflake)
- Motor: InnoDB, charset: utf8mb4
- Marcas de tiempo: `created_at`, `updated_at` (DATETIME)

---

## 6. Arquitectura de seguridad

### 6.1 Niveles de protección

| Capa | Mecanismo | Cobertura |
|----|------|----------|
| Transporte | Nginx (terminación SSL) | Total |
| Red | Lista blanca CORS + verificación de Origin + HSTS | Service |
| Entrada | AttackGuard (XSS 11 patrones / path traversal 7 patrones / inyección de cabeceras) | Service + Admin |
| Inyección | SQLGuard (detección de patrones de inyección SQL) | Service |
| Saneamiento | ValidationMiddleware (strip_tags) | Service |
| Autenticación | JWT Bearer + bcrypt + vinculación IP/UA + rotación de refresh | Service |
| Autenticación | Doble canal Session + JWT + Token CSRF | Admin |
| Autorización | RBAC (roles + JSON de permisos) | Admin |
| Limitación | RateLimit (ventana deslizante) + LoginThrottle (5 veces→15 minutos) | Service + Admin |
| Sesión | SessionLimit (máximo 3 tokens activos) + lista negra | Service |
| Cifrado | EncryptionMiddleware (transmisión) + Encryptable (almacenamiento) | Service |
| Replay | ReplayGuard (Nonce+Timestamp ±5min, lado no navegador) | Service + clientes |
| Resiliencia | CircuitBreaker (por plataforma: 5 fallos → OPEN → 30s semiabierto) + GuardedAdapter (fast-fail de degradación) | Service |
| Auditoría | Trazabilidad de operaciones (IP/UA/plataforma) | Admin |
| Enmascaramiento | Ocultación de campos sensibles en logs (password/token/secret → ***) | Service |

### 6.2 Identificación de plataforma de cliente

A través de la cabecera `X-Client-Platform`:

| Valor | Origen |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | HarmonyOS App |

---

## 7. Mecanismo de enrutamiento por versión de API

El número de versión no aparece en la ruta URL. La versión se transmite mediante la cabecera `X-API-Version`, y `VersionMiddleware` la lee y establece `$request->apiVersion`. La función auxiliar `versioned()` reemplaza en tiempo de ejecución el segmento de versión en la clase del controlador por la versión de la solicitud.

```
请求: GET /api/campaigns
Header: X-API-Version: v1

VersionMiddleware → $request->apiVersion = 'v1'
versioned(CampaignController::class, 'index')
  → controller\v1\CampaignController::index()
```

---

## 8. Programación de tareas programadas

| Tarea | Cron | Función |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | Renovar tokens OAuth caducados |
| DataSyncTask | `*/10 * * * *` | Sincronizar Campaigns→AdGroups→Creatives→Reports→limpiar caché |
| AlertCheckTask | `*/5 * * * *` | Evaluar reglas de alerta y disparar notificaciones |
| BidCheckTask | `*/10 * * * *` | Evaluar reglas de oferta y ejecutar ajuste de presupuesto/iniciar-detener |
| RetrySyncTask | `*/3 * * * *` | Reintentar sincronizaciones fallidas (máximo 3 veces, backoff exponencial) |

---

## 9. Integración de paquetes Erik Stack

| Paquete | Ubicación de integración | Uso |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10 modelos (SnowflakeTrait) + admin helpers.php | Generación de claves primarias |
| `erikwang2013/hashids` | ApiResponse + 2 controladores de Admin | Codificación de IDs |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | Tokens de autenticación |
| `erikwang2013/encryption` | EncryptionMiddleware | Cifrado/descifrado en transmisión |
| `erikwang2013/encryptable` | Modelos PlatformAccount + AuthToken | Cifrado de campos de BD |
| `erikwang2013/webman-scout` | Modelo Campaign (trait Searchable) | Búsqueda ES |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | Banderas de países |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | Código de verificación deslizante |
| `hg/apidoc` | Anotaciones → generación de documentos (Web UI: :8788/apidoc) | Documentación de API |

---

## 10. Arquitectura de alta concurrencia

### 10.1 Capa de base de datos

| Optimización | Descripción |
|------|------|
| Separación lectura/escritura | Base de datos principal `shared`（escritura）+ réplica de solo lectura `read_replica`（consultas de reportes/análisis） |
| Conexiones persistentes | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent` para evitar handshakes TCP frecuentes |
| Precalentamiento de conexiones | Ejecutar `SELECT 1` al iniciar el worker; aceptar solicitudes después de que el pool esté listo |

### 10.2 Capa de caché

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 Cola de mensajes

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4 canales: `sync` | `report` | `export` | `notification`

### 10.4 Escalado horizontal

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

- **keepalive**: 32 conexiones largas reutilizadas
- **failover**: `proxy_next_upstream` conmutación por error automática, 2 reintentos
- **Limitación**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 CDN de recursos estáticos

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — archivos js/css precomprimidos
- Conexión a CDN en producción (CloudFront/Aliyun CDN)

### 10.6 Aceleración CDN de materiales

Ensamblaje de URL, estrategias de caché y purga: ver [capítulo 12 Almacenamiento de materiales y aceleración CDN](#12-almacenamiento-de-materiales-y-aceleración-cdn).

---

## 11. Despliegue y CI/CD

### Servicios Docker

| Servicio | Puerto | Imagen |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP Syntax → PHPUnit → TypeScript → Docker Build
- **CD** (`.github/workflows/deploy.yml`): Docker Buildx → GHCR Push (service/admin/admin-php) → Deploy

---

## 12. Almacenamiento de materiales y aceleración CDN

### 12.1 Capa de abstracción de almacenamiento

`service/plugin/ads-storage/` ofrece una fachada `Storage` unificada + interfaz `StorageDriver` (put/delete/signedUrl/publicUrl/putFile/deleteUrl/purge), cambiando la implementación según el driver:

| driver | implementación | uso |
|--------|----------------|-----|
| `local` | LocalStorage | Predeterminado, local `public/uploads/assets/` |
| `oss` | AlibabaOssStorage | Alibaba Cloud OSS |
| `cos` | TencentCosStorage | Tencent Cloud COS (protocolo S3) |
| `s3` | S3CompatibleStorage | Compatible S3: AWS S3 / Cloudflare R2 / MinIO |

La distribución prioriza el proveedor predeterminado en la BD (configurable en el panel); si no, vuelve a env/local.

### 12.2 Gestión de proveedores CDN

Nueva tabla `ads_cdn_providers` (name/driver/bucket/region/endpoint/access_key/secret_key/cdn_domain/cdn_driver/cdn_token/enabled/is_default/status):

- Las credenciales (access_key/secret_key/cdn_token) se cifran por campo con `Erikwang2013\Encryptable`; la API solo devuelve campos enmascarados
- Solo el tenant principal de la plataforma (tenantId=1) puede gestionar (AdminMiddleware); 8 endpoints en `/api/admin/cdn/providers`: listar/crear/actualizar/eliminar/predeterminado/activar-desactivar/prueba de conectividad/purga de caché
- purge está realmente implementado para cdn_driver `aliyun` (firma OpenAPI); cloudflare/cloudfront pendientes

### 12.3 Ensamblaje de URL

`ads_assets.url` siempre guarda una ruta relativa (`/uploads/assets/...`); al leer se antepone el `cdn_domain` del proveedor predeterminado formando una URL HTTPS completa (`https://{cdn_domain}/{url}`); sin CDN se devuelve tal cual.

### 12.4 Estrategia de caché

| tipo | estrategia |
|------|------------|
| imágenes | caché larga `immutable` (nombres aleatorios, URL únicas — seguro) |
| vídeo | caché corta + soporte Range (reproducción por segmentos) |

Al eliminar un material, su URL se purga automáticamente de la caché CDN.

### 12.5 Aislamiento de rutas multi-tenant

Las claves de materiales llevan un prefijo de aislamiento por tenant y se agrupan por tenant_id; los materiales de distintos tenants son invisibles entre sí.

### 12.6 Carga directa pre-firmada y migración

- `POST /api/assets/presign`: obtiene una URL de carga pre-firmada (el cliente sube directo al almacenamiento de objetos, p. ej. vídeos de 50 MiB); formato de `key`: `Ymd/32hex.extensión`
- `POST /api/assets/register`: registra un material subido directamente; el formato de key se valida estrictamente contra path traversal
- presign no disponible con el driver `local` (sin firma de almacenamiento de objetos)
- `service/scripts/backfill-assets.php`: copia los materiales locales existentes al almacenamiento de objetos (`--dry-run` para vista previa); la columna `url` no cambia

### 12.7 Ruta de origen

`service/config/static.php` activa el servicio de archivos estáticos de webman; `/uploads/assets` se sirve directamente por HTTP en 8788 como ruta de origen del CDN.
