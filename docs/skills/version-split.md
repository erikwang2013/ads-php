# 版本拆分

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

管理 Lite/Standard/Full 三版本功能差异。

## 三层架构

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## 拆分步骤

### 1. 创建功能分支

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. 删除非目标版本的功能

**从 Full 拆分到 Standard（需要删除的）**：

- 控制器: BidRuleController, TargetingTemplateController, AssetController
- 服务: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- 模型: BidRule, BidLog, TargetingTemplate
- 中间件: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- 任务: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- 路由: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**从 Standard 拆分到 Lite（需要删除的）**：

- 控制器: AdGroupController, CreativeController, AlertController, NotificationController
- 服务: AlertEngine, NotificationService
- 模型: AlertRule, AlertLog
- 中间件: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- 任务: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- DataSyncTask 中移除 adgroup/creative 同步

### 3. 更新配置文件

每次拆分后更新：
- `route.php`: 删除对应路由和 import
- `middleware.php` (service+admin): 简化中间件链
- `cron.php`: 精简定时任务
- `router/index.ts` + `SideNav.vue` (Vue): 移除页面路由和菜单
- `router.dart` + `menu_config.dart` (Flutter): 同步更新

### 4. 验证

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
