# Divisão de versões

[中文](docs/skills/version-split.md) | [English](docs/skills/version-split.en.md) | [한국어](docs/skills/version-split.ko.md) | [Русский](docs/skills/version-split.ru.md) | [Deutsch](docs/skills/version-split.de.md) | [Français](docs/skills/version-split.fr.md) | [Español](docs/skills/version-split.es.md) | [Português](docs/skills/version-split.pt.md) | [हिन्दी](docs/skills/version-split.hi.md) | [العربية](docs/skills/version-split.ar.md) | [বাংলা](docs/skills/version-split.bn.md) | [Bahasa Indonesia](docs/skills/version-split.id.md) | [日本語](docs/skills/version-split.ja.md)

Gerenciar as diferenças de funcionalidades entre as três versões Lite/Standard/Full.

## Arquitetura de três camadas

```
Lite (开源 MIT)          Standard (商业)           Full (商业)
├── 7 控制器             ├── 11 控制器             ├── 17 控制器
├── 7 中间件             ├── 11 中间件             ├── 15 中间件
├── 3 cron               ├── 4 cron                ├── 6 cron
├── 8 张表               ├── 13 张表               ├── 18 张表
├── 7 Vue 页面            ├── 13 Vue 页面           ├── 17 Vue 页面
└── 26 API 端点           └── 44 API 端点           └── 62 API 端点
```

## Etapas da divisão

### 1. Criar o branch de funcionalidade

```bash
git checkout -b feature/lite   # 简化版
git checkout -b feature/standard  # 标准版
```

### 2. Remover funcionalidades que não pertencem à versão alvo

**Da Full para a Standard (o que remover):**

- Controladores: BidRuleController, TargetingTemplateController, AssetController
- Serviços: BidEngine, BudgetAlertService, AttributionEngine, CalendarService
- Modelos: BidRule, BidLog, TargetingTemplate
- Middlewares: ReplayGuard, SessionLimit, OriginGuard, CsrfMiddleware
- Tarefas: BidCheckTask, BudgetCheckTask
- Vue: BidRuleList, AssetGallery, CampaignCalendar, AttributionReport
- Rotas: bid-rules, targeting-templates, assets, attribution, calendar, budget-alerts

**Da Standard para a Lite (o que remover):**

- Controladores: AdGroupController, CreativeController, AlertController, NotificationController
- Serviços: AlertEngine, NotificationService
- Modelos: AlertRule, AlertLog
- Middlewares: AttackGuard(×2), ClientPlatform(×2), LoginThrottle(×2), ResponseTime
- Tarefas: AlertCheckTask
- Vue: AdGroupList, CreativeList, AlertRuleList, AlertLogList, NotificationList, ReportView
- Remover a sincronização de adgroup/creative do DataSyncTask

### 3. Atualizar os arquivos de configuração

Atualizar após cada divisão:
- `route.php`: remover as rotas e imports correspondentes
- `middleware.php` (service+admin): simplificar a cadeia de middlewares
- `cron.php`: reduzir os jobs agendados
- `router/index.ts` + `SideNav.vue` (Vue): remover rotas e menus de páginas
- `router.dart` + `menu_config.dart` (Flutter): atualizar de forma sincronizada

### 4. Validação

```bash
php -l 检查语法
./vendor/bin/phpunit   # 35/35
vue-tsc --noEmit       # 零错误
dart analyze           # 零错误
grep -rn 已删除类名    # 零残留引用
```

