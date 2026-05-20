# Ads Platform — 多平台广告管理系统

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## 概述

对接 **29 个广告平台**，统一管理广告投放与跨平台数据报表，支持告警监控、报表导出、多端访问。

### 支持的平台

#### 国内 (16)
| 平台 | 适配器 | 认证 |
|------|--------|------|
| 巨量引擎 | Juliang | OAuth2 Access-Token |
| 百度营销 | Baidu | OAuth2 + 信封签名 |
| 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5 |
| 腾讯广告 | Tencent | OAuth2 + nonce |
| 快手磁力引擎 | Kuaishou | OAuth2 URL参数 |
| 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer |
| 微博粉丝通 | Weibo | OAuth2 Bearer |
| B站花火 | Bilibili | OAuth2 Bearer |
| 优酷广告 | Youku | OAuth2 + MD5 |
| 美团广告 | Meituan | OAuth2 Bearer |
| 知乎广告 | Zhihu | OAuth2 Bearer |
| 360推广 | Qihoo360 | API Key + Sign |
| 搜狗推广 | Sogou | API Key + Sign |
| 友盟 | Umeng | API Key + MD5 |
| 京东京准通 | Jingdong | OAuth2 + MD5 |
| 拼多多广告 | Pinduoduo | OAuth2 + 自定义Sign |

#### 国际 (13)
| 平台 | 适配器 | 认证 |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 URL参数 |
| TikTok Ads | Tiktok | OAuth2 Access-Token |
| LinkedIn Ads | Linkedin | OAuth2 Bearer |
| Snapchat Ads | Snapchat | OAuth2 Bearer |
| Pinterest Ads | Pinterest | OAuth2 Bearer |
| Twitter/X Ads | Twitter | OAuth2 Bearer |
| Amazon Ads | Amazon | OAuth2 + Profile |
| The Trade Desk | TheTradeDesk | HMAC-SHA256 |
| Spotify Ads | Spotify | OAuth2 Bearer |
| Twitch Ads | Twitch | OAuth2 Bearer + ClientId |
| Netflix Ads | Netflix | OAuth2 client_credentials |

---

## 技术栈

| 层 | 技术 | 说明 |
|----|------|------|
| 服务端 | webman v2 + PHP 8.2+ | 7 个插件，45+ API 端点 |
| 数据库 | MySQL 8.0 | 18 张表，erik_ 前缀，Snowflake BIGINT 主键 |
| 缓存 | Redis 7 | API 响应缓存、仪表盘缓存、限流计数、Pub/Sub |
| 搜索 | Elasticsearch | webman-scout 自动索引同步（已配置） |
| 管理后台 | webman-admin v2 + Vue 3 + TypeScript + Element Plus | PHP 后端(端口 8789)，ServiceProxy 调用业务 API(端口 8788)，15+ 页面，ECharts 可视化 |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | PC/Mobile 响应式，Desktop Shell 布局，12+ 页面 |
| HarmonyOS | ArkTS + ArkUI | HTTP 客户端已就绪，UI 规划中 |
| 部署 | Docker + Nginx + GHCR | Docker Compose 一键启动，GitHub Actions 自动构建推送 |

## 架构图

```text
                        ┌──────────────────────┐
                        │  Flutter / HarmonyOS │
                        │  Admin / Browser     │
                        └──────────┬───────────┘
                                   │
                                   v
                        ┌──────────────────────┐
                        │     Nginx :80        │
                        │  / -> admin :8789    │
                        │  /api -> svc :8788   │
                        └──────┬───────┬───────┘
                               │       │
                  ┌────────────┘       └────────────┐
                  v                                 v
        ┌─────────────────┐               ┌─────────────────┐
        │  Admin :8789     │  ServiceProxy │  Service :8788  │
        │  webman-admin v2 │──────────────>│  webman v2 API  │
        │  RBAC / 审计     │   HTTP call   │  29 平台适配器  │
        └────────┬────────┘               └────────┬────────┘
                 │                                 │
                 └─────────────┬───────────────────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
              v                v                v
        ┌──────────┐   ┌──────────┐    ┌───────────┐
        │ MySQL 8.0│   │ Redis 7  │    │    ES     │
        │ erik_ 18 │   │ 缓存 队列│    │  搜索索引 │
        └──────────┘   └──────────┘    └───────────┘
                               │
                               v
                    ┌──────────────────┐
                    │   29 广告平台 API │
                    │ 巨量/百度/Google  │
                    │ Meta / TikTok ...│
                    └──────────────────┘
```

> 完整架构图、业务逻辑图、部署图见 [设计文档](docs/superpowers/specs/2026-05-14-ads-platform-design.md)

## 架构说明

- **`service/`** — webman v2 用户端业务 API 服务，监听端口 **8788**。处理广告平台对接、OAuth 授权、数据同步、报表引擎、告警监控等业务逻辑。
- **`admin/`** — webman-admin v2 独立管理后台，监听端口 **8789**。包含 PHP 后端（认证鉴权、用户管理、系统配置）和 Vue 3 SPA 前端。
- **管理后台与业务服务的通信** — Admin 通过 `ServiceProxy`（基于 cURL 的 HTTP 代理）调用 service API，转发管理员请求并携带 JWT Token。
- **开发模式** — Vite dev server (端口 5173) 将 `/api` 代理至 service:8788；admin PHP 后端在 8789 提供 session 认证和 SPA 静态服务。
- **生产模式** — Nginx 将 `/` 路由至 admin:8789（管理后台 SPA），将 `/api/` 路由至 service:8788（业务 API）。

## Erik Stack 集成

| 包 | 用途 |
|----|------|
| `erikwang2013/snowflake-php` | 分布式 Snowflake ID 生成 |
| `erikwang2013/hashids` | API ID 参数加解密 |
| `erikwang2013/jwt-webman` | JWT 认证令牌 |
| `erikwang2013/encryption` | API 层敏感数据加解密 |
| `erikwang2013/encryptable` | DB 字段级自动加解密 |
| `erikwang2013/webman-scout` | Elasticsearch 数据同步 |
| `erikwang2013/season` | 国家旗帜标识 |
| `erikwang2013/poster-php` | 滑块验证码（登录保护） |

## 国际化

全部界面支持 **中文 (zh-CN)** / **English (en)** 双语切换：

| 端 | 技术 | 切换方式 |
|----|------|---------|
| Admin | vue-i18n v9 | TopBar 语言下拉菜单，localStorage 持久化 |
| Service API | `erik\support\I18n` | Accept-Language 请求头 / `?lang=` 参数 |
| Flutter | AppLocalizations + Delegate | 系统语言自动检测 |
| HarmonyOS | StringResources | `setLang()` 切换 |

## 安全

**11 层中间件**（service 全量，admin 含 AttackGuard/ClientPlatform/Version）：CORS（白名单） → SecurityHeaders → AttackGuard（XSS 11模式/路径遍历/Header注入/Body 10MiB/Content-Type白名单） → ClientPlatform（8端来源识别） → Version → RateLimit → SQLGuard → Validation → ResponseTime → Encryption → JWT

**认证**：服务端和 admin 统一用 `admin_users` 表 + bcrypt 哈希认证，JWT Token 24h 有效期 + refresh 端点自动轮换

**CORS**：生产环境白名单模式（`CORS_ALLOWED_ORIGINS`），开发环境允许全部来源

**操作来源识别**：所有客户端通过 `X-Client-Platform` 头标识来源端（web/ios/android/ipados/macos/windows/linux/harmonyos），审计日志自动记录

**数据加密**：`access_token`/`refresh_token` 由 encryptable 自动 DB 加解密，API 敏感传输由 encryption 中间件处理

**验证码**：登录等敏感操作需通过滑块验证码（erikwang2013/poster-php），token 有效期 5 分钟、偏移容差 5px

**二次确认**：删除/解绑/批量操作等敏感操作采用"输入以确认"模式（`GlobalConfirm` + `useConfirmStore`），需输入目标名称或确认词方可执行

---

## 快速启动

### Docker (推荐)

```bash
# 启动全部服务 (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# 初始化数据库（创建表 + 种子数据）
make db-init

# 访问
# 管理后台: http://localhost
# API: http://localhost/api（Header: X-API-Version: v1）
```

### 本地开发

```bash
# 服务端 (端口 8788)
cd service && composer install && php start.php start

# 管理后台 (端口 5173)
cd admin/public/web && npm install && npm run dev

# Flutter App
cd apps/flutter && flutter run -d chrome  # Web PC
# HarmonyOS App
# 使用 DevEco Studio 打开 apps/harmonyos 目录
cd apps/flutter && flutter run -d android # Mobile

# TypeScript 检查
cd admin/public/web && npx vue-tsc --noEmit   # 零错误
```

---

## 项目结构

```
ads-php/
├── service/                           # 用户端业务服务 (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (45+ 端点，版本路由)
│   │   │   ├── controller/v1/         # 14 个控制器
│   │   │   ├── middleware/            # 7 个中间件
│   │   │   ├── config/route.php       # 路由定义
│   │   │   └── route_helpers.php      # versioned() 辅助函数
│   │   ├── ads-platform/              # 平台适配器核心
│   │   │   ├── adapter/               # 29 个平台适配器
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # SQL 迁移 + 性能索引
│   │   ├── ads-account/               # OAuth 账户管理
│   │   ├── ads-task/                  # 定时任务调度 (5 cron)
│   │   ├── ads-alert/                 # 告警监控引擎
│   │   ├── ads-report/                # 报表引擎 (导出 CSV/Excel/PDF)
│   │   └── ads-tenant/                # 多租户管理
│   ├── support/                       # Erik Stack 工具类
│   │   ├── ControllerTrait.php        # 控制器公共 trait
│   │   ├── JwtService.php             # JWT 包装类
│   │   ├── CacheService.php           # Redis 缓存服务
│   │   ├── ExceptionHandler.php       # API 异常处理器
│   │   └── ApiResponse.php            # 统一响应格式
│   ├── config/                        # 全局配置 (DB/Redis/Log/Middleware)
│   ├── tests/                         # PHPUnit 测试 (35 tests)
│   │   ├── Unit/                      # 单元测试 (Middleware, Task)
│   │   └── Integration/               # 集成测试 (Auth, Health)
│   └── start.php                      # 服务入口
├── admin/                             # 独立管理后台 (webman-admin v2 :8789)
│   ├── public/web/src/
│   │   ├── views/                     # 15 个 Vue 页面
│   │   │   ├── dashboard/             # 仪表盘 (ECharts)
│   │   │   ├── campaign/              # 广告计划
│   │   │   ├── adgroup/               # 广告组
│   │   │   ├── creative/              # 广告创意
│   │   │   ├── report/                # 报表分析 + 导出
│   │   │   ├── alert/                 # 告警规则 + 记录
│   │   │   ├── notification/          # 通知中心
│   │   │   ├── bid/                   # 自动出价规则
│   │   │   └── system/                # 用户管理 + 审计日志
│   │   ├── api/                       # 9 个 API 客户端
│   │   ├── stores/                    # 4 个 Pinia Store
│   │   └── components/                # 共享组件 (ListPageLayout 等)
│   ├── app/                           # PHP 后端 (controller/middleware)
│   └── config/                        # Admin 配置
├── apps/
│   ├── flutter/                       # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/              # 12 个功能页面 + Shell 布局
│   │       ├── config/menu_config.dart # 两级菜单配置
│   │       ├── router.dart            # GoRouter (ShellRoute + 路由守卫)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API Client 就绪)
├── docker/                            # Docker & Nginx 配置
├── .github/workflows/                 # CI (语法→测试→TS→Docker) + CD (构建推送)
├── docs/                              # 设计文档、实施计划、Skills
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## API 端点

> 所有 API 端点均需 Header `X-API-Version: v1`。版本号不出现于 URL 路径。

### 认证 & 基础

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/auth/login | 登录获取 JWT Token |
| GET | /api/auth/me | 当前用户信息 |
| POST | /api/auth/refresh | 刷新 JWT Token（旧 Token 自动黑名单） |
| GET | /api/platforms | 29 个适配平台列表（缓存 1h） |
| GET | /api/platforms/:code/oauth-url | 获取 OAuth 授权 URL |
| POST | /api/platforms/:code/callback | OAuth 回调处理 |
| GET | /health | 健康检查（DB + Redis 连通性） |
| GET | /ping | 轻量探活 |
| GET | /docs | API 文档（HTML） |
| GET | /api/captcha/generate | 生成滑块验证码 |
| POST | /api/captcha/verify | 验证滑块偏移量 |

### 广告计划

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/campaigns | 列表（筛选/排序/分页，含今日汇总） |
| POST | /api/campaigns | 创建广告计划 |
| GET | /api/campaigns/:id | 详情（含今日指标） |
| PUT | /api/campaigns/:id | 更新广告计划 |
| POST | /api/campaigns/:id/toggle | 启停广告计划 |
| POST | /api/campaigns/batch/toggle | 批量启停 |

### 广告组

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/ad-groups | 列表（支持 platform/campaign_id/status 筛选） |
| POST | /api/ad-groups | 创建广告组（支持定位模板） |
| GET | /api/ad-groups/:id | 详情（含今日指标） |
| PUT | /api/ad-groups/:id | 更新广告组 |
| POST | /api/ad-groups/:id/toggle | 启停广告组 |

### 广告创意

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/creatives | 列表（支持 platform/ad_group_id/media_type 筛选） |
| GET | /api/creatives/:id | 详情（含今日指标） |

### 报表

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/reports/summary | 仪表盘汇总（缓存 5 分钟） |
| GET | /api/reports/custom | 自定义多维度报表 |
| GET | /api/reports/export | 导出 CSV/Excel |
| GET | /api/reports/export-dashboard | 导出仪表盘 PDF |

### 账户

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/accounts | 已绑定账户列表（缓存 5 分钟） |
| GET | /api/accounts/:id | 账户详情（缓存 5 分钟） |
| DELETE | /api/accounts/:id | 解绑账户 |
| POST | /api/accounts/:id/sync | 手动触发数据同步 |

### 告警

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/alerts/rules | 告警规则列表（缓存 2 分钟） |
| POST | /api/alerts/rules | 创建告警规则 |
| PUT | /api/alerts/rules/:id | 更新告警规则 |
| DELETE | /api/alerts/rules/:id | 删除告警规则 |
| GET | /api/alerts/logs | 告警记录（按状态筛选） |
| POST | /api/alerts/logs/:id/acknowledge | 确认告警 |
| GET | /api/alerts/unread-count | 未读告警数量（缓存 30s） |

### 通知

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/notifications | 通知列表（支持 type/is_read 筛选） |
| GET | /api/notifications/unread-count | 未读通知数量 |
| POST | /api/notifications/:id/read | 标记单条已读 |
| POST | /api/notifications/read-all | 全部已读 |

### 自动出价

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/bid-rules | 规则列表 |
| POST | /api/bid-rules | 创建出价规则 |
| PUT | /api/bid-rules/:id | 更新出价规则 |
| DELETE | /api/bid-rules/:id | 删除出价规则 |
| GET | /api/bid-rules/logs | 出价调整历史 |

### 定向模板

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/targeting-templates | 模板列表（按平台筛选） |
| GET | /api/targeting-templates/:id | 模板详情 |
| POST | /api/targeting-templates | 创建定向模板 |
| PUT | /api/targeting-templates/:id | 更新定向模板 |
| DELETE | /api/targeting-templates/:id | 删除定向模板 |

### Admin 端点（端口 8789）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/admin/login | 管理员登录 |
| GET | /api/admin/me | 当前管理员信息（含角色权限） |
| GET | /api/admin/users | 用户管理列表 |
| POST | /api/admin/users | 创建管理员用户 |
| PUT | /api/admin/users/:id | 更新管理员用户 |
| DELETE | /api/admin/users/:id | 禁用管理员用户 |
| GET | /api/admin/audit-logs | 审计日志（按操作人/类型/日期筛选） |
| GET | /api/admin/roles | 可用角色列表 |

---

## 数据库

**命名规范**: 表前缀 `erik_`，主键 `BIGINT UNSIGNED PRIMARY KEY`（无自增，Snowflake ID），引擎 InnoDB，字符集 utf8mb4

| 分类 | 表名 | 用途 |
|------|------|------|
| 基础 | `erik_tenants` | 多租户 |
| 账户 | `erik_platform_accounts`, `erik_auth_tokens` | OAuth 平台账户 |
| 投放 | `erik_campaigns`, `erik_ad_groups`, `erik_creatives` | 广告投放层级 |
| 报表 | `erik_report_metrics`, `erik_report_extras` | 统一报表指标 |
| 告警 | `erik_alert_rules`, `erik_alert_logs` | 告警监控 |
| 出价 | `erik_bid_rules`, `erik_bid_logs` | 自动出价规则 + 历史 |
| 定向 | `erik_targeting_templates` | 受众定向模板 |
| 通知 | `erik_notifications` | 站内通知 |
| 系统 | `erik_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | 同步错误、RBAC、审计 |

---

## 定时任务

| 任务 | 频率 | 功能 |
|------|------|------|
| TokenRefreshTask | 每 55 分钟 | 扫描过期 OAuth Token，自动刷新 |
| DataSyncTask | 每 10 分钟 | 拉取各平台计划+广告组+创意+报表，写入统一表，清除缓存 |
| AlertCheckTask | 每 5 分钟 | 遍历启用告警规则，评估阈值，触发推送 |
| BidCheckTask | 每 10 分钟 | 遍历自动出价规则，查询指标，执行预算调整/启停 |
| RetrySyncTask | 每 3 分钟 | 重试失败的同步任务（最多3次，指数退避） |

---

## 测试

```bash
cd service && ./vendor/bin/phpunit
# 35 测试 / 70 断言
```

**覆盖范围**: 中间件 (Version/SQLGuard/SecurityHeaders) · 数据对象 (CampaignData/FieldMapping/Hashids) · 引擎 (ReportBuilder/AdapterRegistry) · 集成测试 (Auth/Health)

```bash
# TypeScript 检查
cd admin/public/web && npx vue-tsc --noEmit   # 零错误

# Dart 分析
cd apps/flutter && dart analyze   # 零错误
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): 自动管线 — **PHP Syntax → PHPUnit → TypeScript → Docker Build**

**CD** (`.github/workflows/deploy.yml`): 手动触发 — **Docker Buildx → 推送 GHCR (service/admin/admin-php) → 部署通知**

`.github/dependabot.yml` 每周自动更新 Composer + npm + Docker 依赖。

---

## Skills

`docs/skills/` — 6 个可复用项目技能：

| Skill | 说明 |
|------|------|
| `adapter-generator` | 生成新的广告平台适配器（14 方法模板） |
| `migration-generator` | 生成 SQL 迁移文件（erik_ 前缀 + BIGINT PK） |
| `erik-stack` | Erik Stack 7 包集成使用指南 |
| `admin-page-generator` | 生成 Vue3 管理后台页面 |
| `api-endpoint` | 添加 RESTful API 端点 |
| `tdd-workflow` | TDD 验证流程（测试→实现→语法→TypeScript→提交） |


## 开源不易，欢迎支持

| 微信 | 支付宝 |
|:---:|:---:|
| ![微信](./docs/weixinpay.png "微信") | ![支付宝](./docs/alipay.png "支付宝") |

---

## 许可证

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
