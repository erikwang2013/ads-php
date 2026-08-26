# Phase 7: 跨端契约修复 Implementation Plan

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **状态更新（2026-08-16）：** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ 全部完成，tester 回归验证通过（35 tests OK，契约交叉核对无幽灵端点，Phase 7 可验收）。

**Goal:** 修复团队审计发现的跨端 API 契约问题：Flutter 3 个幽灵端点（404）、Admin `admin.ts` 双前缀 bug、`/system/info` 无路由、ServiceProxy 未接线、文档口径过时。恢复三端（Admin/Flutter/HarmonyOS）对 service API 的一致消费。

**来源:** 2026-08-07 团队并行审计（backend-dev 路由盘点 61 端点、vue-dev Admin 调用盘点 50 调用点、mobile-dev 移动端盘点、researcher 已实现/已规划盘点交叉比对）

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1: 修复 Flutter 幽灵端点（🔴 最高优先）

### 背景
Flutter 3 个页面调用了 service 不存在的路由，全部 404：

| Flutter 调用 | service 实际路由 | 修复方案 |
|---|---|---|
| `GET /dashboard` | 无（仪表盘汇总在 `/reports/summary`） | 改为 `GET /reports/summary` |
| `GET /alerts` | 无（告警在 `/alerts/rules`、`/alerts/logs`、`/alerts/unread-count`） | 改为 `GET /alerts/logs`（告警列表语义） |
| `GET /reports` | 无（报表在 `/reports/summary`、`/reports/custom`） | 改为 `GET /reports/custom`（带日期/维度/指标参数，匹配 ReportBuilder::buildCustom） |

### Files:
- Modify: `apps/flutter/lib/features/dashboard/dashboard_page.dart`（`/dashboard` → `/reports/summary` ×2 区间，适配响应结构 `data.overview`/`by_platform`/`daily`）✅
- Modify: `apps/flutter/lib/features/alert/alert_page.dart`（`/alerts` → `/alerts/logs`，适配分页结构 `data.list`，AlertLog 字段 rule_name/metric/current_value/condition/threshold）✅
- Modify: `apps/flutter/lib/features/report/report_page.dart`（`/reports` → `/reports/custom`，参数 date_start/date_end/dimensions[]/metrics[]，解析 `data.list`，字段 cost）✅
- Verify: 响应字段与 `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` 实际返回一致 ✅

### 验收
- [x] 三处路径修改完成，查询参数保留（report 页的日期参数 → date_start/date_end + dimensions/metrics）✅
- [x] 响应解析与后端实际 JSON 结构对齐（overview / paginated list / custom list）✅
- [x] 修改后 `flutter analyze` 无错误 — 本环境 Flutter SDK 缓存只读无法运行，改用 SDK 内置 `dart analyze` 全项目 **0 errors**（15 个既有警告均为改动前存在，本次未引入新问题）✅

---

## Task 2: 修复 Admin `admin.ts` 双前缀 bug

### 背景
- `admin/public/web/src/api/admin.ts` 路径写 `/api/admin/...`，而 axios baseURL 已是 `/api`（`src/api/index.ts`），实际拼成 `/api/api/admin/...`，UserManage.vue / AuditLog.vue 的 5 个调用大概率 404。
- **深层架构问题（vue-dev 最终报告确认）**：admin 后端（8789）自身提供 12 条本地路由（`/api/admin/login`、`me`、`logout`、`users` CRUD、`roles`、`audit-logs`、`/api/install/*`），但：
  - `docker/nginx/admin.conf` 的 `location /api/` **全部** proxy_pass 到 `service_api`（php:8788）；
  - `upstream admin_backend`（admin-php:8789）虽已定义，但**没有任何 location 引用它** → 生产环境下 `/api/admin/*` 永远到不了 8789；
  - Vite dev 代理同样将 `/api` 全部指向 8788。
  - 结论：即使修好双前缀，`/api/admin/*` 仍 404——admin 后端本地路由在生产链路中未接线。

### 决策点（需 backend-dev + vue-dev + devops 确认）
- 方案 A（推荐）：vue-dev 将 `admin.ts` 路径改为相对 `/admin/users`、`/admin/audit-logs`，同时 **devops 在 Nginx 增加 `location /api/admin/` → `proxy_pass http://admin_backend`**（置于 `location /api/` 之前，精确前缀优先匹配），让 admin 专属路由由 8789 直服，业务路由仍走 8788
- 方案 B：backend-dev 在 service 增加 `/api/admin/*` 路由（与 Admin 端职责重叠，不推荐）
- 方案 C：业务查询也改走 ServiceProxy（需接线，改动最大，仅当需要 admin 端统一鉴权时才考虑）

### Files:
- Modify: `admin/public/web/src/api/admin.ts`（去除 `/api` 前缀）
- Modify: `docker/nginx/admin.conf`（新增 `location /api/admin/` → admin_backend upstream）
- Modify: `admin/public/web/vite.config.ts`（dev 代理增加 `/api/admin` → 8789 规则，置于 `/api` 之前）
- Verify: `admin/config/route.php` 中 admin 后端路由（/api/admin/users 等）与前端调用匹配

### 验收
- [x] 前端请求路径与实际存在的后端路由一致（无 404）— admin.ts 9 个方法全部核对 route.php ✅，vue-tsc 通过
- [x] Nginx / Vite 均能正确分流 `/api/admin/*` 到 8789，`/api/*` 其余到 8788 — Nginx 新增 `location /api/admin/`，Vite 新增 `/api/admin` 代理（置于 `/api` 前）✅
- [x] UserManage / AuditLog 页面功能可用 — 路径已对齐（含 listRoles → `/admin/users/roles` 决策）✅

---

## Task 3: `/system/info` 无路由 + ServiceProxy 决策

### 背景
- `SystemInfo.vue` / `stores/admin.ts` 调用 `GET /api/system/info`，service 无此路由（仅 /health、/ping），404 被 try/catch 吞掉
- `admin/app/controller/ServiceProxy.php` 已定义但全仓 0 个活跃调用方（"已定义未接线"）

### 决策点
- `/system/info`：方案 A — 前端改为调用 `/health`（service 已有）；方案 B — backend-dev 在 service 增加 `/api/system/info` 端点（返回版本/环境信息，对 HarmonyOS/Flutter 也有用，推荐）
- ServiceProxy：方案 A — 接线到 admin 需要的 admin 专属 API（如审计日志转发）；方案 B — 删除类并更新文档声明"Admin 直连 service"（当前实际架构）

### 已执行（2026-08-16）
- **`/system/info` → 方案 A（前端改调 `/health`）**：SystemInfo.vue 改原生 axios 调 `GET /health`，判定 `checks.database === 'ok'`；`/health` 路由在 service 侧不带 `/api` 前缀，Vite 已新增 `/health` 代理，Nginx 原有 `location /health` 已存在；`stores/admin.ts` 死代码同步改为 `/health` ✅
- **ServiceProxy → 方案 B（保留 + 文档说明）**：类保留为预留基础设施（`ServiceProxy::init()` 自初始化无害），`admin/config/app.php` 注释更新为"预留基础设施，当前无活跃调用方" ✅

### 验收
- [x] `/system/info` 决策落地：前端已移除调用（改 /health），无 404 幽灵请求 ✅
- [x] ServiceProxy 决策落地：保留类并在 config 注释说明现状 ✅

---

## Task 4: 文档回填与口径统一

### 背景
- README"14 控制器 / 45+ 端点"过时（实际 17 控制器 / 61 端点）
- `docs/superpowers/plans/` 各 phase checkbox 未回填（代码已实现但文档未勾选）
- HarmonyOS 状态"UI 规划中"过时（实际 6 页面 + ApiClient 已就绪）
- install.html / InstallController 默认 `.../api/v1` 与 config 默认 `/api`（X-API-Version 头）不一致
- CacheService 注释称两级缓存，实为三级（L1 内存 / APCu / Redis）

### Files:
- Modify: `README.md` / `README.en.md`（控制器数、端点数、HarmonyOS 状态、缓存层级）
- Modify: `admin/public/install.html` / `admin/app/controller/InstallController.php`（版本前缀口径统一）
- Modify: `service/support/CacheService.php`（注释更正）
- Optional: 回填 `docs/superpowers/plans/*.md` checkbox

### 已执行（2026-08-16）
- README.md / README.en.md：17 控制器 / 61 端点 / HarmonyOS 6 页面 / 19 Vue 页面 / SPA 直连口径全部更新 ✅
- install.html / InstallController：`/api/v1` 默认值 → `/api`（X-API-Version 头机制）✅
- 8 份 phase plan checkbox 全部回填 ✅（phase7 除外，待执行）

### 验收
- [x] README 数据与代码一致（17 控制器 / 61 端点 / HarmonyOS 6 页面）✅
- [x] 安装向导版本前缀与 X-API-Version 机制一致 ✅

---

## 后续阶段规划（Phase 8-10，本计划之外）

| Phase | 内容 | 状态 |
|---|---|---|
| Phase 8 | 告警多渠道落地：ads-alert 新增 channel/（Email SMTP、Webhook、SMS 网关占位）—— 补 Phase 5 遗留缺口 | 待启动 |
| Phase 9 | HarmonyOS 真实联调：6 页面接入 ApiClient（当前 0 真实调用，全模拟数据） | 待启动 |
| Phase 10 | 深化与商业化：29 平台真实联调、同步状态可视化、转化数据闭环、Flutter/HarmonyOS CI 打包、多租户 SaaS 配额 | 待启动 |
