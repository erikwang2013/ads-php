# تقسيم الإصدارات (Version Split)

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

إدارة الاختلافات الوظيفية بين الإصدارات الثلاثة Lite/Standard/Full.

## البنية ثلاثية الطبقات

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## خطوات التقسيم

### 1. إنشاء فرع وظيفي

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. حذف وظائف الإصدارات غير المستهدفة

**التقسيم من Full إلى Standard (ما يجب حذفه)**:

- وحدات التحكم: BidRuleController, TargetingTemplateController, AssetController
- الخدمات: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- النماذج: BidRule, BidLog, TargetingTemplate
- الوسائط: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- المهام: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- المسارات: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**التقسيم من Standard إلى Lite (ما يجب حذفه)**:

- وحدات التحكم: AdGroupController, CreativeController, AlertController, NotificationController
- الخدمات: AlertEngine, NotificationService
- النماذج: AlertRule, AlertLog
- الوسائط: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- المهام: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- إزالة مزامنة adgroup/creative من DataSyncTask

### 3. تحديث ملفات التكوين

بعد كل تقسيم حدّث:
- `route.php`: حذف المسارات والاستيرادات المقابلة
- `middleware.php` (service+admin): تبسيط سلسلة الوسائط
- `cron.php`: تبسيط المهام المجدولة
- `router/index.ts` + `SideNav.vue` (Vue): إزالة مسارات الصفحات والقوائم
- `router.dart` + `menu_config.dart` (Flutter): تحديث متزامن

### 4. التحقق

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
