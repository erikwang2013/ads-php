# API 接口文档

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 通用规范

### Base URL

```
http://your-domain.com/api
```

### 必需 Headers

| Header | 值 | 说明 |
|--------|----|------|
| `X-API-Version` | `v1` | API 版本号（必填，不出现于 URL 路径） |
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | 操作来源端（必填） |
| `Authorization` | `Bearer <token>` | JWT 认证令牌（除登录/平台列表/健康检查外必填） |

### 防重放 Header（非浏览器端）

| Header | 说明 |
|--------|------|
| `X-Nonce` | 随机字符串（每次请求唯一） |
| `X-Timestamp` | Unix 秒级时间戳（±5 分钟窗口） |

### 可选 Headers

| Header | 说明 |
|--------|------|
| `X-Tenant-Id` | 租户 ID（多租户模式） |
| `X-Encrypted` | `1` = 请求体需解密，响应体需加密 |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| 值 | 说明 |
|----|------|
| `application/json` | JSON 请求体（推荐） |
| `application/x-www-form-urlencoded` | 表单请求 |
| `multipart/form-data` | 文件上传 |

### 响应格式

**成功**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**分页**:
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [ ... ],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

**错误**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**健康检查**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### HTTP 状态码

| 状态码 | 含义 |
|--------|------|
| 200 | 成功 |
| 204 | OPTIONS 预检成功 |
| 400 | 请求参数错误、不支持的 API 版本 |
| 401 | 未认证、Token 过期、Token IP/UA 不匹配 |
| 403 | 禁止访问（XSS/路径遍历/CSRF/SQL注入/Origin 不匹配） |
| 404 | 资源不存在 |
| 429 | 请求过多（限流/登录节流/并发会话限制） |
| 500 | 服务器错误 |
| 503 | 服务降级（DB 或 Redis 不可用） |

### 分页参数

| 参数 | 默认值 | 最大值 | 说明 |
|------|--------|--------|------|
| `page` | 1 | — | 页码 |
| `per_page` | 20 | 100 | 每页条数（超过自动截断） |
| `sort` | `id` | — | 排序字段（需在白名单内） |

### 缓存策略

| 端点 | TTL | 层 |
|------|-----|-----|
| `/api/platforms` | 1 小时 | L1 内存 → L2 APCu → L3 Redis |
| `/api/accounts` + `/api/accounts/:id` | 5 分钟 | 同上 |
| `/api/reports/summary` | 5 分钟 | 同上 |
| `/api/alerts/rules` | 2 分钟 | 同上 |
| `/api/alerts/unread-count` | 30 秒 | 同上 |

---

## 模块 1: 系统

### GET /health — 健康检查

```
GET /health
```

**响应**:
```json
{
  "status": "healthy",
  "timestamp": "2026-05-22T00:00:00+08:00",
  "checks": {
    "database": "ok",
    "redis": "ok"
  }
}
```

- `status`: `healthy` (200) 或 `degraded` (503)
- 无认证要求，不走版本路由

---

### GET /ping — 探活

```
GET /ping
```

**响应**: `{ "pong": true }`

---

### GET /docs — API 文档

```
GET /docs
```

返回 HTML 格式的 API 文档页面（免认证）。

---

### GET /api/captcha/generate — 生成验证码

免认证。

**响应**:
```json
{
  "code": 0,
  "data": {
    "captcha_token": "aes-encrypted-token",
    "background": "base64...",
    "puzzle": "base64..."
  }
}
```

- token 有效期 5 分钟
- 偏移容差 5px

---

### POST /api/captcha/verify — 验证验证码

免认证。

**请求**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**响应**: `{ "code": 0, "message": "验证通过" }`

---

## 模块 2: 认证

### POST /api/auth/login — 登录

免认证。

**请求**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**响应**:
```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "username": "admin",
      "name": "超级管理员",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

- JWT Token 有效期 24 小时
- Token 内嵌 IP + User-Agent hash
- 5 次失败 → Redis 锁定 15 分钟

---

### GET /api/auth/me — 当前用户

**请求头**: `Authorization: Bearer <token>`

**响应**:
```json
{
  "code": 0,
  "data": {
    "id": 1,
    "username": "admin",
    "name": "超级管理员",
    "email": "admin@example.com",
    "role": "admin",
    "tenant_id": 1
  }
}
```

---

### POST /api/auth/refresh — 刷新 Token

**请求头**: `Authorization: Bearer <old_token>`

**响应**:
```json
{
  "code": 0,
  "message": "Token 已刷新",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

- 旧 Token 自动加入黑名单
- 每个用户最大 3 个活跃 Token

---

## 模块 3: 平台 & 账户

### GET /api/platforms — 平台列表

免认证。缓存 1 小时。

**响应**:
```json
{
  "code": 0,
  "data": [
    { "code": "juliang", "name": "巨量引擎", "flag": "🇨🇳", "capabilities": ["campaign", "report"] },
    { "code": "meta", "name": "Meta Ads", "flag": "🇺🇸", "capabilities": ["campaign", "report"] }
  ]
}
```

---

### GET /api/platforms/:code/oauth-url — OAuth 授权 URL

**参数**: `?redirect_uri=https://your-domain.com/callback`

**响应**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` 必须通过 SSRF 白名单校验（`OAUTH_ALLOWED_REDIRECTS` 环境变量）

---

### POST /api/platforms/:code/callback — OAuth 回调

**请求**: `{ "state": "...", "code": "..." }`

**响应**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/accounts — 账户列表

缓存 5 分钟。

**参数**:

| 参数 | 说明 |
|------|------|
| `platform` | 平台代码筛选 |
| `page` | 页码 |
| `per_page` | 每页条数 |

**响应**: 分页格式，list 中每项包含 `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at`

---

### GET /api/accounts/:id — 账户详情

缓存 5 分钟。

---

### DELETE /api/accounts/:id — 解绑账户

---

### POST /api/accounts/:id/sync — 手动同步

---

## 模块 4: 广告计划

### GET /api/campaigns — 计划列表

**参数**:

| 参数 | 说明 | 可选值 |
|------|------|--------|
| `platform` | 平台筛选 | juliang, meta, google... |
| `status` | 状态筛选 | enabled, paused |
| `keyword` | 名称搜索 | 任意文本 |
| `sort` | 排序字段 | id, name, platform, daily_budget, status, created_at |
| `page` | 页码 | — |
| `per_page` | 每页条数 | ≤100 |

**响应**: 分页格式 + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/campaigns — 创建计划

**请求**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**响应**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- `daily_budget` 单位：分（20000 = ¥200.00）

---

### GET /api/campaigns/:id — 计划详情

**响应**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/campaigns/:id — 更新计划

**请求**: `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/campaigns/:id/toggle — 启停计划

**请求**: `{ "enabled": false }`

---

### POST /api/campaigns/batch/toggle — 批量启停

**请求**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**响应**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## 模块 5: 广告组

### GET /api/ad-groups — 广告组列表

**参数**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/ad-groups — 创建广告组

**请求**:
```json
{
  "campaign_id": 1,
  "name": "测试广告组",
  "bid_amount": 100,
  "bid_type": "cpc",
  "targeting": { "age": { "min": 18, "max": 45 } },
  "targeting_template_id": "hashids-encoded-template-id"
}
```

- `targeting_template_id`: 可选，从定向模板加载 targeting JSON 并合并

### GET /api/ad-groups/:id — 广告组详情

### PUT /api/ad-groups/:id — 更新广告组

### POST /api/ad-groups/:id/toggle — 启停广告组

---

## 模块 6: 创意

### GET /api/creatives — 创意列表

**参数**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/creatives/:id — 创意详情

---

## 模块 7: 报表

### GET /api/reports/summary — 仪表盘汇总

缓存 5 分钟。

**参数**: `date_start`, `date_end`

**响应**:
```json
{
  "code": 0,
  "data": {
    "overview": { "cost": 123456, "impressions": 10000, ... },
    "by_platform": [ ... ],
    "daily": [ ... ]
  }
}
```

---

### GET /api/reports/custom — 自定义报表

**参数**:

| 参数 | 说明 |
|------|------|
| `dimensions[]` | 维度: date, platform, campaign |
| `metrics[]` | 指标: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | 起始日期 |
| `date_end` | 截止日期 |
| `platform` | 平台筛选 |

---

### GET /api/reports/export — 导出报表

**参数**: `format=csv`, `date_start`, `date_end`, `metrics[]`

返回文件下载（CSV UTF-8 BOM 或 Excel .xls）。

---

### GET /api/reports/export-dashboard — 导出仪表盘 PDF

---

### GET /api/reports/calendar — 投放日历

**参数**: `date_start`, `date_end`, `platform`

**响应**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/reports/budget-alerts — 预算预警

**响应**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/reports/attribution — 归因分析

**参数**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**响应**:
```json
{
  "code": 0,
  "data": {
    "total_conversions": 42,
    "total_value": 123456.78,
    "by_campaign": [ { "campaign_id": 1, "credit": 5000.00 } ]
  }
}
```

---

### GET /api/reports/attribution/models — 归因模型列表

**响应**: `[{ code: "last_touch", name: "末次触点", description: "..." }]`

共 5 种模型。

---

## 模块 8: 告警

### GET /api/alerts/rules — 告警规则列表

缓存 2 分钟。

**参数**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/alerts/rules — 创建告警规则

**请求**:
```json
{
  "name": "花费超限",
  "metric": "cost",
  "condition": "gt",
  "threshold": 100000,
  "scope": "tenant",
  "platform": null,
  "campaign_id": null,
  "channels": ["web"]
}
```

### PUT /api/alerts/rules/:id — 更新告警规则

### DELETE /api/alerts/rules/:id — 删除告警规则

### GET /api/alerts/logs — 告警记录

**参数**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/alerts/logs/:id/acknowledge — 确认告警

### GET /api/alerts/unread-count — 未读告警数

缓存 30 秒。前端 30s 轮询。

---

## 模块 9: 通知

### GET /api/notifications — 通知列表

**参数**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/notifications/unread-count — 未读通知数

### POST /api/notifications/:id/read — 标记已读

### POST /api/notifications/read-all — 全部已读

---

## 模块 10: 自动出价

### GET /api/bid-rules — 规则列表

### POST /api/bid-rules — 创建规则

**请求**:
```json
{
  "name": "ROI 达标加预算",
  "metric": "roi",
  "condition": "gte",
  "threshold": 3.0,
  "action_type": "adjust_budget",
  "adjust_step": 5000,
  "budget_min": 0,
  "budget_max": 100000,
  "cooldown_minutes": 60
}
```

**字段说明**:

| 字段 | 类型 | 说明 |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | 监控指标 |
| condition | gt/gte/lt/lte | 触发条件 |
| threshold | decimal | 阈值 |
| action_type | adjust_budget/toggle_pause/toggle_enable | 动作类型 |
| adjust_step | int (分) | 预算调整步长（正=加, 负=减） |
| budget_min | int | 预算下限（分） |
| budget_max | int | 预算上限（分） |
| cooldown_minutes | int | 冷却时间（默认 60） |

### PUT /api/bid-rules/:id — 更新规则

### DELETE /api/bid-rules/:id — 删除规则

### GET /api/bid-rules/logs — 出价历史

**参数**: `rule_id`, `campaign_id`

---

## 模块 11: 定向模板

### GET /api/targeting-templates — 模板列表

**参数**: `platform`

### GET /api/targeting-templates/:id — 模板详情

### POST /api/targeting-templates — 创建模板

**请求**:
```json
{
  "name": "核心受众",
  "platform": "",
  "targeting": {
    "age": { "min": 18, "max": 45 },
    "gender": "all",
    "interests": ["sports", "tech"],
    "devices": { "os": ["android", "ios"] }
  },
  "is_shared": 0
}
```

### PUT /api/targeting-templates/:id — 更新模板

### DELETE /api/targeting-templates/:id — 删除模板

---

## 模块 12: 素材库

### GET /api/assets — 素材列表

**参数**: `type`(image/video), `page`, `per_page`

### POST /api/assets/upload — 上传素材

**请求**: `multipart/form-data`, 字段 `file`

- 图片: 最大 5 MB (jpeg/png/gif/webp)
- 视频: 最大 50 MB (mp4)

**响应**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

### GET /api/assets/:id — 素材详情

### DELETE /api/assets/:id — 删除素材

---

## Admin 端点（端口 8789）

### POST /api/admin/login — 管理员登录

**请求**: `{ "username": "admin", "password": "..." }`

**响应**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Token 存入 localStorage
- `csrf_token` 需在后续 POST/PUT/DELETE 请求的 `X-CSRF-Token` header 中携带

### GET /api/admin/me — 当前管理员

### POST /api/admin/logout — 退出

### GET /api/admin/users — 用户列表

**参数**: `keyword`, `role_id`, `page`, `per_page`

响应中 `id` 和 `role_id` 使用 hashids 编码。

### POST /api/admin/users — 创建用户

### PUT /api/admin/users/:id — 更新用户

### DELETE /api/admin/users/:id — 禁用用户

### GET /api/admin/users/roles — 角色列表

### GET /api/admin/audit-logs — 审计日志

**参数**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

## 错误码参考

| code | HTTP | 说明 |
|------|------|------|
| 0 | 200 | 成功 |
| 1 | 200/400 | 通用业务错误 |
| 401 | 401 | 未认证 / Token 过期 / IP/UA 不匹配 |
| 403 | 403 | 禁止访问（安全拦截） |
| 404 | 404 | 资源不存在 |
| 422 | 422 | 参数校验失败 |
| 429 | 429 | 请求过多 / 登录节流 / 并发限制 |
| 1001 | 200 | 认证失败（用户名或密码错误） |

---

## 安全拦截响应

当请求被安全中间件拦截时，返回 403：

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## 限流响应

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

`Retry-After` header 包含剩余等待秒数。
