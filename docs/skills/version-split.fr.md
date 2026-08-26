# Découpage des versions

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Gérer les différences de fonctionnalités entre les trois versions Lite/Standard/Full.

## Architecture en trois couches

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## Étapes de découpage

### 1. Créer les branches de fonctionnalités

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. Supprimer les fonctionnalités hors version cible

**De Full vers Standard (à supprimer)** :

- Contrôleurs : BidRuleController, TargetingTemplateController, AssetController
- Services : BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- Modèles : BidRule, BidLog, TargetingTemplate
- Middlewares : ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- Tâches : BidCheckTask, BudgetCheckTask
- Vue : BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- Routes : bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**De Standard vers Lite (à supprimer)** :

- Contrôleurs : AdGroupController, CreativeController, AlertController, NotificationController
- Services : AlertEngine, NotificationService
- Modèles : AlertRule, AlertLog
- Middlewares : AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- Tâches : AlertCheckTask
- Vue : AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- Supprimer la synchronisation adgroup/creative de DataSyncTask

### 3. Mettre à jour les fichiers de configuration

Après chaque découpage, mettre à jour :
- `route.php` : supprimer les routes et imports correspondants
- `middleware.php` (service+admin) : simplifier la chaîne de middlewares
- `cron.php` : alléger les tâches planifiées
- `router/index.ts` + `SideNav.vue` (Vue) : retirer les routes et menus de pages
- `router.dart` + `menu_config.dart` (Flutter) : mise à jour synchronisée

### 4. Vérification

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
