# Phase 9: HarmonyOS 真实联调 Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**Goal:** 将 HarmonyOS 端 6 个页面从模拟数据切换为真实 API 调用（service :8788），修复 ApiClient 的 baseUrl 硬编码问题，实现登录真实化，使鸿蒙端成为可用的第三客户端。

**来源:** Phase 7 团队审计（mobile-dev 盘点：HarmonyOS 6 页面全部模拟数据、0 处真实调用、ApiClient baseUrl 硬编码 `http://127.0.0.1:8788/api`）

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## 现状（已核实）

| 组件 | 状态 |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login 已完备；baseUrl 硬编码 `http://127.0.0.1:8788/api`（Flutter 用同源相对 `/api`）；login() 无调用方 |
| `pages/LoginPage.ets` | 模拟登录（setTimeout 1s 跳转），注释"replace with actual API call" |
| `pages/DashboardPage.ets` | `@State` 硬编码指标（totalCost=1250000 等） |
| `pages/CampaignListPage.ets` | L187 注释占位 `/campaigns` |
| `pages/AccountPage.ets` | L138 注释占位 `/accounts` |
| `pages/AlertPage.ets` | L146 注释占位 `/alerts` |
| `pages/ReportPage.ets` | L242 注释占位 `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric 已存在 |
| i18n | StringResources.ets（15+ keys） |

## Task 1: ApiClient 增强

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### 设计要点
- **baseUrl 改为可配置**：保留 setBaseUrl，默认值仍为 `http://127.0.0.1:8788/api`（真机/模拟器需指向局域网地址，注释说明）；避免 Flutter 式同源相对路径（ArkTS 必须绝对 URL）
- **修复重复 replayHeaders bug**：`{ ...this.replayHeaders(), ...this.replayHeaders() }` 重复展开（get 方法内）→ 单次
- **login() 返回值适配**：service `POST /api/auth/login` 返回 `{access_token, token_type, expires_in, user}`（对照 `service/plugin/ads-api/controller/v1/AuthController.php` 实际字段——是 access_token 而非 token，需核实后修正 `data.token` 判断）
- **错误处理**：resp.responseCode 非 2xx 时抛错/返回明确错误信息；JSON.parse 失败保护
- 保持 get/post/put/delete 返回 `data.data`（ApiResponse 解包）的既有约定

## Task 2: LoginPage 真实登录

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### 设计要点
- `handleLogin()` 调 `ApiClient.login(username, password)`；成功 → setToken + 跳转 Dashboard；失败 → toast 错误信息
- 加载态 isLoading 已存在，复用
- 错误消息优先用 service 返回的 message（ApiResponse envelope），无则通用文案

## Task 3: 五个业务页面真实化

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`、`CampaignListPage.ets`、`AccountPage.ets`、`AlertPage.ets`、`ReportPage.ets`

### 端点对照（Phase 7 审计已确认，与 Flutter 修复后一致）
| 页面 | 调用 | 解析 |
|---|---|---|
| DashboardPage | `GET /reports/summary`（今日区间） | `data.overview` → totalCost/total_impressions/avg_ctr 等（金额为分，formatFen 已有） |
| CampaignListPage | `GET /campaigns` | `data.list`（分页）→ Campaign model |
| AccountPage | `GET /accounts` | `data.list` → PlatformAccount model |
| AlertPage | `GET /alerts/logs` | `data.list` → AlertLog 字段（metric/rule_name/current_value/condition/threshold/status） |
| ReportPage | `GET /reports/custom`（date_start/date_end/dimensions[]/metrics[]） | `data.list` → ReportMetric |

### 设计要点
- 页面加载（aboutToAppear）触发请求；@State 数据初始化置空/0，避免模拟值残留
- 加载失败显示错误 + 重试（参考 Flutter 页面的错误/重试模式）
- 金额单位：service 返回分为单位的数字，formatFen 已处理
- **不新增文件**，保持各页面现有 UI 结构与 i18n

## Task 4: 验证

### 验收
- [ ] ApiClient 无重复 replayHeaders、login 返回字段与 AuthController 一致
- [ ] 6 个页面无硬编码模拟业务数据残留（grep 验证）
- [ ] 5 个业务页面调用路径与 service 路由一一对应（对照 `service/plugin/ads-api/config/route.php`）
- [ ] ArkTS 语法检查（如本环境有 hvigor/DevEco 工具链则运行；无则说明并人工核对）
- [ ] 回归：service PHPUnit 不受影响
