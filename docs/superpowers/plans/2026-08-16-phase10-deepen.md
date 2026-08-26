# Phase 10: 深化与商业化 Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**Goal:** 在 Phase 7-9 契约与多渠道基础上，落地同步状态可视化、转化数据闭环、移动端 CI 打包、多租户 SaaS 配额四项深化能力。

**来源:** Phase 7 团队审计推断方向（researcher：ES/读写分离/队列落地、Flutter/鸿蒙 CI、29 平台真实联调、SaaS 计费配额、转化数据闭环、同步状态可视化、AI 出价）

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## 现状（已核实）

| 候选子项 | 现状 |
|---|---|
| 同步状态可视化 | `erik_sync_errors` 表 + `RetrySyncTask`（重试 3 次、退避 5^n 分钟）已存在；**无前端页面/API 展示同步失败率与延迟** |
| 转化数据闭环 | `erik_conversions` + `erik_attribution_results` 表已存在，归因引擎已实现；**无转化数据采集入口**（回传/埋点 API） |
| 移动端 CI | `ci.yml` 仅 PHP 语法→PHPUnit→vue-tsc→Docker；**无 Flutter/HarmonyOS 构建打包** |
| 多租户 SaaS | `erik_tenants` 表 + TenantIdentify 中间件已存在；**无计费/配额/用量统计** |
| ES 落地 | scout.php 已配置 + webman-scout 依赖已引入；**docker-compose 无 ES 服务** |
| 29 平台真实联调 | 29 适配器代码齐全；**无沙箱/凭据联调记录**（需外部凭据，标记为人工项） |

## Task 1: 同步状态可视化

### Files:
- Modify: `service/plugin/ads-api/controller/v1/DashboardController.php` 或新增 `service/plugin/ads-api/controller/v1/SyncController.php` + route
- Create: `admin/public/web/src/api/sync.ts`
- Create: `admin/public/web/src/views/sync/SyncStatus.vue`（或并入系统页）

### 设计要点
- 端点：`GET /api/sync/status`（账户维度：last_sync_at、成功率、今日失败数、pending 重试数）+ `GET /api/sync/errors`（分页错误列表，含 last_error/retry_count/next_retry_at）
- 前端：同步状态页（表格 + 摘要卡片），仅 Full/Standard 版本线
- 数据源：erik_platform_accounts（last_sync_at）+ erik_sync_errors

## Task 2: 转化数据采集 API

### Files:
- Modify: `service/plugin/ads-api/controller/v1/`（新增 ConversionController + route）
- Create: `service/plugin/ads-report/service/ConversionService.php`

### 设计要点
- 端点：`POST /api/conversions`（业务方回传转化：platform/campaign_id/order_id/conversion_time/value/currency/channel）+ `GET /api/conversions`（查询）
- 校验：campaign_id 存在、金额非负、时间格式；写入 erik_conversions
- 归因联动：回传后可触发归因重算（或说明由现有 AttributionEngine 定时/手动重算）
- 前端：归因报表页增加"转化回传"说明/演示（可选）

## Task 3: 移动端 CI 打包

### Files:
- Modify: `.github/workflows/ci.yml`（新增 job：Flutter build（web + linux 或 apk）+ HarmonyOS 静态检查）

### 设计要点
- Flutter：`flutter pub get && flutter analyze && flutter build web`（或 apk，按仓库现状选择可构建目标；如 flutter 环境受限则用 dart analyze）
- HarmonyOS：无标准 Linux CI 工具链，做静态检查说明或跳过（标注）
- 与现有 php-tests job 并行，不阻塞主流程

## Task 4: 多租户 SaaS 配额（MVP）

### Files:
- Modify: `service/plugin/ads-tenant/`（新增 QuotaService）
- Modify: `service/plugin/ads-api/config/route.php` + controller

### 设计要点
- 数据：erik_tenants 增加 quota 字段或新表 erik_tenant_quotas（plan/account_limit/campaign_limit/sync_quota）
- 校验点：账户绑定数、计划创建数、每日同步次数（在 AccountController/CampaignController/DataSyncTask 入口检查）
- 端点：`GET /api/tenant/quota`（用量 + 配额）
- 前端：系统页展示配额用量（可选，MVP 可仅 API）
- 版本线：quota 默认值按 lite/standard/full 差异（config 常量）

## 验收（按 Task）
- [ ] Task 1：sync API 端点可用、前端页面展示、测试覆盖
- [ ] Task 2：conversions 回传 API 可写可查、校验生效、测试覆盖
- [ ] Task 3：CI 新增 job 通过（或明确标注跳过项）
- [ ] Task 4：quota API 返回正确、超限拦截生效、测试覆盖
- [ ] 全部：`php vendor/bin/phpunit --no-coverage` 全过、vue-tsc 通过

## 不在本期范围（需外部资源）
- 29 平台真实联调（需各平台凭据/沙箱）
- ES 服务落地（需 docker-compose 增加 ES 服务与索引初始化）
- AI 出价建议（模型/数据准备）
