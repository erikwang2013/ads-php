# वर्शन स्प्लिट

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Lite/Standard/Full तीन वर्शन की फ़ंक्शन भिन्नता प्रबंधित करें।

## त्रि-परत आर्किटेक्चर

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## स्प्लिट चरण

### 1. फ़ंक्शन ब्रांच बनाएँ

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. गैर-लक्ष्य वर्शन की सुविधाएँ हटाएँ

**Full से Standard में स्प्लिट (हटाने की आवश्यकता)**:

- कंट्रोलर: BidRuleController, TargetingTemplateController, AssetController
- सेवाएँ: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- मॉडल: BidRule, BidLog, TargetingTemplate
- मिडलवेयर: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- कार्य: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- रूट: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**Standard से Lite में स्प्लिट (हटाने की आवश्यकता)**:

- कंट्रोलर: AdGroupController, CreativeController, AlertController, NotificationController
- सेवाएँ: AlertEngine, NotificationService
- मॉडल: AlertRule, AlertLog
- मिडलवेयर: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- कार्य: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- DataSyncTask से adgroup/creative सिंक हटाएँ

### 3. कॉन्फ़िगरेशन फ़ाइलें अपडेट करें

हर स्प्लिट के बाद अपडेट करें:
- `route.php`: संबंधित रूट और import हटाएँ
- `middleware.php` (service+admin): मिडलवेयर चेन सरल करें
- `cron.php`: निर्धारित कार्य कम करें
- `router/index.ts` + `SideNav.vue` (Vue): पेज रूट और मेन्यू हटाएँ
- `router.dart` + `menu_config.dart` (Flutter): सिंक-अपडेट करें

### 4. सत्यापन

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
