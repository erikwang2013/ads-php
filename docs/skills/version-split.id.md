# Pemisahan Versi

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Kelola perbedaan fitur antara tiga versi Lite/Standard/Full.

## Arsitektur Tiga Lapisan

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## Langkah Pemisahan

### 1. Buat Branch Fitur

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. Hapus Fitur yang Bukan Target Versi

**Dari Full ke Standard (yang perlu dihapus)**:

- Controller: BidRuleController, TargetingTemplateController, AssetController
- Layanan: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- Model: BidRule, BidLog, TargetingTemplate
- Middleware: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- Tugas: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- Route: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**Dari Standard ke Lite (yang perlu dihapus)**:

- Controller: AdGroupController, CreativeController, AlertController, NotificationController
- Layanan: AlertEngine, NotificationService
- Model: AlertRule, AlertLog
- Middleware: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- Tugas: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- Hapus sinkronisasi adgroup/creative dari DataSyncTask

### 3. Perbarui File Konfigurasi

Perbarui setelah setiap pemisahan:
- `route.php`: hapus route dan import terkait
- `middleware.php` (service+admin): sederhanakan rantai middleware
- `cron.php`: rampingkan tugas terjadwal
- `router/index.ts` + `SideNav.vue` (Vue): hapus rute halaman dan menu
- `router.dart` + `menu_config.dart` (Flutter): perbarui sinkron

### 4. Verifikasi

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
