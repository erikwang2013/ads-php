# バージョン分割

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Lite/Standard/Full の 3 バージョンの機能差異を管理します。

## 3 層アーキテクチャ

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## 分割手順

### 1. 機能ブランチを作成

```bash
git checkout -b feature/lite   # 簡易版
git checkout -b feature/standard  # 標準版
```

### 2. 対象外バージョンの機能を削除

**Full から Standard へ分割（削除が必要なもの）**：

- コントローラー: BidRuleController, TargetingTemplateController, AssetController
- サービス: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- モデル: BidRule, BidLog, TargetingTemplate
- ミドルウェア: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- タスク: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- ルート: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**Standard から Lite へ分割（削除が必要なもの）**：

- コントローラー: AdGroupController, CreativeController, AlertController, NotificationController
- サービス: AlertEngine, NotificationService
- モデル: AlertRule, AlertLog
- ミドルウェア: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- タスク: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- DataSyncTask から adgroup/creative 同期を除去

### 3. 設定ファイルを更新

分割のたびに以下を更新：
- `route.php`: 該当ルートと import を削除
- `middleware.php` (service+admin): ミドルウェアチェーンを簡素化
- `cron.php`: 定期タスクを整理
- `router/index.ts` + `SideNav.vue` (Vue): ページルートとメニューを除去
- `router.dart` + `menu_config.dart` (Flutter): 同期更新

### 4. 検証

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```
