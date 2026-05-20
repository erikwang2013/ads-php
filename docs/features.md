# 功能设计文档

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 模块总览

| # | 模块 | 控制器 | API 路由数 | Vue 页面 |
|---|------|--------|-----------|----------|
| 1 | 认证授权 | AuthController | 3 | LoginPage |
| 2 | 平台管理 | PlatformController | 3 | — |
| 3 | 账户管理 | AccountController | 5 | AccountList, AccountBind |
| 4 | 广告计划 | CampaignController | 6 | CampaignList |
| 5 | 广告组 | AdGroupController | 5 | AdGroupList |
| 6 | 广告创意 | CreativeController | 2 | CreativeList |
| 7 | 数据报表 | DashboardController, ReportController, ExportController | 4 | DashboardPage, ReportView, ReportExport |
| 8 | 告警监控 | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | 通知中心 | NotificationController | 4 | NotificationList |
| 10 | 自动出价 | BidRuleController | 5 | BidRuleList |
| 11 | 定向模板 | TargetingTemplateController | 5 | — |
| 12 | 系统管理 | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | 健康检查 | HealthController | 2 | — |
| 14 | 验证码 | CaptchaController | 2 | — |
| 15 | API 文档 | DocController | 1 | — |

**合计**: 15 模块, 14 控制器, 59 路由, 15 Vue 页面

---

## 模块 1: 认证授权

### 1.1 登录

```
POST /api/auth/login
Body: { username, password, captcha_token?, captcha_offset?, tenant_id? }
```

- 验证码检查（可选）
- 查询 `admin_users` 表
- bcrypt `password_verify()` 验证
- JWT Token 生成 (24h TTL)
- 返回: `{ access_token, token_type, expires_in, user }`

### 1.2 Token 刷新

```
POST /api/auth/refresh
Header: Authorization: Bearer <old_token>
```

- 旧 Token 自动加入黑名单
- 返回新 Token

### 1.3 当前用户

```
GET /api/auth/me
Header: Authorization: Bearer <token>
```

- 从 Token 提取 `uid`，查询数据库获取用户信息

---

## 模块 2-3: 平台与账户管理

### 2.1 平台列表

```
GET /api/platforms
Response: [{ code, name, flag, capabilities, auth_type }]
```

- 缓存 1 小时 (Redis)
- 集成 Season 国旗 emoji

### 2.2 OAuth 流程

```
GET  /api/platforms/:code/oauth-url?redirect_uri=...
POST /api/platforms/:code/callback
Body: { state, code }
```

- 生成随机 state → 构建授权 URL → 回调处理 → 存储 Token

### 2.3 账户 CRUD

```
GET    /api/accounts             # 列表 (缓存 5min)
GET    /api/accounts/:id         # 详情 (缓存 5min)
DELETE /api/accounts/:id         # 解绑
POST   /api/accounts/:id/sync    # 手动同步
```

---

## 模块 4-6: 广告投放层级

### 数据结构

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

### 4.1 广告计划

```
GET   /api/campaigns                 # 列表 (筛选/排序/分页 + 今日汇总)
POST  /api/campaigns                 # 创建 (通过平台适配器 + 写入本地)
GET   /api/campaigns/:id             # 详情 (含今日指标)
PUT   /api/campaigns/:id             # 更新
POST  /api/campaigns/:id/toggle      # 启停
POST  /api/campaigns/batch/toggle    # 批量启停 (单次 API 调用)
```

### 4.2 广告组

```
GET   /api/ad-groups                 # 列表 (支持 campaign_id/status 筛选)
POST  /api/ad-groups                 # 创建 (支持 targeting_template_id)
GET   /api/ad-groups/:id             # 详情 (含今日指标)
PUT   /api/ad-groups/:id             # 更新
POST  /api/ad-groups/:id/toggle      # 启停
```

### 4.3 创意

```
GET   /api/creatives                 # 列表 (支持 ad_group_id/media_type 筛选)
GET   /api/creatives/:id             # 详情 (含今日指标)
```

---

## 模块 7: 数据报表

### 7.1 仪表盘

```
GET /api/reports/summary?date_start=...&date_end=...
Response: { overview: {...}, by_platform: [...], daily: [...] }
```

- 缓存 5 分钟
- 8 个 KPI 指标卡片 + 日趋势折线图 + 平台柱状图

### 7.2 自定义报表

```
GET /api/reports/custom
Query: dimensions[]=date&dimensions[]=platform&metrics[]=cost&metrics[]=clicks
       &date_start=...&date_end=...
```

- 支持维度: date, platform, campaign
- 支持指标: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi

### 7.3 报表导出

```
GET /api/reports/export?format=csv&...
GET /api/reports/export-dashboard?format=pdf&...
```

- CSV (UTF-8 BOM), Excel (HTML .xls), PDF (HTML 打印)

---

## 模块 8: 告警监控

### 8.1 规则管理

```
GET    /api/alerts/rules        # 列表 (缓存 2min, 筛选 platform/enabled/metric)
POST   /api/alerts/rules        # 创建
PUT    /api/alerts/rules/:id    # 更新
DELETE /api/alerts/rules/:id    # 删除
```

### 8.2 告警记录

```
GET  /api/alerts/logs                  # 列表 (筛选 status/rule_id/metric)
POST /api/alerts/logs/:id/acknowledge  # 确认
GET  /api/alerts/unread-count          # 未读数 (缓存 30s, 前端 30s 轮询)
```

### 8.3 AlertEngine 求值流程

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### 8.4 通知渠道

| 渠道 | 状态 | 实现 |
|------|------|------|
| web | ✅ | 写入 erik_notifications |
| email | 占位 | echo 存根 |
| sms | 占位 | echo 存根 |
| Redis pub/sub | ✅ | `alert:new` 频道 JSON 推送 |

---

## 模块 9: 通知中心

```
GET  /api/notifications                 # 列表 (筛选 type/is_read)
GET  /api/notifications/unread-count    # 未读数
POST /api/notifications/:id/read        # 标记已读
POST /api/notifications/read-all        # 全部已读
```

- 前端 Pinia store 30s 轮询
- 侧边栏铃铛图标 + 未读数字徽标

---

## 模块 10: 自动出价引擎

### 10.1 规则管理

```
GET    /api/bid-rules          # 列表
POST   /api/bid-rules          # 创建
PUT    /api/bid-rules/:id      # 更新
DELETE /api/bid-rules/:id      # 删除
GET    /api/bid-rules/logs    # 操作历史
```

### 10.2 BidEngine 求值流程

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 冷却检查 (cooldown_minutes 内是否有过操作)
  → 执行动作:
    - adjust_budget: 新预算 = current + adjust_step, 限制 [budget_min, budget_max]
    - toggle_pause: 暂停计划
    - toggle_enable: 启用计划
  → 通过 AdapterRegistry → PlatformAdapter 调用平台 API
  → 更新本地 DB + 写入 BidLog
```

### 10.3 规则字段

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

---

## 模块 11: 受众定向模板

### 11.1 模板 CRUD

```
GET    /api/targeting-templates          # 列表 (按 platform 筛选)
GET    /api/targeting-templates/:id      # 详情
POST   /api/targeting-templates          # 创建
PUT    /api/targeting-templates/:id      # 更新
DELETE /api/targeting-templates/:id      # 删除
```

### 11.2 集成到广告组

```
POST /api/ad-groups
Body: { targeting_template_id: 1, targeting: {...} }

→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### 11.3 通用 JSON Schema

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

---

## 模块 12: 系统管理 (Admin)

### 12.1 用户管理

```
GET    /api/admin/users         # 列表 (分页/搜索/角色筛选, ID hashids 编码)
POST   /api/admin/users         # 创建 (bcrypt 哈希密码)
PUT    /api/admin/users/:id     # 更新
DELETE /api/admin/users/:id     # 软禁用 (status=0)
GET    /api/admin/users/roles   # 角色列表
```

### 12.2 审计日志

```
GET /api/admin/audit-logs       # 列表 (筛选 user_id/action/date, ID hashids 编码)
```

记录字段: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

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

### 健康检查
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```
