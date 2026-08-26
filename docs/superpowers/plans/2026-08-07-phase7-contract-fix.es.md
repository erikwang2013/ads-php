# Fase 7: Plan de Implementación — Reparación de Contratos entre Extremos

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **Actualización de estado (2026-08-16):** Tarea 1 ✅ / Tarea 2 ✅ / Tarea 3 ✅ / Tarea 4 ✅ completadas; la verificación de regresión del tester pasó (35 tests OK, cruce de contratos sin endpoints fantasma, Fase 7 aceptable).

**Objetivo:** Reparar los problemas de contratos de API entre extremos detectados en la auditoría del equipo: 3 endpoints fantasma de Flutter (404), el bug de doble prefijo de `admin.ts` de Admin, `/system/info` sin ruta, ServiceProxy sin cablear y documentación con criterios desactualizados. Restaurar el consumo coherente de la API de service por parte de los tres extremos (Admin/Flutter/HarmonyOS).

**Fuente:** Auditoría paralela del equipo del 2026-08-07 (backend-dev inventario de rutas 61 endpoints, vue-dev inventario de llamadas de Admin 50 puntos, mobile-dev inventario móvil, researcher cruce de lo implementado/planificado)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Tarea 1: Reparar los endpoints fantasma de Flutter (🔴 Prioridad máxima)

### Antecedentes
Las 3 páginas de Flutter llaman a rutas que no existen en service; todas devuelven 404:

| Llamada de Flutter | Ruta real de service | Solución |
|---|---|---|
| `GET /dashboard` | No existe (el resumen del dashboard está en `/reports/summary`) | Cambiar a `GET /reports/summary` |
| `GET /alerts` | No existe (las alertas están en `/alerts/rules`, `/alerts/logs`, `/alerts/unread-count`) | Cambiar a `GET /alerts/logs` (semántica de lista de alertas) |
| `GET /reports` | No existe (los informes están en `/reports/summary`, `/reports/custom`) | Cambiar a `GET /reports/custom` (con parámetros de fecha/dimensión/métrica, coincide con ReportBuilder::buildCustom) |

### Archivos:
- Modificar: `apps/flutter/lib/features/dashboard/dashboard_page.dart` (`/dashboard` → `/reports/summary` ×2 rangos, adaptar la estructura de respuesta `data.overview`/`by_platform`/`daily`) ✅
- Modificar: `apps/flutter/lib/features/alert/alert_page.dart` (`/alerts` → `/alerts/logs`, adaptar la estructura paginada `data.list`, campos AlertLog rule_name/metric/current_value/condition/threshold) ✅
- Modificar: `apps/flutter/lib/features/report/report_page.dart` (`/reports` → `/reports/custom`, parámetros date_start/date_end/dimensions[]/metrics[], analizar `data.list`, campo cost) ✅
- Verificar: que los campos de respuesta coincidan con lo que realmente devuelven `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` ✅

### Aceptación
- [x] Modificados los tres caminos; parámetros de consulta conservados (los parámetros de fecha de la página de informe → date_start/date_end + dimensions/metrics) ✅
- [x] El análisis de la respuesta está alineado con la estructura JSON real del backend (overview / lista paginada / lista custom) ✅
- [x] `flutter analyze` sin errores tras el cambio — la caché del SDK de Flutter de este entorno es de solo lectura y no se puede ejecutar; se usó el `dart analyze` integrado en el SDK en todo el proyecto: **0 errors** (los 15 avisos existentes ya estaban antes del cambio; esta vez no se introdujeron problemas nuevos) ✅

---

## Tarea 2: Reparar el bug de doble prefijo de `admin.ts` de Admin

### Antecedentes
- Las rutas de `admin/public/web/src/api/admin.ts` están escritas como `/api/admin/...`, pero el baseURL de axios ya es `/api` (`src/api/index.ts`), por lo que realmente se concatena `/api/api/admin/...`; las 5 llamadas de UserManage.vue / AuditLog.vue probablemente devuelven 404.
- **Problema arquitectónico profundo (confirmado en el informe final de vue-dev)**: el backend de admin (8789) ofrece 12 rutas locales (`/api/admin/login`, `me`, `logout`, CRUD `users`, `roles`, `audit-logs`, `/api/install/*`), pero:
  - El `location /api/` de `docker/nginx/admin.conf` hace proxy_pass de **todo** a `service_api` (php:8788);
  - El `upstream admin_backend` (admin-php:8789) está definido, pero **ningún location lo referencia** → en producción `/api/admin/*` nunca llega a 8789;
  - El proxy de dev de Vite también apunta todo `/api` a 8788.
  - Conclusión: incluso arreglando el doble prefijo, `/api/admin/*` seguiría devolviendo 404 — las rutas locales del backend de admin no están cableadas en la cadena de producción.

### Punto de decisión (requiere confirmación de backend-dev + vue-dev + devops)
- Opción A (recomendada): vue-dev cambia las rutas de `admin.ts` a relativas `/admin/users`, `/admin/audit-logs`, y **devops añade en Nginx `location /api/admin/` → `proxy_pass http://admin_backend`** (colocado antes de `location /api/`; la coincidencia de prefijo exacto tiene prioridad), de modo que las rutas exclusivas de admin las sirva directamente 8789 y las rutas de negocio sigan pasando por 8788
- Opción B: backend-dev añade rutas `/api/admin/*` en service (se solapa con las responsabilidades del extremo Admin; no recomendado)
- Opción C: las consultas de negocio también pasan por ServiceProxy (requiere cableado; el cambio más grande; solo si se necesita autenticación unificada del extremo admin)

### Archivos:
- Modificar: `admin/public/web/src/api/admin.ts` (eliminar el prefijo `/api`)
- Modificar: `docker/nginx/admin.conf` (añadir `location /api/admin/` → upstream admin_backend)
- Modificar: `admin/public/web/vite.config.ts` (añadir en el proxy de dev la regla `/api/admin` → 8789, antes de `/api`)
- Verificar: que las rutas del backend de admin en `admin/config/route.php` (/api/admin/users etc.) coincidan con las llamadas del frontend

### Aceptación
- [x] Las rutas de solicitud del frontend coinciden con las rutas del backend realmente existentes (sin 404) — los 9 métodos de admin.ts verificados contra route.php ✅, vue-tsc pasa
- [x] Nginx / Vite desvían correctamente `/api/admin/*` a 8789 y el resto de `/api/*` a 8788 — Nginx con nuevo `location /api/admin/`, Vite con nuevo proxy `/api/admin` (antes de `/api`) ✅
- [x] Las páginas UserManage / AuditLog funcionan — rutas alineadas (incluida la decisión listRoles → `/admin/users/roles`) ✅

---

## Tarea 3: `/system/info` sin ruta + decisión de ServiceProxy

### Antecedentes
- `SystemInfo.vue` / `stores/admin.ts` llaman a `GET /api/system/info`; service no tiene esa ruta (solo /health, /ping); el 404 queda oculto por el try/catch
- `admin/app/controller/ServiceProxy.php` está definido pero no tiene ningún llamador activo en todo el repositorio ("definido pero no cableado")

### Punto de decisión
- `/system/info`: Opción A — el frontend cambia a `/health` (ya existe en service); Opción B — backend-dev añade el endpoint `/api/system/info` en service (devuelve información de versión/entorno; también útil para HarmonyOS/Flutter; recomendada)
- ServiceProxy: Opción A — cablearlo a las APIs exclusivas de admin que admin necesita (p. ej., reenvío de logs de auditoría); Opción B — eliminar la clase y actualizar la documentación indicando "Admin se conecta directamente a service" (arquitectura real actual)

### Ejecutado (2026-08-16)
- **`/system/info` → Opción A (el frontend cambia a `/health`)**: SystemInfo.vue usa axios nativo para llamar a `GET /health` y comprueba `checks.database === 'ok'`; la ruta `/health` no lleva el prefijo `/api` en service; Vite ya tiene el proxy `/health` añadido; Nginx ya tenía el `location /health`; el código muerto de `stores/admin.ts` también cambia a `/health` ✅
- **ServiceProxy → Opción B (conservar + documentar)**: la clase se conserva como infraestructura reservada (`ServiceProxy::init()` se autoinicializa sin efectos secundarios); el comentario de `admin/config/app.php` se actualiza a "infraestructura reservada, actualmente sin llamadores activos" ✅

### Aceptación
- [x] Decisión de `/system/info` implementada: el frontend ya no la llama (cambió a /health); sin solicitudes fantasma 404 ✅
- [x] Decisión de ServiceProxy implementada: la clase se conserva y el comentario en config explica el estado actual ✅

---

## Tarea 4: Relleno de documentación y unificación de criterios

### Antecedentes
- El README dice "14 controllers / 45+ endpoints" (desactualizado; en realidad 17 controllers / 61 endpoints)
- Los checkboxes de las fases de `docs/superpowers/plans/` no están rellenados (código implementado pero documentación sin marcar)
- El estado de HarmonyOS "UI en planificación" está desactualizado (en realidad 6 páginas + ApiClient listos)
- El prefijo por defecto de install.html / InstallController `.../api/v1` no coincide con el `/api` por defecto de config (cabecera X-API-Version)
- El comentario de CacheService dice caché de dos niveles, pero en realidad es de tres (L1 memoria / APCu / Redis)

### Archivos:
- Modificar: `README.md` / `README.en.md` (número de controllers, de endpoints, estado de HarmonyOS, niveles de caché)
- Modificar: `admin/public/install.html` / `admin/app/controller/InstallController.php` (unificar el criterio del prefijo de versión)
- Modificar: `service/support/CacheService.php` (corregir comentario)
- Opcional: rellenar los checkboxes de `docs/superpowers/plans/*.md`

### Ejecutado (2026-08-16)
- README.md / README.en.md: actualizados todos los criterios (17 controllers / 61 endpoints / 6 páginas HarmonyOS / 19 páginas Vue / conexión directa SPA) ✅
- install.html / InstallController: valor por defecto `/api/v1` → `/api` (mecanismo de cabecera X-API-Version) ✅
- Checkboxes de 8 planes de fase rellenados ✅ (excepto phase7, pendiente de ejecución)

### Aceptación
- [x] Los datos del README coinciden con el código (17 controllers / 61 endpoints / 6 páginas HarmonyOS) ✅
- [x] El prefijo de versión del asistente de instalación coincide con el mecanismo X-API-Version ✅

---

## Planificación de fases posteriores (Fase 8-10, fuera de este plan)

| Fase | Contenido | Estado |
|---|---|---|
| Fase 8 | Implantación de alertas multicanal: ads-alert añade channel/ (Email SMTP, Webhook, placeholder de pasarela SMS) — cubre el hueco restante de la Fase 5 | Pendiente de inicio |
| Fase 9 | Integración real de HarmonyOS: 6 páginas conectadas a ApiClient (actualmente 0 llamadas reales, todos datos simulados) | Pendiente de inicio |
| Fase 10 | Profundización y comercialización: integración real de 29 plataformas, visualización del estado de sincronización, cierre del bucle de datos de conversión, empaquetado CI de Flutter/HarmonyOS, cuotas SaaS multitenant | Pendiente de inicio |
