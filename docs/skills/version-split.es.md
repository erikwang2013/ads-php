# 版本拆分

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Gestiona las diferencias funcionales entre las tres versiones Lite/Standard/Full.

## Arquitectura de tres niveles

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## Pasos de la división

### 1. Crear la rama de funcionalidad

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. Eliminar funciones de la versión no objetivo

**De Full a Standard (lo que hay que eliminar)**:

- Controladores: BidRuleController, TargetingTemplateController, AssetController
- Servicios: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- Modelos: BidRule, BidLog, TargetingTemplate
- Middlewares: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- Tareas: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- Rutas: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**De Standard a Lite (lo que hay que eliminar)**:

- Controladores: AdGroupController, CreativeController, AlertController, NotificationController
- Servicios: AlertEngine, NotificationService
- Modelos: AlertRule, AlertLog
- Middlewares: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- Tareas: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- En DataSyncTask eliminar la sincronización de adgroup/creative

### 3. Actualizar los archivos de configuración

Tras cada división, actualizar:
- `route.php`: eliminar las rutas e imports correspondientes
- `middleware.php` (service+admin): simplificar la cadena de middlewares
- `cron.php`: simplificar las tareas programadas
- `router/index.ts` + `SideNav.vue` (Vue): eliminar rutas y menús de páginas
- `router.dart` + `menu_config.dart` (Flutter): actualizar en sincronía

### 4. Verificación

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
