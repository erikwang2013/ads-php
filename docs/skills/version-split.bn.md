# ভার্সন স্প্লিট

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Lite/Standard/Full তিন ভার্সনের ফিচার পার্থক্য ব্যবস্থাপনা।

## থ্রি-লেয়ার আর্কিটেকচার

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## স্প্লিট ধাপ

### 1. ফিচার ব্রাঞ্চ তৈরি

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. নন-টার্গেট ভার্সনের ফিচার ডিলিট

**Full থেকে Standard-এ স্প্লিট（যা ডিলিট করতে হবে）**：

- কন্ট্রোলার: BidRuleController, TargetingTemplateController, AssetController
- সার্ভিস: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- মডেল: BidRule, BidLog, TargetingTemplate
- মিডলওয়্যার: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- টাস্ক: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- রাউট: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**Standard থেকে Lite-এ স্প্লিট（যা ডিলিট করতে হবে）**：

- কন্ট্রোলার: AdGroupController, CreativeController, AlertController, NotificationController
- সার্ভিস: AlertEngine, NotificationService
- মডেল: AlertRule, AlertLog
- মিডলওয়্যার: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- টাস্ক: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- DataSyncTask-এ adgroup/creative সিঙ্ক সরান

### 3. কনফিগ ফাইল আপডেট

প্রতিটি স্প্লিটের পর আপডেট করুন：
- `route.php`: সংশ্লিষ্ট রাউট ও import ডিলিট
- `middleware.php` (service+admin): মিডলওয়্যার চেইন সরলীকরণ
- `cron.php`: ক্রন টাস্ক হ্রাস
- `router/index.ts` + `SideNav.vue` (Vue): পেজ রাউট ও মেনু সরান
- `router.dart` + `menu_config.dart` (Flutter): সিঙ্ক আপডেট

### 4. ভেরিফিকেশন

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
