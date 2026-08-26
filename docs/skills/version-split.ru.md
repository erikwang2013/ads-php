# Разделение версий

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Управление функциональными различиями версий Lite/Standard/Full.

## Трёхуровневая архитектура

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## Шаги разделения

### 1. Создание функциональной ветки

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. Удаление функций, не входящих в целевую версию

**Из Full в Standard (что удалить)**:

- Контроллеры: BidRuleController, TargetingTemplateController, AssetController
- Сервисы: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- Модели: BidRule, BidLog, TargetingTemplate
- Middleware: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- Задачи: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- Маршруты: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**Из Standard в Lite (что удалить)**:

- Контроллеры: AdGroupController, CreativeController, AlertController, NotificationController
- Сервисы: AlertEngine, NotificationService
- Модели: AlertRule, AlertLog
- Middleware: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- Задачи: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- Удаление синхронизации adgroup/creative из DataSyncTask

### 3. Обновление файлов конфигурации

После каждого разделения обновляйте:
- `route.php`: удалите соответствующие маршруты и импорты
- `middleware.php` (service+admin): упростите цепочку middleware
- `cron.php`: сократите плановые задачи
- `router/index.ts` + `SideNav.vue` (Vue): удалите маршруты и меню страниц
- `router.dart` + `menu_config.dart` (Flutter): синхронно обновите

### 4. Проверка

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
