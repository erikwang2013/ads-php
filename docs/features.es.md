# Documento de diseño funcional

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Todas las definiciones de la interfaz de API (solicitud/respuesta/parámetros) están en [api.es.md](api.es.md)。

---

## Resumen de módulos

| # | Módulo | Controlador/Servicio | N.º de rutas API | Página Vue |
|---|------|--------|-----------|----------|
| 1 | Autenticación y autorización | AuthController | 3 | LoginPage |
| 2 | Gestión de plataformas | PlatformController | 3 | — |
| 3 | Gestión de cuentas | AccountController | 5 | AccountList, AccountBind |
| 4 | Campañas publicitarias | CampaignController | 6 | CampaignList |
| 5 | Grupos de anuncios | AdGroupController | 5 | AdGroupList |
| 6 | Creatividades publicitarias | CreativeController | 2 | CreativeList |
| 7 | Reportes de datos | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | Monitoreo de alertas | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | Centro de notificaciones | NotificationController | 4 | NotificationList |
| 10 | Ofertas automáticas | BidRuleController | 5 | BidRuleList |
| 11 | Plantillas de segmentación | TargetingTemplateController | 5 | — |
| 12 | Administración del sistema | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | Sincronización de datos | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | Biblioteca de materiales | AssetController | 4 | AssetGallery |
| 15 | Alerta de presupuesto | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | Calendario de campañas | CalendarService | 1 | CampaignCalendar |
| 17 | Atribución multiplataforma | AttributionEngine | 2 | AttributionReport |
| 18 | Verificación de salud | HealthController | 2 | — |
| 19 | Código de verificación | CaptchaController | 2 | — |
| 20 | Documentación de API | DocController | 1 | — |

**Total**: 20 módulos, 65+ rutas, 18 páginas Vue

---

## Módulo 1: Autenticación y autorización

- Verificación de código de verificación (opcional)
- Consulta de la tabla `admin_users`
- Verificación con bcrypt `password_verify()`
- Generación de token JWT (TTL 24h)
- Los tokens antiguos se añaden automáticamente a la lista negra
- Extracción de `uid` del token para consultar la información del usuario

Interfaces: Inicio de sesión / Renovación de token / Usuario actual → [módulo 2 de api.es.md](api.es.md#模块-2-认证)

---

## Módulos 2-3: Gestión de plataformas y cuentas

- La lista de plataformas se cachea 1 hora (Redis), integra el emoji de bandera de Season
- Flujo OAuth: generar state aleatorio → construir URL de autorización → procesar callback → almacenar Token
- La lista/detalle de cuentas se cachea 5 minutos

Interfaces: Lista de plataformas / OAuth / CRUD de cuentas + sincronización → [módulo 3 de api.es.md](api.es.md#模块-3-平台--账户)

---

## Módulos 4-6: Jerarquía de entrega de anuncios

### Estructura de datos

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- La creación de campañas se realiza a través del adaptador de plataforma + escritura local
- Soporta filtrado por plataforma/estado/palabra clave; la lista incluye resumen del día
- La creación de grupos de anuncios admite `targeting_template_id` para cargar plantillas de segmentación

Interfaces: Campañas / Grupos de anuncios / Creatividades → [módulos 4-6 de api.es.md](api.es.md#模块-4-广告计划)

---

## Módulo 7: Reportes de datos

- El resumen del panel de control se cachea 5 minutos: 8 tarjetas de indicadores KPI + gráfico de líneas de tendencia diaria + gráfico de barras por plataforma
- Dimensiones de reportes personalizados: date, platform, campaign
- Métricas: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Formatos de exportación: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (impresión HTML)

Interfaces: Resumen / Personalizado / Exportación → [módulo 7 de api.es.md](api.es.md#模块-7-报表)

---

## Módulo 8: Monitoreo de alertas

### Flujo de evaluación de AlertEngine

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### Canales de notificación

| Canal | Estado | Implementación |
|------|------|------|
| web | ✅ | Escritura en erik_notifications |
| email | Placeholder | Stub echo |
| sms | Placeholder | Stub echo |
| Redis pub/sub | ✅ | Push JSON en el canal `alert:new` |

Interfaces: CRUD de reglas / registros de alerta / confirmación / no leídos → [módulo 8 de api.es.md](api.es.md#模块-8-告警)

---

## Módulo 9: Centro de notificaciones

- El Pinia store del frontend consulta cada 30s
- Icono de campana en la barra lateral + insignia con número de no leídos

Interfaces: Lista / no leídos / marcar como leído / marcar todo como leído → [módulo 9 de api.es.md](api.es.md#模块-9-通知)

---

## Módulo 10: Motor de ofertas automáticas

### Flujo de evaluación de BidEngine

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 冷却检查 (cooldown_minutes 内是否有过操作)
  → 执行动作:
    - adjust_budget: 新预算 = current + adjust_step, 限制 [budget_min, budget_max]
    - toggle_pause: 暂停计划
    - toggle_enable: 启用计划
  → 通过 AdapterRegistry → PlatformAdapter 调用平台 API
  → 更新本地 DB + 写入 BidLog
```

### Campos de la regla

| Campo | Tipo | Descripción |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Métrica monitoreada |
| condition | gt/gte/lt/lte | Condición de disparo |
| threshold | DECIMAL(12,2) | Umbral |
| scope | tenant/platform/campaign | Alcance |
| action_type | adjust_budget/toggle_pause/toggle_enable | Acción |
| adjust_step | INT (min) | Paso de ajuste de presupuesto (positivo=aumentar, negativo=disminuir) |
| budget_min, budget_max | BIGINT | Límites del presupuesto |
| cooldown_minutes | INT | Período de enfriamiento |

Interfaces: CRUD de reglas / historial de ofertas → [módulo 10 de api.es.md](api.es.md#模块-10-自动出价)

---

## Módulo 11: Plantillas de segmentación de audiencia

### Integración en grupos de anuncios

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### Schema JSON común

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

Interfaces: CRUD de plantillas → [módulo 11 de api.es.md](api.es.md#模块-11-定向模板)

---

## Módulo 12: Administración del sistema (Admin)

- Los IDs de la lista de usuarios se codifican con hashids
- Al crear usuarios, la contraseña se hashea con bcrypt
- Deshabilitar un usuario es un deshabilitado suave (status=0)

Campos del log de auditoría: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

Interfaces: Gestión de usuarios / logs de auditoría / roles → [endpoints Admin de api.es.md](api.es.md#admin-端点端口-8789)

---

## Módulo 13: Sincronización de datos

### Flujo de DataSyncTask (cada 10 minutos)

```
遍历 sync_enabled=1 的账户
  → 获取平台适配器
  → 同步 Campaigns (fetchCampaigns → updateOrInsert)
  → 同步 AdGroups (fetchAdGroups → 遍历每 campaign)
  → 同步 Creatives (fetchCreatives → 遍历每 ad_group)
  → 同步 Reports (fetchReports → 过去 2 天 daily, 9 个指标)
  → 清除 Dashboard 缓存
  → 更新 last_sync_at
```

---

## Formato de respuesta

### Éxito
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### Paginación
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### Error
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## Módulo 14: Biblioteca de materiales publicitarios

- Tipos soportados: image/jpeg, image/png, image/gif, image/webp, video/mp4
- Almacenamiento de archivos: `public/uploads/assets/`
- Frontend: galería en cuadrícula + carga por arrastrar y soltar + vista previa de imágenes + reproducción de video + copiar URL

Interfaces: Carga / lista / detalle / eliminación → [módulo 12 de api.es.md](api.es.md#模块-12-素材库)

---

## Módulo 15: Alerta de presupuesto

- Alertas en tres niveles: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask se ejecuta cada 15 minutos
- Deduplicación: una sola notificación por día para la misma campaña y el mismo nivel
- Escritura en la tabla `erik_notifications`

Interfaces: Alerta de presupuesto → [módulo 7 de api.es.md](api.es.md#模块-7-报表)

---

## Módulo 16: Calendario de campañas

- Agrega la programación de campañas por fecha
- Gráfico Gantt en el frontend: eje x fechas, eje y campañas, diferenciado por color según plataforma
- Soporta cambio entre vista mensual/semanal

Interfaces: Calendario de campañas → [módulo 7 de api.es.md](api.es.md#模块-7-报表)

---

## Módulo 17: Atribución multiplataforma

### Modelos de atribución

| Modelo | Algoritmo |
|------|------|
| first_touch | Primer punto de contacto 100% |
| last_touch | Último punto de contacto 100% |
| linear | Todos los puntos de contacto a partes iguales (1/N) |
| time_decay | e^(-λ×Δt), semivida de 7 días |
| position_based | Primero 40% + último 40% + medio 20% |

- Ventana de retroceso: 30 días
- Fuente de puntos de contacto: `erik_report_metrics` (clics > 0)
- Los resultados se escriben en `erik_attribution_results`
- Frontend: AttributionReport.vue cambio de modelo + tarjetas de estadísticas + gráfico de barras ECharts + tabla de detalle

### Tablas de datos

| Tabla | Campos |
|----|------|
| `erik_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `erik_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

Interfaces: Análisis de atribución / lista de modelos → [módulo 7 de api.es.md](api.es.md#模块-7-报表)

### Verificación de salud
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```
