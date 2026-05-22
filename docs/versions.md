# 版本对比

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| 版本 | 授权 | 获取方式 |
|------|------|----------|
| **简化版 (Lite)** | 开源 (MIT) | GitHub 公开仓库 |
| **标准版 (Standard)** | 商业授权 | 联系 erik@erik.xyz |
| **完整版 (Full)** | 商业授权 | 联系 erik@erik.xyz |

---

## 功能对比

### 基础功能

| 功能 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 认证 (登录/Token刷新/当前用户) | ✅ | ✅ | ✅ |
| 平台管理 (29 平台列表 + OAuth) | ✅ | ✅ | ✅ |
| 账户管理 (CRUD + 同步) | ✅ | ✅ | ✅ |
| 广告计划 (CRUD + 启停 + 批量) | ✅ | ✅ | ✅ |
| 报表 (仪表盘 + 自定义 + 导出 CSV/Excel/PDF) | ✅ | ✅ | ✅ |
| 健康检查 + API 文档 + 验证码 | ✅ | ✅ | ✅ |
| 数据同步 (Campaign + Report) | ✅ | ✅ | ✅ |

### 投放管理

| 功能 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 广告组 (CRUD + 启停) | — | ✅ | ✅ |
| 广告创意 (列表 + 详情) | — | ✅ | ✅ |
| 广告组/创意数据同步 | — | ✅ | ✅ |

### 监控与通知

| 功能 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 告警规则引擎 (7 指标/4 条件/3 范围) | — | ✅ | ✅ |
| 告警记录 + 确认 + 未读数 | — | ✅ | ✅ |
| 通知中心 (列表/已读/全部已读) | — | ✅ | ✅ |

### 高级功能

| 功能 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 自动出价规则引擎 (3 动作/冷却) | — | — | ✅ |
| 受众定向模板 (通用 JSON Schema) | — | — | ✅ |
| 广告素材库 (上传/画廊/预览) | — | — | ✅ |
| 预算预警 (三段告警 50/80/100%) | — | — | ✅ |
| 投放日历 (Gantt 可视化) | — | — | ✅ |
| 跨平台归因 (5 模型/30 天回溯) | — | — | ✅ |

---

## 安全防护对比

| 防护项 | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| CORS 白名单 | ✅ | ✅ | ✅ |
| 安全响应头 (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| 版本路由 (X-API-Version) | ✅ | ✅ | ✅ |
| 接口限流 (滑动窗口) | ✅ | ✅ | ✅ |
| SQL 注入检测 (模式匹配) | ✅ | ✅ | ✅ |
| 输入过滤 (strip_tags + trim) | ✅ | ✅ | ✅ |
| 传输加解密 (X-Encrypted) | ✅ | ✅ | ✅ |
| JWT Bearer 认证 | ✅ | ✅ | ✅ |
| XSS 攻击检测 (11 模式) | — | ✅ | ✅ |
| 路径遍历检测 (7 模式) | — | ✅ | ✅ |
| Header 注入检测 | — | ✅ | ✅ |
| Body 大小限制 (10 MiB) | — | ✅ | ✅ |
| Content-Type 白名单 | — | ✅ | ✅ |
| 客户端来源识别 (8 端) | — | ✅ | ✅ |
| 登录节流 (5次→15分钟) | — | ✅ | ✅ |
| 响应时间监控 (X-Response-Time) | — | ✅ | ✅ |
| Origin/Referer 校验 | — | — | ✅ |
| 防重放攻击 (Nonce+Timestamp) | — | — | ✅ |
| 并发会话限制 (最大3个) | — | — | ✅ |
| CSRF Token (Admin端) | — | — | ✅ |
| SSRF 防护 (OAuth 白名单) | — | — | ✅ |
| 日志数据脱敏 | — | — | ✅ |
| JWT IP/UA 绑定 | — | — | ✅ |

---

## 中间件链对比

### Service 端

| Lite (7 层) | Standard (11 层) | Full (15 层) |
|-------------|-----------------|-------------|
| CorsMiddleware | CorsMiddleware | CorsMiddleware |
| — | — | OriginGuardMiddleware |
| SecurityHeadersMiddleware | SecurityHeadersMiddleware | SecurityHeadersMiddleware |
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | ReplayGuardMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |
| RateLimitMiddleware | RateLimitMiddleware | RateLimitMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | — | SessionLimitMiddleware |
| SqlGuardMiddleware | SqlGuardMiddleware | SqlGuardMiddleware |
| ValidationMiddleware | ValidationMiddleware | ValidationMiddleware |
| — | ResponseTimeMiddleware | ResponseTimeMiddleware |
| EncryptionMiddleware | EncryptionMiddleware | EncryptionMiddleware |

### Admin 端

| Lite (1 层) | Standard (4 层) | Full (5 层) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## 定时任务对比

| 任务 | 频率 | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55min | ✅ | ✅ | ✅ |
| DataSyncTask | 10min | ✅ (仅 Campaign+Report) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5min | — | ✅ | ✅ |
| BidCheckTask | 10min | — | — | ✅ |
| BudgetCheckTask | 15min | — | — | ✅ |

---

## 数据库表对比

| 分类 | 表名 | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| 基础 | erik_tenants | ✅ | ✅ | ✅ |
| 账户 | erik_platform_accounts | ✅ | ✅ | ✅ |
| | erik_auth_tokens | ✅ | ✅ | ✅ |
| 投放 | erik_campaigns | ✅ | ✅ | ✅ |
| | erik_report_metrics | ✅ | ✅ | ✅ |
| | erik_report_extras | ✅ | ✅ | ✅ |
| | erik_ad_groups | — | ✅ | ✅ |
| | erik_creatives | — | ✅ | ✅ |
| 告警 | erik_alert_rules | — | ✅ | ✅ |
| | erik_alert_logs | — | ✅ | ✅ |
| 通知 | erik_notifications | — | ✅ | ✅ |
| 出价 | erik_bid_rules | — | — | ✅ |
| | erik_bid_logs | — | — | ✅ |
| 定向 | erik_targeting_templates | — | — | ✅ |
| 素材 | erik_assets | — | — | ✅ |
| 归因 | erik_conversions | — | — | ✅ |
| | erik_attribution_results | — | — | ✅ |
| 系统 | erik_sync_errors | ✅ | ✅ | ✅ |
| 管理 | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **合计** | | **8** | **13** | **18** |

---

## 前端页面对比

### Vue Admin SPA

| 页面 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 登录 | ✅ | ✅ | ✅ |
| 仪表盘 | ✅ | ✅ | ✅ |
| 账户列表 + 绑定 | ✅ | ✅ | ✅ |
| 广告计划 | ✅ | ✅ | ✅ |
| 报表导出 | ✅ | ✅ | ✅ |
| 用户管理 | ✅ | ✅ | ✅ |
| 审计日志 | ✅ | ✅ | ✅ |
| 广告组 | — | ✅ | ✅ |
| 广告创意 | — | ✅ | ✅ |
| 报表分析 (ECharts) | — | ✅ | ✅ |
| 告警规则 | — | ✅ | ✅ |
| 告警记录 | — | ✅ | ✅ |
| 通知中心 | — | ✅ | ✅ |
| 自动出价 | — | — | ✅ |
| 素材库 | — | — | ✅ |
| 投放日历 | — | — | ✅ |
| 归因分析 | — | — | ✅ |
| **合计** | **7** | **13** | **17** |

### Flutter

| 页面 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 登录 | ✅ | ✅ | ✅ |
| 仪表盘 | ✅ | ✅ | ✅ |
| 广告计划 (列表+详情) | ✅ | ✅ | ✅ |
| 数据报表 | ✅ | ✅ | ✅ |
| 平台账户 | ✅ | ✅ | ✅ |
| 告警管理 | ✅ | ✅ | ✅ |
| 广告组 | — | ✅ | ✅ |
| 广告创意 | — | ✅ | ✅ |
| 报表分析 | — | ✅ | ✅ |
| 通知中心 | — | ✅ | ✅ |
| 自动出价 | — | — | ✅ |
| **合计** | **6** | **10** | **11** |

---

## API 端点对比

| 模块 | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| 系统 (health/ping/docs/captcha) | 6 | 6 | 6 |
| 认证 (login/me/refresh) | 3 | 3 | 3 |
| 平台 (list/oauthUrl/callback) | 3 | 3 | 3 |
| 账户 (index/show/destroy/sync) | 4 | 4 | 4 |
| 广告计划 (CRUD/toggle/batch) | 6 | 6 | 6 |
| 广告组 (CRUD/toggle) | — | 5 | 5 |
| 创意 (index/show) | — | 2 | 2 |
| 报表 (summary/custom/export×2) | 4 | 4 | 4 |
| 报表 (calendar/budget/attribution/models) | — | — | 4 |
| 告警 (rules CRUD + logs + acknowledge + unread) | — | 7 | 7 |
| 通知 (index/unread/read/readAll) | — | 4 | 4 |
| 自动出价 (CRUD + logs) | — | — | 5 |
| 定向模板 (CRUD) | — | — | 5 |
| 素材库 (index/upload/show/destroy) | — | — | 4 |
| **合计** | **26** | **44** | **62** |

---

## 技术栈

三层版本共享统一技术栈：

| 层 | 技术 |
|----|------|
| 后端框架 | webman v2, PHP 8.2+ |
| 数据库 | MySQL 8.0 (InnoDB, utf8mb4) |
| 缓存 | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| 认证 | erikwang2013/jwt-webman |
| ID 生成 | erikwang2013/snowflake-php |
| ID 编码 | erikwang2013/hashids |
| 前端 | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| 部署 | Docker + Nginx + Docker Compose |

---

## 升级路径

```
Lite (开源)
  │
  ├─→ 升级到 Standard (联系 erik@erik.xyz)
  │     │
  │     └─→ 新增: 广告组/创意管理、告警引擎、通知中心、
  │              AttackGuard/XSS/路径遍历/登录节流/响应时间监控
  │
  └─→ 升级到 Full (联系 erik@erik.xyz)
        │
        └─→ 新增: Standard 全部 + 自动出价、定向模板、素材库、
                  预算预警、投放日历、跨平台归因、防重放/并发限制/CSRF/SSRF
```
