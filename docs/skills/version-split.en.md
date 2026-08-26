# Version Split

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Manage feature differences across the Lite/Standard/Full versions.

## Three-Tier Architecture

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## Split Steps

### 1. Create Feature Branch

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. Remove Features Not in the Target Version

**Full → Standard (to remove)**:

- Controllers: BidRuleController, TargetingTemplateController, AssetController
- Services: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- Models: BidRule, BidLog, TargetingTemplate
- Middleware: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- Tasks: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- Routes: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**Standard → Lite (to remove)**:

- Controllers: AdGroupController, CreativeController, AlertController, NotificationController
- Services: AlertEngine, NotificationService
- Models: AlertRule, AlertLog
- Middleware: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- Tasks: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- Remove adgroup/creative sync from DataSyncTask

### 3. Update Config Files

Update after each split:
- `route.php`: delete corresponding routes and imports
- `middleware.php` (service+admin): simplify middleware chain
- `cron.php`: trim scheduled tasks
- `router/index.ts` + `SideNav.vue` (Vue): remove page routes and menu items
- `router.dart` + `menu_config.dart` (Flutter): sync updates

### 4. Verify

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
