# Fase 9: Plan de Implementación — Integración Real de HarmonyOS

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**Objetivo:** Cambiar las 6 páginas del extremo HarmonyOS de datos simulados a llamadas reales a la API (service :8788), reparar el problema del baseUrl codificado en ApiClient, hacer real el inicio de sesión y convertir el extremo HarmonyOS en un tercer cliente utilizable.

**Fuente:** Auditoría del equipo de la Fase 7 (inventario de mobile-dev: las 6 páginas de HarmonyOS usan datos simulados, 0 llamadas reales, baseUrl de ApiClient codificado `http://127.0.0.1:8788/api`)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## Estado actual (verificado)

| Componente | Estado |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login completos; baseUrl codificado `http://127.0.0.1:8788/api` (Flutter usa la ruta relativa de mismo origen `/api`); login() sin llamadores |
| `pages/LoginPage.ets` | Inicio de sesión simulado (setTimeout 1s y redirige), comentario "replace with actual API call" |
| `pages/DashboardPage.ets` | Métricas codificadas en `@State` (totalCost=1250000 etc.) |
| `pages/CampaignListPage.ets` | L187 comentario placeholder `/campaigns` |
| `pages/AccountPage.ets` | L138 comentario placeholder `/accounts` |
| `pages/AlertPage.ets` | L146 comentario placeholder `/alerts` |
| `pages/ReportPage.ets` | L242 comentario placeholder `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric ya existen |
| i18n | StringResources.ets (15+ keys) |

## Tarea 1: Mejora de ApiClient

### Archivos:
- Modificar: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### Puntos de diseño
- **baseUrl configurable**: conservar setBaseUrl; el valor por defecto sigue siendo `http://127.0.0.1:8788/api` (en dispositivo real/simulador hay que apuntar a la dirección de la red local; comentario explicativo); evitar la ruta relativa de mismo origen estilo Flutter (ArkTS requiere URL absoluta)
- **Reparar el bug de replayHeaders duplicados**: `{ ...this.replayHeaders(), ...this.replayHeaders() }` expande dos veces (en el método get) → una sola vez
- **Adaptar el valor de retorno de login()**: `POST /api/auth/login` de service devuelve `{access_token, token_type, expires_in, user}` (comparar con los campos reales de `service/plugin/ads-api/controller/v1/AuthController.php` — es access_token y no token; verificar y corregir la comprobación `data.token`)
- **Manejo de errores**: cuando resp.responseCode no es 2xx, lanzar error/devolver mensaje de error claro; protección ante fallo de JSON.parse
- Mantener la convención existente de que get/post/put/delete devuelven `data.data` (desempaquetado de ApiResponse)

## Tarea 2: Inicio de sesión real en LoginPage

### Archivos:
- Modificar: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### Puntos de diseño
- `handleLogin()` llama a `ApiClient.login(username, password)`; éxito → setToken + redirigir a Dashboard; fallo → toast con el mensaje de error
- El estado de carga isLoading ya existe; reutilizarlo
- El mensaje de error prioriza el message devuelto por service (envelope ApiResponse); si no hay, texto genérico

## Tarea 3: Realización de las cinco páginas de negocio

### Archivos:
- Modificar: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`, `CampaignListPage.ets`, `AccountPage.ets`, `AlertPage.ets`, `ReportPage.ets`

### Correspondencia de endpoints (confirmada en la auditoría de la Fase 7; coincide con la versión reparada de Flutter)
| Página | Llamada | Análisis |
|---|---|---|
| DashboardPage | `GET /reports/summary` (rango de hoy) | `data.overview` → totalCost/total_impressions/avg_ctr etc. (importes en céntimos; formatFen ya existe) |
| CampaignListPage | `GET /campaigns` | `data.list` (paginado) → modelo Campaign |
| AccountPage | `GET /accounts` | `data.list` → modelo PlatformAccount |
| AlertPage | `GET /alerts/logs` | `data.list` → campos AlertLog (metric/rule_name/current_value/condition/threshold/status) |
| ReportPage | `GET /reports/custom` (date_start/date_end/dimensions[]/metrics[]) | `data.list` → ReportMetric |

### Puntos de diseño
- La carga de la página (aboutToAppear) dispara la solicitud; los datos `@State` se inicializan vacíos/0 para evitar que queden valores simulados
- El fallo de carga muestra error + reintentar (referencia el patrón de error/reintento de las páginas de Flutter)
- Unidad monetaria: service devuelve números en céntimos; formatFen ya lo maneja
- **No añadir archivos nuevos**; conservar la estructura de UI y el i18n existentes de cada página

## Tarea 4: Verificación

### Aceptación
- [ ] ApiClient sin replayHeaders duplicados; los campos de retorno de login coinciden con AuthController
- [ ] Las 6 páginas sin restos de datos simulados codificados (verificar con grep)
- [ ] Los caminos de llamada de las 5 páginas de negocio se corresponden uno a uno con las rutas de service (comparar con `service/plugin/ads-api/config/route.php`)
- [ ] Comprobación de sintaxis ArkTS (si este entorno tiene la cadena de herramientas hvigor/DevEco, ejecutarla; si no, explicarlo y verificar manualmente)
- [ ] Regresión: el PHPUnit de service no se ve afectado
