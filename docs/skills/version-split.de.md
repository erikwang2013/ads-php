# Versionsaufteilung

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Die Funktionsunterschiede der drei Versionen Lite/Standard/Full verwalten.

## Drei-Schichten-Architektur

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## Aufteilungsschritte

### 1. Funktionsbranch erstellen

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. Funktionen der Nicht-Zielversion entfernen

**Von Full zu Standard aufteilen (zu Entfernendes)**:

- Controller: BidRuleController, TargetingTemplateController, AssetController
- Services: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- Modelle: BidRule, BidLog, TargetingTemplate
- Middleware: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- Aufgaben: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- Routen: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**Von Standard zu Lite aufteilen (zu Entfernendes)**:

- Controller: AdGroupController, CreativeController, AlertController, NotificationController
- Services: AlertEngine, NotificationService
- Modelle: AlertRule, AlertLog
- Middleware: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- Aufgaben: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- In DataSyncTask die adgroup/creative-Synchronisierung entfernen

### 3. Konfigurationsdateien aktualisieren

Nach jeder Aufteilung aktualisieren:
- `route.php`: Entsprechende Routen und Imports entfernen
- `middleware.php` (service+admin): Middleware-Kette vereinfachen
- `cron.php`: Geplante Aufgaben reduzieren
- `router/index.ts` + `SideNav.vue` (Vue): Seitenrouten und Menüs entfernen
- `router.dart` + `menu_config.dart` (Flutter): Synchron aktualisieren

### 4. Verifizierung

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
