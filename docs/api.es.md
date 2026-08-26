# Documentación de la interfaz de API

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **Documentación en línea hg/apidoc**: tras iniciar el servicio, acceda a `http://127.0.0.1:8788/apidoc`（cambio entre las dos aplicaciones Service + Admin）  
> Archivo de configuración: `service/config/plugin/hg/apidoc/app.php`

---

## Convenciones generales

### Base URL

```
http://your-domain.com/api
```

### Headers obligatorios

| Header | Valor | Descripción |
|--------|----|------|
| `X-API-Version` | `v1` | Número de versión de la API（obligatorio, no aparece en la ruta URL） |
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | Extremo de origen de la operación（obligatorio） |
| `Authorization` | `Bearer <token>` | Token de autenticación JWT（obligatorio excepto inicio de sesión/lista de plataformas/verificación de salud） |

### Headers anti-replay（lado no navegador）

| Header | Descripción |
|--------|------|
| `X-Nonce` | Cadena aleatoria（única por solicitud） |
| `X-Timestamp` | Marca de tiempo Unix en segundos（ventana de ±5 minutos） |

### Headers opcionales

| Header | Descripción |
|--------|------|
| `X-Tenant-Id` | ID de tenant（modo multi-tenant） |
| `X-Encrypted` | `1` = el cuerpo de la solicitud debe descifrarse y el cuerpo de la respuesta debe cifrarse |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| Valor | Descripción |
|----|------|
| `application/json` | Cuerpo de solicitud JSON（recomendado） |
| `application/x-www-form-urlencoded` | Solicitud de formulario |
| `multipart/form-data` | Subida de archivos |

### Formato de respuesta

**Éxito**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**Paginación**:
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

**Error**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**Verificación de salud**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### Códigos de estado HTTP

| Código de estado | Significado |
|--------|------|
| 200 | Éxito |
| 204 | Preflight OPTIONS exitoso |
| 400 | Error de parámetros de solicitud, versión de API no soportada |
| 401 | No autenticado, Token caducado, IP/UA del Token no coinciden |
| 403 | Acceso prohibido（XSS/path traversal/CSRF/inyección SQL/Origin no coincide） |
| 404 | Recurso inexistente |
| 429 | Demasiadas solicitudes（limitación de tráfico/límite de inicio de sesión/límite de sesiones concurrentes） |
| 500 | Error del servidor |
| 503 | Servicio degradado（DB o Redis no disponible） |

### Parámetros de paginación

| Parámetro | Valor por defecto | Máximo | Descripción |
|------|--------|--------|------|
| `page` | 1 | — | Número de página |
| `per_page` | 20 | 100 | Elementos por página（se trunca automáticamente si supera） |
| `sort` | `id` | — | Campo de ordenación（debe estar en la lista blanca） |

### Estrategia de caché

| Endpoint | TTL | Capa |
|------|-----|-----|
| `/api/platforms` | 1 hora | L1 memoria → L2 APCu → L3 Redis |
| `/api/accounts` + `/api/accounts/:id` | 5 minutos | Igual que arriba |
| `/api/reports/summary` | 5 minutos | Igual que arriba |
| `/api/alerts/rules` | 2 minutos | Igual que arriba |
| `/api/alerts/unread-count` | 30 segundos | Igual que arriba |

---

## Módulo 1: Sistema

### GET /health — Verificación de salud

```
GET /health
```

**Respuesta**:
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

- `status`: `healthy` (200) o `degraded` (503)
- Sin requisitos de autenticación, no pasa por el enrutamiento de versiones

---

### GET /ping — Comprobación de actividad

```
GET /ping
```

**Respuesta**: `{ "pong": true }`

---

### GET /docs — Documentación de API

```
GET /docs
```

Devuelve una página de documentación de API en formato HTML（sin autenticación）。

---

### GET /api/captcha/generate — Generar código de verificación

Sin autenticación.

**Respuesta**:
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

- El token es válido 5 minutos
- Tolerancia de desplazamiento 5px

---

### POST /api/captcha/verify — Verificar código de verificación

Sin autenticación.

**Solicitud**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**Respuesta**: `{ "code": 0, "message": "验证通过" }`

---

## Módulo 2: Autenticación

### POST /api/auth/login — Inicio de sesión

Sin autenticación.

**Solicitud**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**Respuesta**:
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

- El Token JWT es válido 24 horas
- El Token incorpora hash de IP + User-Agent
- 5 intentos fallidos → bloqueo en Redis de 15 minutos

---

### GET /api/auth/me — Usuario actual

**Cabecera de solicitud**: `Authorization: Bearer <token>`

**Respuesta**:
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

### POST /api/auth/refresh — Renovar Token

**Cabecera de solicitud**: `Authorization: Bearer <old_token>`

**Respuesta**:
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

- El Token antiguo se añade automáticamente a la lista negra
- Máximo 3 tokens activos por usuario

---

## Módulo 3: Plataformas y cuentas

### GET /api/platforms — Lista de plataformas

Sin autenticación. Caché de 1 hora.

**Respuesta**:
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

### GET /api/platforms/:code/oauth-url — URL de autorización OAuth

**Parámetros**: `?redirect_uri=https://your-domain.com/callback`

**Respuesta**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` debe pasar la validación de la lista blanca SSRF（variable de entorno `OAUTH_ALLOWED_REDIRECTS`）

---

### POST /api/platforms/:code/callback — Callback OAuth

**Solicitud**: `{ "state": "...", "code": "..." }`

**Respuesta**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/accounts — Lista de cuentas

Caché de 5 minutos.

**Parámetros**:

| Parámetro | Descripción |
|------|------|
| `platform` | Filtro por código de plataforma |
| `page` | Número de página |
| `per_page` | Elementos por página |

**Respuesta**: formato de paginación; cada elemento de la lista contiene `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at`

---

### GET /api/accounts/:id — Detalle de cuenta

Caché de 5 minutos.

---

### DELETE /api/accounts/:id — Desvincular cuenta

---

### POST /api/accounts/:id/sync — Sincronización manual

---

## Módulo 4: Campañas publicitarias

### GET /api/campaigns — Lista de campañas

**Parámetros**:

| Parámetro | Descripción | Valores posibles |
|------|------|--------|
| `platform` | Filtro por plataforma | juliang, meta, google... |
| `status` | Filtro por estado | enabled, paused |
| `keyword` | Búsqueda por nombre | Texto arbitrario |
| `sort` | Campo de ordenación | id, name, platform, daily_budget, status, created_at |
| `page` | Número de página | — |
| `per_page` | Elementos por página | ≤100 |

**Respuesta**: formato de paginación + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/campaigns — Crear campaña

**Solicitud**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**Respuesta**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- Unidad de `daily_budget`: centavos（20000 = ¥200.00）

---

### GET /api/campaigns/:id — Detalle de campaña

**Respuesta**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/campaigns/:id — Actualizar campaña

**Solicitud**: `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/campaigns/:id/toggle — Iniciar/detener campaña

**Solicitud**: `{ "enabled": false }`

---

### POST /api/campaigns/batch/toggle — Iniciar/detener en lote

**Solicitud**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**Respuesta**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## Módulo 5: Grupos de anuncios

### GET /api/ad-groups — Lista de grupos de anuncios

**Parámetros**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/ad-groups — Crear grupo de anuncios

**Solicitud**:
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

- `targeting_template_id`: opcional, carga el targeting JSON desde la plantilla de segmentación y lo combina

### GET /api/ad-groups/:id — Detalle de grupo de anuncios

### PUT /api/ad-groups/:id — Actualizar grupo de anuncios

### POST /api/ad-groups/:id/toggle — Iniciar/detener grupo de anuncios

---

## Módulo 6: Creatividades

### GET /api/creatives — Lista de creatividades

**Parámetros**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/creatives/:id — Detalle de creatividad

---

## Módulo 7: Reportes

### GET /api/reports/summary — Resumen del panel de control

Caché de 5 minutos.

**Parámetros**: `date_start`, `date_end`

**Respuesta**:
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

### GET /api/reports/custom — Reporte personalizado

**Parámetros**:

| Parámetro | Descripción |
|------|------|
| `dimensions[]` | Dimensiones: date, platform, campaign |
| `metrics[]` | Métricas: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | Fecha de inicio |
| `date_end` | Fecha de fin |
| `platform` | Filtro por plataforma |

---

### GET /api/reports/export — Exportar reporte

**Parámetros**: `format=csv`, `date_start`, `date_end`, `metrics[]`

Devuelve una descarga de archivo（CSV UTF-8 BOM o Excel .xls）。

---

### GET /api/reports/export-dashboard — Exportar panel de control a PDF

---

### GET /api/reports/calendar — Calendario de campañas

**Parámetros**: `date_start`, `date_end`, `platform`

**Respuesta**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/reports/budget-alerts — Alerta de presupuesto

**Respuesta**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/reports/attribution — Análisis de atribución

**Parámetros**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**Respuesta**:
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

### GET /api/reports/attribution/models — Lista de modelos de atribución

**Respuesta**: `[{ code: "last_touch", name: "末次触点", description: "..." }]`

Hay 5 modelos en total.

---

## Módulo 8: Alertas

### GET /api/alerts/rules — Lista de reglas de alerta

Caché de 2 minutos.

**Parámetros**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/alerts/rules — Crear regla de alerta

**Solicitud**:
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

### PUT /api/alerts/rules/:id — Actualizar regla de alerta

### DELETE /api/alerts/rules/:id — Eliminar regla de alerta

### GET /api/alerts/logs — Registros de alerta

**Parámetros**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/alerts/logs/:id/acknowledge — Confirmar alerta

### GET /api/alerts/unread-count — Número de alertas no leídas

Caché de 30 segundos. El frontend consulta cada 30s.

---

## Módulo 9: Notificaciones

### GET /api/notifications — Lista de notificaciones

**Parámetros**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/notifications/unread-count — Número de notificaciones no leídas

### POST /api/notifications/:id/read — Marcar como leída

### POST /api/notifications/read-all — Marcar todas como leídas

---

## Módulo 10: Ofertas automáticas

### GET /api/bid-rules — Lista de reglas

### POST /api/bid-rules — Crear regla

**Solicitud**:
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

**Descripción de campos**:

| Campo | Tipo | Descripción |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Métrica monitoreada |
| condition | gt/gte/lt/lte | Condición de disparo |
| threshold | decimal | Umbral |
| action_type | adjust_budget/toggle_pause/toggle_enable | Tipo de acción |
| adjust_step | int (min) | Paso de ajuste de presupuesto（positivo=aumentar, negativo=disminuir） |
| budget_min | int | Límite inferior del presupuesto（centavos） |
| budget_max | int | Límite superior del presupuesto（centavos） |
| cooldown_minutes | int | Tiempo de enfriamiento（por defecto 60） |

### PUT /api/bid-rules/:id — Actualizar regla

### DELETE /api/bid-rules/:id — Eliminar regla

### GET /api/bid-rules/logs — Historial de ofertas

**Parámetros**: `rule_id`, `campaign_id`

---

## Módulo 11: Plantillas de segmentación

### GET /api/targeting-templates — Lista de plantillas

**Parámetros**: `platform`

### GET /api/targeting-templates/:id — Detalle de plantilla

### POST /api/targeting-templates — Crear plantilla

**Solicitud**:
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

### PUT /api/targeting-templates/:id — Actualizar plantilla

### DELETE /api/targeting-templates/:id — Eliminar plantilla

---

## Módulo 12: Biblioteca de materiales

### GET /api/assets — Lista de materiales

**Parámetros**: `type`(image/video), `page`, `per_page`

### POST /api/assets/upload — Subir material

**Solicitud**: `multipart/form-data`, campo `file`

- Imágenes: máximo 5 MB (jpeg/png/gif/webp)
- Videos: máximo 50 MB (mp4)

**Respuesta**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

### GET /api/assets/:id — Detalle de material

### DELETE /api/assets/:id — Eliminar material

---

## Endpoints de Admin（puerto 8789）

### POST /api/admin/login — Inicio de sesión de administrador

**Solicitud**: `{ "username": "admin", "password": "..." }`

**Respuesta**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- El Token se guarda en localStorage
- `csrf_token` debe enviarse en la cabecera `X-CSRF-Token` de las solicitudes POST/PUT/DELETE posteriores

### GET /api/admin/me — Administrador actual

### POST /api/admin/logout — Cerrar sesión

### GET /api/admin/users — Lista de usuarios

**Parámetros**: `keyword`, `role_id`, `page`, `per_page`

En la respuesta, `id` y `role_id` están codificados con hashids.

### POST /api/admin/users — Crear usuario

### PUT /api/admin/users/:id — Actualizar usuario

### DELETE /api/admin/users/:id — Deshabilitar usuario

### GET /api/admin/users/roles — Lista de roles

### GET /api/admin/audit-logs — Logs de auditoría

**Parámetros**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

## Referencia de códigos de error

| code | HTTP | Descripción |
|------|------|------|
| 0 | 200 | Éxito |
| 1 | 200/400 | Error de negocio general |
| 401 | 401 | No autenticado / Token caducado / IP/UA no coinciden |
| 403 | 403 | Acceso prohibido（intercepción de seguridad） |
| 404 | 404 | Recurso inexistente |
| 422 | 422 | Fallo de validación de parámetros |
| 429 | 429 | Demasiadas solicitudes / límite de inicio de sesión / límite de concurrencia |
| 1001 | 200 | Fallo de autenticación（nombre de usuario o contraseña incorrectos） |

---

## Respuesta de intercepción de seguridad

Cuando una solicitud es interceptada por el middleware de seguridad, devuelve 403:

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## Respuesta de limitación de tráfico

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

La cabecera `Retry-After` contiene los segundos restantes de espera.
