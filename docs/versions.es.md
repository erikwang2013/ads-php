# Comparación de versiones

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| Versión | Licencia | Cómo obtenerla |
|------|------|----------|
| **简化版 (Lite)** | Código abierto (MIT) | Repositorio público de GitHub |
| **标准版 (Standard)** | Licencia comercial | Contactar erik@erik.xyz |
| **完整版 (Full)** | Licencia comercial | Contactar erik@erik.xyz |

---

## Comparación de funciones

### Funciones básicas

| Función | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Autenticación (login/renovación de token/usuario actual) | ✅ | ✅ | ✅ |
| Gestión de plataformas (lista de 29 plataformas + OAuth) | ✅ | ✅ | ✅ |
| Gestión de cuentas (CRUD + sincronización) | ✅ | ✅ | ✅ |
| Campañas publicitarias (CRUD + iniciar/detener + lote) | ✅ | ✅ | ✅ |
| Reportes (panel de control + personalizado + exportación CSV/Excel/PDF) | ✅ | ✅ | ✅ |
| Verificación de salud + documentación de API + código de verificación | ✅ | ✅ | ✅ |
| Sincronización de datos (Campaign + Report) | ✅ | ✅ | ✅ |

### Gestión de campañas

| Función | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Grupos de anuncios (CRUD + iniciar/detener) | — | ✅ | ✅ |
| Creatividades publicitarias (lista + detalle) | — | ✅ | ✅ |
| Sincronización de datos de grupos de anuncios/creatividades | — | ✅ | ✅ |

### Monitoreo y notificaciones

| Función | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Motor de reglas de alerta (7 métricas/4 condiciones/3 alcances) | — | ✅ | ✅ |
| Registros de alerta + confirmación + no leídos | — | ✅ | ✅ |
| Centro de notificaciones (lista/leídas/todas leídas) | — | ✅ | ✅ |

### Funciones avanzadas

| Función | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Motor de reglas de oferta automática (3 acciones/enfriamiento) | — | — | ✅ |
| Plantillas de segmentación de audiencia (JSON Schema común) | — | — | ✅ |
| Biblioteca de materiales publicitarios (subida/galería/vista previa) | — | — | ✅ |
| Alerta de presupuesto (alertas en tres niveles 50/80/100%) | — | — | ✅ |
| Calendario de campañas (visualización Gantt) | — | — | ✅ |
| Atribución multiplataforma (5 modelos/retroceso de 30 días) | — | — | ✅ |

---

## Comparación de protección de seguridad

| Ítem de protección | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| Lista blanca CORS | ✅ | ✅ | ✅ |
| Cabeceras de seguridad de respuesta (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| Enrutamiento por versión (X-API-Version) | ✅ | ✅ | ✅ |
| Limitación de tráfico de la API (ventana deslizante) | ✅ | ✅ | ✅ |
| Detección de inyección SQL (coincidencia de patrones) | ✅ | ✅ | ✅ |
| Filtrado de entrada (strip_tags + trim) | ✅ | ✅ | ✅ |
| Cifrado/descifrado en transmisión (X-Encrypted) | ✅ | ✅ | ✅ |
| Autenticación JWT Bearer | ✅ | ✅ | ✅ |
| Detección de ataques XSS (11 patrones) | — | ✅ | ✅ |
| Detección de path traversal (7 patrones) | — | ✅ | ✅ |
| Detección de inyección de cabeceras | — | ✅ | ✅ |
| Límite de tamaño del body (10 MiB) | — | ✅ | ✅ |
| Lista blanca Content-Type | — | ✅ | ✅ |
| Identificación de origen del cliente (8 extremos) | — | ✅ | ✅ |
| Limitación de inicio de sesión (5 veces→15 minutos) | — | ✅ | ✅ |
| Monitoreo del tiempo de respuesta (X-Response-Time) | — | ✅ | ✅ |
| Verificación de Origin/Referer | — | — | ✅ |
| Protección contra ataques de replay (Nonce+Timestamp) | — | — | ✅ |
| Límite de sesiones concurrentes (máximo 3) | — | — | ✅ |
| Token CSRF (lado Admin) | — | — | ✅ |
| Protección SSRF (lista blanca OAuth) | — | — | ✅ |
| Enmascaramiento de datos en logs | — | — | ✅ |
| Vinculación de IP/UA en JWT | — | — | ✅ |

---

## Comparación de cadenas de middleware

### Lado Service

| Lite (7 capas) | Standard (11 capas) | Full (15 capas) |
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

### Lado Admin

| Lite (1 capa) | Standard (4 capas) | Full (5 capas) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## Comparación de tareas programadas

| Tarea | Frecuencia | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55min | ✅ | ✅ | ✅ |
| DataSyncTask | 10min | ✅ (solo Campaign+Report) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5min | — | ✅ | ✅ |
| BidCheckTask | 10min | — | — | ✅ |
| BudgetCheckTask | 15min | — | — | ✅ |

---

## Comparación de tablas de base de datos

| Categoría | Nombre de tabla | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| Base | erik_tenants | ✅ | ✅ | ✅ |
| Cuentas | erik_platform_accounts | ✅ | ✅ | ✅ |
| | erik_auth_tokens | ✅ | ✅ | ✅ |
| Campañas | erik_campaigns | ✅ | ✅ | ✅ |
| | erik_report_metrics | ✅ | ✅ | ✅ |
| | erik_report_extras | ✅ | ✅ | ✅ |
| | erik_ad_groups | — | ✅ | ✅ |
| | erik_creatives | — | ✅ | ✅ |
| Alertas | erik_alert_rules | — | ✅ | ✅ |
| | erik_alert_logs | — | ✅ | ✅ |
| Notificaciones | erik_notifications | — | ✅ | ✅ |
| Ofertas | erik_bid_rules | — | — | ✅ |
| | erik_bid_logs | — | — | ✅ |
| Segmentación | erik_targeting_templates | — | — | ✅ |
| Materiales | erik_assets | — | — | ✅ |
| Atribución | erik_conversions | — | — | ✅ |
| | erik_attribution_results | — | — | ✅ |
| Sistema | erik_sync_errors | ✅ | ✅ | ✅ |
| Administración | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **Total** | | **8** | **13** | **18** |

---

## Comparación de páginas de frontend

### Vue Admin SPA

| Página | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Inicio de sesión | ✅ | ✅ | ✅ |
| Panel de control | ✅ | ✅ | ✅ |
| Lista de cuentas + vinculación | ✅ | ✅ | ✅ |
| Campañas publicitarias | ✅ | ✅ | ✅ |
| Exportación de reportes | ✅ | ✅ | ✅ |
| Gestión de usuarios | ✅ | ✅ | ✅ |
| Logs de auditoría | ✅ | ✅ | ✅ |
| Grupos de anuncios | — | ✅ | ✅ |
| Creatividades publicitarias | — | ✅ | ✅ |
| Análisis de reportes (ECharts) | — | ✅ | ✅ |
| Reglas de alerta | — | ✅ | ✅ |
| Registros de alerta | — | ✅ | ✅ |
| Centro de notificaciones | — | ✅ | ✅ |
| Ofertas automáticas | — | — | ✅ |
| Biblioteca de materiales | — | — | ✅ |
| Calendario de campañas | — | — | ✅ |
| Análisis de atribución | — | — | ✅ |
| **Total** | **7** | **13** | **17** |

### Flutter

| Página | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Inicio de sesión | ✅ | ✅ | ✅ |
| Panel de control | ✅ | ✅ | ✅ |
| Campañas publicitarias (lista+detalle) | ✅ | ✅ | ✅ |
| Reportes de datos | ✅ | ✅ | ✅ |
| Cuentas de plataforma | ✅ | ✅ | ✅ |
| Gestión de alertas | ✅ | ✅ | ✅ |
| Grupos de anuncios | — | ✅ | ✅ |
| Creatividades publicitarias | — | ✅ | ✅ |
| Análisis de reportes | — | ✅ | ✅ |
| Centro de notificaciones | — | ✅ | ✅ |
| Ofertas automáticas | — | — | ✅ |
| **Total** | **6** | **10** | **11** |

---

## Comparación de endpoints de API

| Módulo | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Sistema (health/ping/docs/captcha) | 6 | 6 | 6 |
| Autenticación (login/me/refresh) | 3 | 3 | 3 |
| Plataformas (list/oauthUrl/callback) | 3 | 3 | 3 |
| Cuentas (index/show/destroy/sync) | 4 | 4 | 4 |
| Campañas publicitarias (CRUD/toggle/batch) | 6 | 6 | 6 |
| Grupos de anuncios (CRUD/toggle) | — | 5 | 5 |
| Creatividades (index/show) | — | 2 | 2 |
| Reportes (summary/custom/export×2) | 4 | 4 | 4 |
| Reportes (calendar/budget/attribution/models) | — | — | 4 |
| Alertas (rules CRUD + logs + acknowledge + unread) | — | 7 | 7 |
| Notificaciones (index/unread/read/readAll) | — | 4 | 4 |
| Ofertas automáticas (CRUD + logs) | — | — | 5 |
| Plantillas de segmentación (CRUD) | — | — | 5 |
| Biblioteca de materiales (index/upload/show/destroy) | — | — | 4 |
| **Total** | **26** | **44** | **62** |

---

## Pila tecnológica

Las tres versiones comparten una pila tecnológica unificada:

| Capa | Tecnología |
|----|------|
| Framework de backend | webman v2, PHP 8.2+ |
| Base de datos | MySQL 8.0 (InnoDB, utf8mb4) |
| Caché | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| Autenticación | erikwang2013/jwt-webman |
| Generación de IDs | erikwang2013/snowflake-php |
| Codificación de IDs | erikwang2013/hashids |
| Frontend | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| Despliegue | Docker + Nginx + Docker Compose |

---

## Ruta de actualización

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
