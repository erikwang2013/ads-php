# Ads Platform — Sistema de gestión de anuncios multiplataforma

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## Resumen

Integración con **29 plataformas publicitarias**, gestión unificada de la entrega de anuncios y reportes de datos multiplataforma, con soporte de monitoreo de alertas, ofertas automáticas y acceso multi-dispositivo.

> Diseño de arquitectura → [docs/architecture.es.md](docs/architecture.es.md)  
> Módulos funcionales → [docs/features.es.md](docs/features.es.md)  
> Documentación de API → [docs/api.es.md](docs/api.es.md) | hg/apidoc: `http://127.0.0.1:8788/apidoc`  
> Comparación de versiones → [docs/versions.es.md](docs/versions.es.md)（Lite código abierto / Standard y Full contactar erik@erik.xyz）

### Plataformas compatibles

#### Nacionales (16)
| Plataforma | Adaptador | Autenticación |
|------|--------|------|
| 巨量引擎 | Juliang | OAuth2 Access-Token |
| 百度营销 | Baidu | OAuth2 + firma en sobre |
| 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5 |
| 腾讯广告 | Tencent | OAuth2 + nonce |
| 快手磁力引擎 | Kuaishou | OAuth2 parámetros URL |
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
| 拼多多广告 | Pinduoduo | OAuth2 + Sign personalizado |

#### Internacionales (13)
| Plataforma | Adaptador | Autenticación |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 parámetros URL |
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

## Pila tecnológica

| Capa | Tecnología | Descripción |
|----|------|------|
| Servidor | webman v2 + PHP 8.2+ | 7 plugins, 65+ endpoints de API |
| Base de datos | MySQL 8.0 | 28 tablas, prefijo erik_, claves primarias BIGINT Snowflake |
| Caché | Redis 7 | Caché de tres niveles (L1 memoria / L2 APCu / L3 Redis), contador de limitación de tráfico, Pub/Sub, cola de mensajes |
| Búsqueda | Elasticsearch | Sincronización automática de índice webman-scout (configurado) |
| Panel de administración | webman-admin v2 + Vue 3 + TypeScript + Element Plus | Backend PHP (puerto 8789), SPA conecta directamente a la API de negocio (puerto 8788), 19 páginas, visualización ECharts |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | Responsive PC/Móvil, diseño Shell de escritorio, 12 páginas |
| HarmonyOS | ArkTS + ArkUI | 6 páginas implementadas, cliente HTTP listo |
| Despliegue | Docker + Nginx + GHCR | Docker Compose de un solo clic, GitHub Actions construcción y publicación automática |

## Diagrama de arquitectura

![Diagrama de arquitectura del sistema](docs/diagrams/svg/architecture.es.svg)

### Diagrama de flujo de solicitudes

![Diagrama de flujo de solicitudes](docs/diagrams/svg/request-flow.es.svg)

### Diagrama de módulos funcionales

![Diagrama de módulos funcionales](docs/diagrams/svg/functional-modules.es.svg)

### Diagrama del ciclo de vida de datos

![Diagrama del ciclo de vida de datos](docs/diagrams/svg/data-lifecycle.es.svg)

> La versión completa incluye todas las anotaciones de detalle, el pipeline del lado Admin, el diagrama de Gantt de tareas programadas y la máquina de estados de caché → [docs/diagrams/](docs/diagrams/) |

> Explicación detallada de arquitectura, arquitectura de seguridad y diseño de alta concurrencia en el [documento de diseño de arquitectura](docs/architecture.es.md) | Especificaciones de diseño históricas en [design.md](docs/superpowers/specs/design.es.md)

## Explicación de la arquitectura

- **`service/`** — servicio de API de negocio para usuarios basado en webman v2, escucha en el puerto **8788**. Gestiona la integración con plataformas publicitarias, autorización OAuth, sincronización de datos, motor de reportes, monitoreo de alertas y demás lógica de negocio.
- **`admin/`** — panel de administración independiente basado en webman-admin v2, escucha en el puerto **8789**. Incluye backend PHP (autenticación y autorización, gestión de usuarios, configuración del sistema) y frontend SPA Vue 3.
- **Comunicación entre el panel de administración y el servicio de negocio** — el SPA Vue se conecta directamente a la API de service a través de axios (baseURL `/api`); las rutas exclusivas del admin (`/api/admin/*`) son servidas por el backend PHP de admin (8789), y Nginx distribuye según la ruta.
- **Modo desarrollo** — el servidor de desarrollo Vite (puerto 5173) hace proxy de `/api` a service:8788; el backend PHP de admin proporciona autenticación de sesión y servicio estático del SPA en 8789.
- **Modo producción** — Nginx enruta `/` a admin:8789 (SPA del panel de administración) y `/api/` a service:8788 (API de negocio).

## Integración Erik Stack

| Paquete | Uso |
|----|------|
| `erikwang2013/snowflake-php` | Generación de IDs distribuidos Snowflake |
| `erikwang2013/hashids` | Cifrado/descifrado de parámetros de ID en la API |
| `erikwang2013/jwt-webman` | Tokens de autenticación JWT |
| `erikwang2013/encryption` | Cifrado/descifrado de datos sensibles en la capa de API |
| `erikwang2013/encryptable` | Cifrado/descifrado automático a nivel de campo de BD |
| `erikwang2013/webman-scout` | Sincronización de datos con Elasticsearch |
| `erikwang2013/season` | Identificación de banderas de países |
| `erikwang2013/poster-php` | Código de verificación deslizante (protección de inicio de sesión) |
| `hg/apidoc` | Generación automática de documentación de API (anotaciones + interfaz web) |

## Internacionalización

Toda la interfaz admite cambio bilingüe entre **chino (zh-CN)** / **English (en)**:

| Extremo | Tecnología | Método de cambio |
|----|------|---------|
| Admin | vue-i18n v9 | Menú desplegable de idioma en TopBar, persistencia en localStorage |
| Service API | `erik\support\I18n` | Cabecera de solicitud Accept-Language / parámetro `?lang=` |
| Flutter | AppLocalizations + Delegate | Detección automática del idioma del sistema |
| HarmonyOS | StringResources | Cambio con `setLang()` |

## Seguridad

### Lado Service (14 capas globales + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware (capa de rutas)

### Lado Admin (10 capas globales + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck (capa de rutas)

### Resumen de capacidades de protección (22 ítems)

| Categoría | Ítem de protección | Descripción |
|------|--------|------|
| Detección de entrada | XSS (11 patrones) | script/iframe/event handler/javascript:/data: |
| | Path traversal (7 patrones) | ../ / null byte / /etc/passwd / .env / .git |
| | Inyección de cabeceras | Detección CRLF |
| | Límite de tamaño del body | 10 MiB |
| | Lista blanca Content-Type | JSON/Form/Multipart/Plain |
| | Inyección SQL | Detección de patrones UNION/DROP/ALTER |
| Autenticación | Vinculación de token JWT | Verificación de hash IP + User-Agent |
| | Renovación de token + lista negra | Los tokens antiguos caducan automáticamente |
| | Limitación de inicio de sesión | 5 intentos fallidos → bloqueo de 15 minutos (Redis) |
| | Límite de sesiones concurrentes | Máximo 3 tokens activos por usuario |
| | Código de verificación | Código de verificación deslizante (válido 5 minutos, tolerancia 5px) |
| Validación de solicitudes | Lista blanca CORS | Lista blanca de dominios en producción |
| | Verificación Origin/Referer | Verificación de orígenes entre dominios |
| | Token CSRF | Verificación de token de sesión en el lado Admin |
| | Protección contra replay | Nonce + Timestamp ±5min (lado no navegador) |
| | Limitación de tráfico de la API | Ventana deslizante 60 veces/60s |
| | Protección SSRF | Lista blanca de redirect_uri de OAuth |
| Cabeceras de respuesta | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | Anti clickjacking + forzar HTTPS |
| | X-Content-Type-Options | nosniff |
| Protección de datos | Cifrado en tránsito | EncryptionMiddleware (X-Encrypted) |
| | Cifrado en almacenamiento | Encryptable (a nivel de campo de BD) |
| | Enmascaramiento de logs | password/token/secret → \*\*\* |

### Diagrama de arquitectura de seguridad

![Diagrama de arquitectura de seguridad](docs/diagrams/svg/security.es.svg)

**Defensa en profundidad**: capa externa (Nginx) → guardias de entrada (5 capas de middleware) → autenticación de identidad (7 ítems) → validación de entrada (4 ítems) → control de frecuencia → cifrado de datos → trazabilidad de auditoría

**Autenticación**: el servidor y admin usan de forma unificada la tabla `admin_users` + hash bcrypt, JWT 24h + rotación de refresh

**Auditoría**: todas las operaciones registran IP / User-Agent / Client-Platform / detalles de la operación

**Confirmación secundaria**: las operaciones de eliminar/desvincular/masivas usan el patrón de "palabra de confirmación de entrada" (`GlobalConfirm` + `useConfirmStore`)

---

## Funciones avanzadas

| Función | Descripción | Tecnología |
|------|------|------|
| Biblioteca de materiales | Gestión de carga de imágenes/videos, vista previa en galería, copiar URL | AssetController + galería Vue |
| Alerta de presupuesto | Seguimiento en tiempo real del consumo del presupuesto diario, alertas en tres niveles (50/80/100%) | BudgetAlertService + Cron cada 15min |
| Calendario de campañas | Diagrama de Gantt multiplataforma, vistas mensual/semanal, coloreado por plataforma | CalendarService + Gantt Vue |
| Atribución multiplataforma | Atribución de 5 modelos (first/last/linear/time_decay/position_based), retroceso de 30 días | AttributionEngine + ECharts |

---

## Alta concurrencia

| Optimización | Solución | Archivo |
|------|------|------|
| Separación lectura/escritura de BD | Base de datos principal `shared` + réplica de solo lectura `read_replica`, las SELECT se enrutan automáticamente a la réplica | `config/database.php` |
| Pool de conexiones de BD | Conexiones persistentes `PDO::ATTR_PERSISTENT` + precalentamiento de inicialización de zona horaria | `config/database.php` |
| Pool de conexiones Redis | Conexiones persistentes `persistent` + configuración de separación lectura/escritura `readonly` | `config/redis.php` |
| Caché de tres niveles | L1 memoria del proceso → L2 APCu memoria compartida → L3 Redis | `support/CacheService.php` |
| Cola de mensajes asíncrona | Redis List 4 canales (sync/report/export/notification) | `support/AsyncJobService.php` |
| Limitación de tráfico por niveles en Nginx | 30r/s + burst 20 + 20 conexiones concurrentes + keepalive 32 | `docker/nginx/admin.conf` |
| Escalado horizontal | Múltiples instancias en upstream + conmutación por error + sticky session | `docker/nginx/admin.conf` |
| Aceleración CDN | Recursos estáticos `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## Inicio rápido

### Instalación web con un clic (recomendado)

Tras iniciar el servicio, acceda a `/install` en el navegador para entrar en el asistente de instalación:

```bash
# Iniciar el panel de administración (puerto 8789)
cd admin && composer install && php start.php start

# Abrir el navegador y visitar http://localhost:8789/install
# En el asistente de instalación, complete la información de la base de datos y la cuenta de administrador, y haga clic en «Iniciar instalación»
```

El asistente de instalación le guiará en la página web:
1. **Conexión a la base de datos** — complete host de MySQL, puerto, nombre de la base de datos, usuario y contraseña; admite prueba de conexión
2. **Configuración de Redis** — complete la información de conexión de Redis (opcional)
3. **Cuenta de administrador** — configure nombre de usuario, contraseña y nombre mostrado del inicio de sesión del panel
4. **Instalación con un clic** — crea automáticamente la base de datos, ejecuta `install.sql` para crear 28 tablas, escribe los datos semilla y actualiza la contraseña del administrador

Tras la instalación, acceda a `/` para entrar en el panel de administración e inicie sesión con el nombre de usuario y contraseña configurados.

### Docker (recomendado para producción)

```bash
# Iniciar todos los servicios (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# Inicializar la base de datos (crear tablas + datos semilla)
make db-init

# Acceso
# Panel de administración: http://localhost
# Asistente de instalación: http://localhost/install
# API: http://localhost/api（Header: X-API-Version: v1）
```

### Desarrollo local

```bash
# Servidor (puerto 8788)
cd service && composer install && php start.php start

# Panel de administración (puerto 5173)
cd admin/public/web && npm install && npm run dev

# Aplicación Flutter
cd apps/flutter && flutter run -d chrome  # Web PC
# Aplicación HarmonyOS
# Usar DevEco Studio para abrir el directorio apps/harmonyos
cd apps/flutter && flutter run -d android # Mobile

# Verificación de TypeScript
cd admin/public/web && npx vue-tsc --noEmit   # cero errores
```

---

## Estructura del proyecto

```
ads-php/
├── service/                           # Servicio de negocio del lado usuario (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (61 endpoints, rutas por versión)
│   │   │   ├── controller/v1/         # 17 controladores
│   │   │   ├── middleware/            # 15 middlewares
│   │   │   ├── config/route.php       # Definición de rutas
│   │   │   └── route_helpers.php      # Función auxiliar versioned()
│   │   ├── ads-platform/              # Núcleo de adaptadores de plataforma
│   │   │   ├── adapter/               # 29 adaptadores de plataforma
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # Migraciones SQL + índices de rendimiento
│   │   ├── ads-account/               # Gestión de cuentas OAuth
│   │   ├── ads-task/                  # Programación de tareas programadas (6 cron)
│   │   ├── ads-alert/                 # Motor de monitoreo de alertas + alerta de presupuesto
│   │   ├── ads-report/                # Motor de reportes (CSV/Excel/PDF) + motor de atribución + calendario de campañas
│   │   └── ads-tenant/                # Gestión multi-tenant
│   ├── support/                       # Clases de utilidades Erik Stack
│   │   ├── ControllerTrait.php        # Trait común de controladores
│   │   ├── JwtService.php             # Clase envoltorio de JWT
│   │   ├── CacheService.php           # Servicio de caché Redis
│   │   ├── ExceptionHandler.php       # Manejador de excepciones de API
│   │   └── ApiResponse.php            # Formato de respuesta unificado
│   ├── config/                        # Configuración global (DB/Redis/Log/Middleware)
│   ├── tests/                         # Pruebas PHPUnit (35 tests)
│   │   ├── Unit/                      # Pruebas unitarias (Middleware, Task)
│   │   └── Integration/               # Pruebas de integración (Auth, Health)
│   └── start.php                      # Punto de entrada del servicio
├── admin/                             # Panel de administración independiente (webman-admin v2 :8789)
│   ├── public/web/src/
│   │   ├── views/                     # 15 páginas Vue
│   │   │   ├── dashboard/             # Panel de control (ECharts)
│   │   │   ├── campaign/              # Campañas publicitarias
│   │   │   ├── adgroup/               # Grupos de anuncios
│   │   │   ├── creative/              # Creatividades publicitarias
│   │   │   ├── report/                # Análisis de reportes + exportación
│   │   │   ├── alert/                 # Reglas de alerta + registros
│   │   │   ├── notification/          # Centro de notificaciones
│   │   │   ├── bid/                   # Reglas de oferta automática
│   │   │   └── system/                # Gestión de usuarios + logs de auditoría
│   │   ├── api/                       # 9 clientes de API
│   │   ├── stores/                    # 4 Pinia Stores
│   │   └── components/                # Componentes compartidos (ListPageLayout, etc.)
│   ├── app/                           # Backend PHP (controller/middleware)
│   └── config/                        # Configuración de Admin
├── apps/
│   ├── flutter/                       # Aplicación de escritorio Flutter
│   │   └── lib/
│   │       ├── features/              # 12 páginas funcionales + diseño Shell
│   │       ├── config/menu_config.dart # Configuración de menú de dos niveles
│   │       ├── router.dart            # GoRouter (ShellRoute + guardas de ruta)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API Client listo)
├── docker/                            # Configuración de Docker y Nginx
├── .github/workflows/                 # CI (sintaxis→tests→TS→Docker) + CD (build y push)
├── docs/                              # Documentos de diseño, planes de implementación, Skills
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## Endpoints de API

> Todas las definiciones de endpoints de API están en [docs/api.es.md](docs/api.es.md)（incluye ejemplos de solicitud/respuesta, códigos de error y políticas de limitación de tráfico）。
> Documentación en línea hg/apidoc: tras iniciar el servicio, acceda a `http://127.0.0.1:8788/apidoc`

## Base de datos

**Convención de nombres**: prefijo de tabla `erik_`, clave primaria `BIGINT UNSIGNED PRIMARY KEY`（sin autoincremento, ID Snowflake）, motor InnoDB, charset utf8mb4

| Categoría | Nombre de tabla | Uso |
|------|------|------|
| Base | `erik_tenants` | Multi-tenant |
| Cuentas | `erik_platform_accounts`, `erik_auth_tokens` | Cuentas de plataforma OAuth |
| Campañas | `erik_campaigns`, `erik_ad_groups`, `erik_creatives` | Jerarquía de entrega de anuncios |
| Reportes | `erik_report_metrics`, `erik_report_extras` | Métricas de reportes unificadas |
| Materiales | `erik_assets` | Biblioteca de materiales creativos |
| Segmentación | `erik_targeting_templates` | Plantillas de segmentación de audiencia |
| Atribución | `erik_conversions`, `erik_attribution_results` | Seguimiento de conversiones + resultados de atribución |
| Ofertas | `erik_bid_rules`, `erik_bid_logs` | Reglas de oferta automática + historial |
| Alertas | `erik_alert_rules`, `erik_alert_logs` | Monitoreo de alertas |
| Notificaciones | `erik_notifications` | Notificaciones dentro del sitio |
| Sistema | `erik_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | Errores de sincronización, RBAC, auditoría |

---

## Tareas programadas

| Tarea | Frecuencia | Función |
|------|------|------|
| TokenRefreshTask | Cada 55 minutos | Escanea tokens OAuth caducados y los renueva automáticamente |
| DataSyncTask | Cada 10 minutos | Obtiene campañas+grupos de anuncios+creatividades+reportes de cada plataforma, escribe en tablas unificadas y limpia la caché |
| AlertCheckTask | Cada 5 minutos | Recorre las reglas de alerta activadas, evalúa umbrales y dispara notificaciones |
| BidCheckTask | Cada 10 minutos | Recorre las reglas de oferta automática, consulta métricas y ejecuta ajustes de presupuesto/iniciar-detener |
| BudgetCheckTask | Cada 15 minutos | Recorre las campañas en entrega, seguimiento del consumo del presupuesto diario, alertas en tres niveles (50/80/100%) |
| RetrySyncTask | Cada 3 minutos | Reintenta tareas de sincronización fallidas (máximo 3 veces, backoff exponencial) |

---

## Pruebas

```bash
cd service && ./vendor/bin/phpunit
# 35 tests / 70 aserciones
```

**Cobertura**: middlewares (Version/SQLGuard/SecurityHeaders) · objetos de datos (CampaignData/FieldMapping/Hashids) · motores (ReportBuilder/AdapterRegistry) · pruebas de integración (Auth/Health)

```bash
# Verificación de TypeScript
cd admin/public/web && npx vue-tsc --noEmit   # cero errores

# Análisis de Dart
cd apps/flutter && dart analyze   # cero errores
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): pipeline automático — **PHP Syntax → PHPUnit → TypeScript → Docker Build**

**CD** (`.github/workflows/deploy.yml`): disparo manual — **Docker Buildx → Push a GHCR (service/admin/admin-php) → notificación de despliegue**

`.github/dependabot.yml` actualiza automáticamente cada semana las dependencias de Composer + npm + Docker.

---

## Skills

`docs/skills/` — 11 skills de proyecto reutilizables:

| Skill | Descripción |
|------|------|
| `adapter-generator` | Generar nuevos adaptadores de plataforma publicitaria (plantilla de 14 métodos) |
| `migration-generator` | Generar archivos de migración SQL (prefijo erik_ + PK BIGINT) |
| `erik-stack` | Guía de integración de los 8 paquetes de Erik Stack |
| `admin-page-generator` | Generar páginas del panel de administración Vue3 |
| `api-endpoint` | Añadir endpoints de API RESTful |
| `tdd-workflow` | Flujo de verificación TDD (prueba→implementación→sintaxis→TypeScript→commit) |
| `security-middleware` | Añadir capas de middleware de seguridad (especificación de interfaz + registro + referencia de la cadena existente) |
| `version-split` | División en tres versiones Lite/Standard/Full (pasos de operación + actualización de configuración) |
| `cache-strategy` | Estrategia de caché de tres niveles (L1 memoria / L2 APCu / L3 Redis + sugerencias de TTL) |
| `attribution-setup` | Motor de atribución multiplataforma (5 modelos + llamadas API + preparación de datos) |
| `high-concurrency` | 8 optimizaciones de alta concurrencia (separación lectura/escritura/pool de conexiones/cola de mensajes/escalado horizontal/CDN) |


## El código abierto no es fácil, bienvenido su apoyo

| WeChat | Alipay |
|:---:|:---:|
| ![微信](./docs/weixinpay.png "微信") | ![支付宝](./docs/alipay.png "支付宝") |

### 全球转账打赏 (Global Transfer Donation)

**收款人信息 (Beneficiary)**

| 字段 | 值 |
|------|-----|
| 收款人姓名 (Name) | WANG KEXUN |
| 收款账户号码 (Account No.) | 881015918251 |

**收款银行 (Receiving Bank) — ZA Bank**

| 字段 | 值 |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| 银行名称 (Bank Name) | ZA Bank Limited |
| 银行编号 (Bank Code) | 387 |
| 银行地址 (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **跨境汇款代理银行（如需，Correspondent Bank）**：Esta es la información del banco corresponsal (intermediario), no la del banco receptor. Consulte a su banco emisor si es necesario proporcionarla.
>
> - **港元、人民币及美元**：Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · 银行编号 006 · Hong Kong Branch（分行编号 391）· Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **其他币种**：THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

---

## Licencia

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
