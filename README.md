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
| 服务端 | webman v2 + PHP 8.2+ | 7 个插件，25+ API 端点 |
| 数据库 | MySQL 8.0 | 13 张表，erik_ 前缀，Snowflake BIGINT 主键 |
| 缓存 | Redis 7 | 仪表盘缓存，限流计数，Pub/Sub |
| 搜索 | Elasticsearch | webman-scout 自动索引同步 |
| 管理后台 | webman-admin v2 + Vue 3 + TypeScript + Element Plus | PHP 后端(端口 8789)，ServiceProxy 调用业务 API(端口 8788)，15+ 页面，ECharts 可视化 |
| Flutter | Dart 3 + Riverpod + GoRouter | PC/Mobile 响应式三断点 |
| HarmonyOS | ArkTS + ArkUI | 6 页，4 组件，HTTP 客户端 |
| 部署 | Docker + Nginx | 一键启动全套服务 |

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
        │ erik_ 14 │   │ 缓存 队列│    │  搜索索引 │
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

**7 层中间件**：CORS → RateLimit (滑动窗口60次/60s) → SQLGuard (注入检测) → Validation (输入过滤) → Encryption (X-Encrypted) → JWT (Bearer Token) → TenantIdentify

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
# API: http://localhost/api/v1
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
├── service/                     # 用户端业务服务 (webman v2 :8788)
│   ├── plugin/                  # 7 个业务插件
│   ├── config/                  # 配置文件
│   ├── support/                 # Erik Stack 工具类
│   └── tests/                   # PHPUnit 测试
├── admin/                       # 独立管理后台 (webman-admin v2 :8789)
│   ├── public/web/              # Vue 3 + TS SPA 源码 (vite.config.ts, src/)
│   ├── app/                     # PHP 后端 (controller/middleware/service)
│   ├── config/                  # Admin 配置
│   └── start.php                # Admin 入口
├── apps/                        # 客户端 App
│   ├── flutter/                 # Flutter (PC Web/Mobile 响应式)
│   └── harmonyos/               # HarmonyOS (ArkTS)
├── docker/                      # Docker & Nginx 配置
├── .github/                     # CI/CD workflows
├── docs/                        # 设计文档 & 实施计划
├── docker-compose.yml           # Docker 一键部署
├── Dockerfile                   # PHP 镜像
├── Dockerfile.admin             # Admin Nginx 镜像
└── Makefile                     # 运维快捷命令
```

## API 端点

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/v1/auth/login | 登录获取 JWT Token |
| GET | /api/v1/auth/me | 当前用户信息 |
| GET | /api/v1/platforms | 29 个适配平台列表 |
| GET | /api/v1/platforms/:code/oauth-url | 获取 OAuth 授权 URL |
| POST | /api/v1/platforms/:code/callback | OAuth 回调处理 |
| GET | /api/v1/accounts | 已绑定账户列表 |
| GET | /api/v1/accounts/:id | 账户详情 |
| DELETE | /api/v1/accounts/:id | 解绑账户 |
| POST | /api/v1/accounts/:id/sync | 手动触发数据同步 |
| GET | /api/v1/campaigns | 广告计划列表（筛选/排序/分页） |
| POST | /api/v1/campaigns | 创建广告计划 |
| GET | /api/v1/campaigns/:id | 计划详情（含今日数据） |
| PUT | /api/v1/campaigns/:id | 更新广告计划 |
| POST | /api/v1/campaigns/:id/toggle | 启停广告计划 |
| GET | /api/v1/reports/summary | 仪表盘汇总（缓存 5 分钟） |
| GET | /api/v1/reports/custom | 自定义多维度报表 |
| GET | /api/v1/reports/export | 导出 CSV/Excel |
| GET | /api/v1/reports/export-dashboard | 导出仪表盘 PDF |
| GET | /api/v1/alerts/rules | 告警规则列表 |
| POST | /api/v1/alerts/rules | 创建告警规则 |
| PUT | /api/v1/alerts/rules/:id | 更新告警规则 |
| DELETE | /api/v1/alerts/rules/:id | 删除告警规则 |
| GET | /api/v1/alerts/logs | 告警记录（按状态筛选） |
| POST | /api/v1/alerts/logs/:id/acknowledge | 确认告警 |
| GET | /api/v1/alerts/unread-count | 未读告警数量 |
| GET | /api/v1/docs | API 文档（HTML，免认证） |
| GET | /api/v1/captcha/generate | 生成滑块验证码 |
| POST | /api/v1/captcha/verify | 验证滑块偏移量 |

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

**14 张表**: `erik_tenants` / `erik_platform_accounts` / `erik_auth_tokens` / `erik_campaigns` / `erik_ad_groups` / `erik_creatives` / `erik_report_metrics` / `erik_report_extras` / `erik_alert_rules` / `erik_alert_logs` / `erik_sync_errors` / `admin_users` / `admin_roles` / `admin_audit_logs`

---

## 定时任务

| 任务 | 频率 | 功能 |
|------|------|------|
| TokenRefreshTask | 每 55 分钟 | 扫描过期 Token，自动刷新 |
| DataSyncTask | 每 10 分钟 | 拉取各平台计划+报表，写入统一表，清仪表盘缓存 |
| AlertCheckTask | 每 5 分钟 | 遍历启用告警规则，评估阈值，触发推送 |
| RetrySyncTask | 每 3 分钟 | 重试失败的同步任务（最多3次，指数退避） |

---

## 测试

```bash
cd service && ./vendor/bin/phpunit
# 20 测试 / 41 断言 — 覆盖 FieldMapping / Hashids / ReportBuilder / CampaignData / AdapterRegistry
```

## CI/CD

GitHub Actions 自动管线：**PHP Syntax → PHPUnit → TypeScript → Docker Build**

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


## 许可证

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
