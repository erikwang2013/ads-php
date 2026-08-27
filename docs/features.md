# 功能设计文档

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> 全部 API 接口定义（请求/响应/参数）见 [api.md](api.md)。

---

## 模块总览

| # | 模块 | 控制器/服务 | API 路由数 | Vue 页面 |
|---|------|--------|-----------|----------|
| 1 | 认证授权 | AuthController | 3 | LoginPage |
| 2 | 平台管理 | PlatformController | 3 | — |
| 3 | 账户管理 | AccountController | 5 | AccountList, AccountBind |
| 4 | 广告计划 | CampaignController | 6 | CampaignList |
| 5 | 广告组 | AdGroupController | 5 | AdGroupList |
| 6 | 广告创意 | CreativeController | 2 | CreativeList |
| 7 | 数据报表 | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | 告警监控 | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | 通知中心 | NotificationController | 4 | NotificationList |
| 10 | 自动出价 | BidRuleController | 5 | BidRuleList |
| 11 | 定向模板 | TargetingTemplateController | 5 | — |
| 12 | 系统管理 | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | 数据同步 | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | 素材库 | AssetController | 4 | AssetGallery |
| 15 | 预算预警 | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | 投放日历 | CalendarService | 1 | CampaignCalendar |
| 17 | 跨平台归因 | AttributionEngine | 2 | AttributionReport |
| 18 | 健康检查 | HealthController | 2 | — |
| 19 | 验证码 | CaptchaController | 2 | — |
| 20 | API 文档 | DocController | 1 | — |

**合计**: 20 模块, 65+ 路由, 18 Vue 页面

---

## 模块 1: 认证授权

- 验证码检查（可选）
- 查询 `admin_users` 表
- bcrypt `password_verify()` 验证
- JWT Token 生成 (24h TTL)
- 旧 Token 自动加入黑名单
- 从 Token 提取 `uid` 查询用户信息

接口: 登录 / Token 刷新 / 当前用户 → [api.md 模块 2](api.md#模块-2-认证)

---

## 模块 2-3: 平台与账户管理

- 平台列表缓存 1 小时 (Redis)，集成 Season 国旗 emoji
- OAuth 流程: 生成随机 state → 构建授权 URL → 回调处理 → 存储 Token
- 账户列表/详情缓存 5 分钟

接口: 平台列表 / OAuth / 账户 CRUD + 同步 → [api.md 模块 3](api.md#模块-3-平台--账户)

---

## 模块 4-6: 广告投放层级

### 数据结构

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- 创建计划通过平台适配器 + 写入本地
- 支持按平台/状态/关键词筛选，列表含今日汇总
- 广告组创建支持 `targeting_template_id` 加载定向模板

接口: 计划 / 广告组 / 创意 → [api.md 模块 4-6](api.md#模块-4-广告计划)

---

## 模块 7: 数据报表

- 仪表盘汇总缓存 5 分钟: 8 个 KPI 指标卡片 + 日趋势折线图 + 平台柱状图
- 自定义报表维度: date, platform, campaign
- 指标: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- 导出格式: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (HTML 打印)

接口: 汇总 / 自定义 / 导出 → [api.md 模块 7](api.md#模块-7-报表)

---

## 模块 8: 告警监控

### AlertEngine 求值流程

```
遍历 enabled=1 的规则
  → 查询 ads_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### 通知渠道

| 渠道 | 状态 | 实现 |
|------|------|------|
| web | ✅ | 写入 ads_notifications |
| email | 占位 | echo 存根 |
| sms | 占位 | echo 存根 |
| Redis pub/sub | ✅ | `alert:new` 频道 JSON 推送 |

接口: 规则 CRUD / 告警记录 / 确认 / 未读数 → [api.md 模块 8](api.md#模块-8-告警)

---

## 模块 9: 通知中心

- 前端 Pinia store 30s 轮询
- 侧边栏铃铛图标 + 未读数字徽标

接口: 列表 / 未读数 / 标记已读 / 全部已读 → [api.md 模块 9](api.md#模块-9-通知)

---

## 模块 10: 自动出价引擎

### BidEngine 求值流程

```
遍历 enabled=1 的规则
  → 查询 ads_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 冷却检查 (cooldown_minutes 内是否有过操作)
  → 执行动作:
    - adjust_budget: 新预算 = current + adjust_step, 限制 [budget_min, budget_max]
    - toggle_pause: 暂停计划
    - toggle_enable: 启用计划
  → 通过 AdapterRegistry → PlatformAdapter 调用平台 API
  → 更新本地 DB + 写入 BidLog
```

### 规则字段

| 字段 | 类型 | 说明 |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | 监控指标 |
| condition | gt/gte/lt/lte | 触发条件 |
| threshold | DECIMAL(12,2) | 阈值 |
| scope | tenant/platform/campaign | 作用范围 |
| action_type | adjust_budget/toggle_pause/toggle_enable | 动作 |
| adjust_step | INT (分) | 预算调整步长 (正=加, 负=减) |
| budget_min, budget_max | BIGINT | 预算边界 |
| cooldown_minutes | INT | 冷却期 |

接口: 规则 CRUD / 出价历史 → [api.md 模块 10](api.md#模块-10-自动出价)

---

## 模块 11: 受众定向模板

### 集成到广告组

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### 通用 JSON Schema

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

接口: 模板 CRUD → [api.md 模块 11](api.md#模块-11-定向模板)

---

## 模块 12: 系统管理 (Admin)

- 用户列表 ID hashids 编码
- 创建用户 bcrypt 哈希密码
- 禁用用户为软禁用 (status=0)

审计日志字段: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

接口: 用户管理 / 审计日志 / 角色 → [api.md Admin 端点](api.md#admin-端点端口-8789)

---

## 模块 13: 数据同步

### DataSyncTask 流程 (每 10 分钟)

```
遍历 sync_enabled=1 的账户
  → 获取平台适配器
  → 同步 Campaigns (fetchCampaigns → updateOrInsert)
  → 同步 AdGroups (fetchAdGroups → 遍历每 campaign)
  → 同步 Creatives (fetchCreatives → 遍历每 ad_group)
  → 同步 Reports (fetchReports → 过去 2 天 daily, 9 个指标)
  → 清除 Dashboard 缓存
  → 更新 last_sync_at
```

---

## 响应格式

### 成功
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### 分页
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### 错误
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## 模块 14: 广告素材库

- 支持类型: image/jpeg, image/png, image/gif, image/webp, video/mp4
- 文件存储: `public/uploads/assets/`
- 前端: 网格画廊 + 拖拽上传 + 图片预览 + 视频播放 + 复制 URL

接口: 上传 / 列表 / 详情 / 删除 → [api.md 模块 12](api.md#模块-12-素材库)

---

## 模块 15: 预算预警

- 三段告警: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask 每 15 分钟执行
- 去重: 同一计划同一级别一天只通知一次
- 写入 `ads_notifications` 表

接口: 预算预警 → [api.md 模块 7](api.md#模块-7-报表)

---

## 模块 16: 投放日历

- 按日期聚合 campaign 排期
- 前端 Gantt 图: x 轴日期, y 轴计划, 按平台颜色区分
- 支持月/周视图切换

接口: 投放日历 → [api.md 模块 7](api.md#模块-7-报表)

---

## 模块 17: 跨平台归因

### 归因模型

| 模型 | 算法 |
|------|------|
| first_touch | 首个触点 100% |
| last_touch | 末个触点 100% |
| linear | 所有触点均分 (1/N) |
| time_decay | e^(-λ×Δt), 7天半衰期 |
| position_based | 首40% + 末40% + 中间20% |

- 回溯窗口: 30 天
- 触点来源: `ads_report_metrics` (点击 > 0)
- 结果写入 `ads_attribution_results`
- 前端: AttributionReport.vue 模型切换 + 统计卡片 + ECharts 柱状图 + 明细表格

### 数据表

| 表 | 字段 |
|----|------|
| `ads_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `ads_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

接口: 归因分析 / 模型列表 → [api.md 模块 7](api.md#模块-7-报表)

### 健康检查
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```

---

## 模块 18: 平台调用弹性（熔断/降级）

### 熔断状态机

`CircuitBreaker` (service/plugin/ads-platform/src/CircuitBreaker.php) — per-platform 状态机:

| 状态 | 触发条件 | 行为 |
|------|----------|------|
| CLOSED | 正常 | 调用放行 |
| OPEN | 连续 5 次失败 | 快速失败 (fast-fail), 跳过该平台 |
| HALF_OPEN | 冷却 30s 后 | 放行一次探活请求 |
| CLOSED | 探活成功 | 恢复, 计数清零 |
| OPEN | 探活再失败 | 重新熔断 |

### GuardedAdapter 代理

- `AdapterRegistry::get()` 返回 GuardedAdapter 代理, 14 个调用点零改动
- OPEN 时抛 `CircuitBreakerOpenException` 快速失败, 任务层 catch 吸收 = 逐平台降级跳过
- Generator 方法: 迭代完整完成记 success / 中断记 failure

### 超时核查

- 29 个适配器均含 CURLOPT_TIMEOUT (30/60s) + CURLOPT_CONNECTTIMEOUT (10s)

### 测试覆盖

- CircuitBreakerTest 8 例 + GuardedAdapterTest 13 例

### 已知局限

- 单节点静态内存实现, 多节点部署需切换 Redis 共享状态
