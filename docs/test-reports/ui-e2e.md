# 管理后台 SPA UI E2E 测试报告

- 测试时间: 2026/8/27 08:21:55
- 覆盖页面: 18（登录流程 + 17 个业务页）
- 结果: 通过 18 / 失败 0

## 1. 环境说明

| 项目 | 状态 | 说明 |
|------|------|------|
| 浏览器 | 可用 | google-chrome (system, headless, playwright channel:chrome) |
| Playwright | 可用 | 1.62.1 (global @ /usr/local/node/lib/node_modules) |
| Node / npm | 可用 | node v22.17.0 / npm 11.14.1 |
| admin 服务 (8789) | 不可用 | 端口被 /home/wwwroot/social/admin（另一项目旧进程）占用，ads-php admin 无法启动；**未停止他人进程、未改业务源码** |
| service (8788) | 可用 | 返回 200（本测试未依赖） |
| SPA 静态资源 | 可用 | /home/wwwroot/ads-php/admin/public/web/dist 存在 index.html + assets bundle |
| 测试方式 | 兜底 | dist 静态托管(127.0.0.1:8899) + playwright API mock 拦截 /api/* |

## 2. 覆盖页面清单

| # | 页面 | 路由 | 结果 | 说明 |
|---|------|------|------|------|
| 1 | login | /login | 通过 | 校验断言+滑块登录成功并跳转 /dashboard |
| 2 | dashboard | /dashboard | 通过 | 广告管理系统 | 核心元素 canvas | 侧边栏导航切换+退出登录 OK |
| 3 | accounts | /accounts | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 4 | accounts_bind | /accounts/bind | 通过 | 广告管理系统 | 核心元素 button |  |
| 5 | campaigns | /campaigns | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 6 | adgroups | /adgroups | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 7 | creatives | /creatives | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 8 | alerts | /alerts | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 9 | alerts_logs | /alerts/logs | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 10 | reports_export | /reports/export | 通过 | 广告管理系统 | 核心元素 .el-button--primary | 点击导出 OK |
| 11 | reports_calendar | /reports/calendar | 通过 | 广告管理系统 | 核心元素 .el-select |  |
| 12 | reports_attribution | /reports/attribution | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 13 | reports_view | /reports/view | 通过 | 广告管理系统 | 核心元素 canvas |  |
| 14 | assets | /assets | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 15 | notifications | /notifications | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 16 | sync | /sync | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 17 | system_users | /system/users | 通过 | 广告管理系统 | 核心元素 .el-table |  |
| 18 | system_audit | /system/audit | 通过 | 广告管理系统 | 核心元素 .el-table |  |

## 3. 截图

全部页面截图已保存: `docs/test-reports/ui-e2e/screens/`（18 张，login.png 与 login_after.png 含登录后跳转）

## 4. 失败 / JS 错误摘要

无。全部页面无未捕获 JS 错误。

## 5. 环境限制说明

- **admin 服务未实测**：8789 被 social 项目旧进程占用（该进程不托管任何 SPA，返回 400），ads-php admin 无法在同端口启动；为不干扰他人服务，本次未终止该进程。
- **API 为 mock 数据**：所有 /api/* 请求由 playwright 拦截返回模拟响应，未验证真实后端数据流（真实联调需先在空闲端口启动 admin 或释放 8789）。
- **无截图视觉断言**：仅验证页面渲染（#app 挂载）、无 JS 错误、登录跳转；未做像素级/视觉比对。
- **深色/多语言未覆盖**：仅默认中文浅色模式。
- **复跑**：`NODE_PATH=$(npm root -g) node scripts/ui-e2e.mjs`（需 playwright 全局包 + system chrome）
