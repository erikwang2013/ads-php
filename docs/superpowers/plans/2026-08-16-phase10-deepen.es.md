# Fase 10: Plan de Implementación — Profundización y Comercialización

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**Objetivo:** Sobre la base de los contratos y los canales múltiples de las Fases 7-9, implantar cuatro capacidades de profundización: visualización del estado de sincronización, cierre del bucle de datos de conversión, empaquetado CI de clientes móviles y cuotas SaaS multitenant.

**Fuente:** Direcciones inferidas de la auditoría del equipo en la Fase 7 (researcher: implantación de ES/separación lectura-escritura/colas, CI de Flutter/HarmonyOS, integración real de 29 plataformas, cuotas de facturación SaaS, cierre del bucle de datos de conversión, visualización del estado de sincronización, oferta de precios con IA)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## Estado actual (verificado)

| Subelemento candidato | Estado actual |
|---|---|
| Visualización del estado de sincronización | La tabla `ads_sync_errors` + `RetrySyncTask` (3 reintentos, retroceso 5^n minutos) ya existen; **sin página/API de frontend que muestre la tasa de fallos y la latencia de sincronización** |
| Cierre del bucle de conversión | Las tablas `ads_conversions` + `ads_attribution_results` existen y el motor de atribución está implementado; **sin punto de entrada de recopilación de conversiones** (API de callback/rastreo) |
| CI de clientes móviles | `ci.yml` solo PHP syntax → PHPUnit → vue-tsc → Docker; **sin compilación/empaquetado de Flutter/HarmonyOS** |
| SaaS multitenant | La tabla `ads_tenants` + middleware TenantIdentify ya existen; **sin facturación/cuotas/estadísticas de uso** |
| Implantación de ES | scout.php configurado + dependencia webman-scout añadida; **docker-compose sin servicio ES** |
| Integración real de 29 plataformas | Código completo de 29 adaptadores; **sin registros de integración con sandbox/credenciales** (requiere credenciales externas; marcado como elemento manual) |

## Tarea 1: Visualización del estado de sincronización

### Archivos:
- Modificar: `service/plugin/ads-api/controller/v1/DashboardController.php` o añadir `service/plugin/ads-api/controller/v1/SyncController.php` + ruta
- Crear: `admin/public/web/src/api/sync.ts`
- Crear: `admin/public/web/src/views/sync/SyncStatus.vue` (o integrarlo en la página del sistema)

### Puntos de diseño
- Endpoints: `GET /api/sync/status` (por cuenta: last_sync_at, tasa de éxito, número de fallos de hoy, número de reintentos pendientes) + `GET /api/sync/errors` (lista de errores paginada, con last_error/retry_count/next_retry_at)
- Frontend: página de estado de sincronización (tabla + tarjetas de resumen), solo en las líneas de versión Full/Standard
- Fuente de datos: ads_platform_accounts (last_sync_at) + ads_sync_errors

## Tarea 2: API de recopilación de datos de conversión

### Archivos:
- Modificar: `service/plugin/ads-api/controller/v1/` (añadir ConversionController + ruta)
- Crear: `service/plugin/ads-report/service/ConversionService.php`

### Puntos de diseño
- Endpoints: `POST /api/conversions` (las partes de negocio devuelven conversiones: platform/campaign_id/order_id/conversion_time/value/currency/channel) + `GET /api/conversions` (consulta)
- Validación: campaign_id existe, importe no negativo, formato de hora; escritura en ads_conversions
- Vinculación de atribución: tras la devolución puede activarse el recálculo de atribución (o indicarse que lo recalcula el AttributionEngine existente de forma programada/manual)
- Frontend: la página de informes de atribución añade una explicación/demostración de "devolución de conversión" (opcional)

## Tarea 3: Empaquetado CI de clientes móviles

### Archivos:
- Modificar: `.github/workflows/ci.yml` (añadir job: build de Flutter (web + linux o apk) + comprobación estática de HarmonyOS)

### Puntos de diseño
- Flutter: `flutter pub get && flutter analyze && flutter build web` (o apk; elegir el objetivo compilable según el estado del repositorio; si el entorno de flutter es limitado, usar dart analyze)
- HarmonyOS: sin cadena de herramientas CI Linux estándar; hacer una comprobación estática documentada o saltarla (anotado)
- En paralelo con el job php-tests existente; no bloquea el flujo principal

## Tarea 4: Cuotas SaaS multitenant (MVP)

### Archivos:
- Modificar: `service/plugin/ads-tenant/` (añadir QuotaService)
- Modificar: `service/plugin/ads-api/config/route.php` + controller

### Puntos de diseño
- Datos: añadir campo quota a ads_tenants o nueva tabla ads_tenant_quotas (plan/account_limit/campaign_limit/sync_quota)
- Puntos de verificación: número de cuentas vinculadas, número de planes creados, número de sincronizaciones diarias (comprobar en las entradas de AccountController/CampaignController/DataSyncTask)
- Endpoints: `GET /api/tenant/quota` (uso + cuota)
- Frontend: la página del sistema muestra el uso de cuota (opcional; en MVP puede ser solo API)
- Líneas de versión: valores por defecto de cuota según lite/standard/full (constantes de config)

## Aceptación (por Tarea)
- [ ] Tarea 1: endpoints de API de sync disponibles, página de frontend visible, cobertura de tests
- [ ] Tarea 2: la API de devolución de conversions se puede escribir y consultar, la validación funciona, cobertura de tests
- [ ] Tarea 3: el nuevo job de CI pasa (o elementos saltados claramente anotados)
- [ ] Tarea 4: la API de quota devuelve datos correctos, la interceptación de exceso de límite funciona, cobertura de tests
- [ ] Todo: `php vendor/bin/phpunit --no-coverage` pasa completo, vue-tsc pasa

## Fuera del alcance de esta fase (requiere recursos externos)
- Integración real de 29 plataformas (requiere credenciales/sandbox de cada plataforma)
- Implantación de ES (requiere añadir servicio ES e inicialización de índices en docker-compose)
- Sugerencias de oferta con IA (preparación de modelos/datos)
