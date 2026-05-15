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
| 管理后台 | Vue 3 + TypeScript + Element Plus | 15+ 页面，ECharts 可视化 |
| Flutter | Dart 3 + Riverpod + GoRouter | PC/Mobile 响应式三断点 |
| HarmonyOS | ArkTS + ArkUI | 6 页，4 组件，HTTP 客户端 |
| 部署 | Docker + Nginx | 一键启动全套服务 |

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

## 安全

**7 层中间件**：CORS → RateLimit (滑动窗口60次/60s) → SQLGuard (注入检测) → Validation (输入过滤) → Encryption (X-Encrypted) → JWT (Bearer Token) → TenantIdentify

**数据加密**：`access_token`/`refresh_token` 由 encryptable 自动 DB 加解密，API 敏感传输由 encryption 中间件处理

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
cd admin && npm install && npm run dev

# Flutter App
cd flutter && flutter run -d chrome  # Web PC
cd flutter && flutter run -d android # Mobile

# TypeScript 检查
cd admin && npx vue-tsc --noEmit   # 零错误
```

---

## 项目结构

```
ads-php/
├── service/                    # PHP 服务端 (webman v2)
│   ├── plugin/
│   │   ├── ads-tenant/         # 多租户管理
│   │   ├── ads-account/        # 账户 & OAuth 授权
│   │   ├── ads-platform/       # 29 个广告平台适配器
│   │   ├── ads-api/            # RESTful API (7 控制器 + 7 中间件)
│   │   ├── ads-task/           # 定时任务调度
│   │   ├── ads-report/         # 报表引擎 & 导出
│   │   └── ads-alert/          # 告警监控
│   ├── config/                 # 配置文件（带注释）
│   └── support/                # Erik Stack 工具类
├── admin/                      # Vue3 + TS 管理后台
│   └── src/
│       ├── views/              # 页面 (dashboard/campaign/account/alert/report)
│       ├── components/         # 组件 (layout/MetricCard/PlatformBadge)
│       ├── api/                # Axios API 层 (7 模块)
│       ├── stores/             # Pinia 状态 (auth/alert)
│       └── router/             # Vue Router
├── flutter/                    # Flutter App (PC Web 优先)
│   └── lib/
│       ├── features/           # 功能页 (dashboard/campaign/report/account/alert)
│       ├── shared/api/         # Dio HTTP 客户端
│       └── stores/             # Riverpod 状态管理
├── harmonyos/                  # HarmonyOS App (ArkTS)
│   └── entry/src/main/ets/
│       ├── pages/              # 6 页面
│       ├── api/                # HTTP 客户端
│       └── widgets/            # 4 组件
├── docker/                     # Nginx 配置
├── docs/                       # 设计文档 & 实施计划
├── docker-compose.yml          # Docker 编排
├── Dockerfile                  # PHP 镜像
├── Dockerfile.admin            # 前端 Nginx 镜像
└── Makefile                    # 运维快捷命令
```

---

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

---

## 数据库

**命名规范**: 表前缀 `erik_`，主键 `BIGINT UNSIGNED PRIMARY KEY`（无自增，Snowflake ID），引擎 InnoDB，字符集 utf8mb4

**13 张表**: `erik_tenants` / `erik_platform_accounts` / `erik_auth_tokens` / `erik_campaigns` / `erik_ad_groups` / `erik_creatives` / `erik_report_metrics` / `erik_report_extras` / `erik_alert_rules` / `erik_alert_logs`

---

## 定时任务

| 任务 | 频率 | 功能 |
|------|------|------|
| TokenRefreshTask | 每 55 分钟 | 扫描过期 Token，自动刷新 |
| DataSyncTask | 每 10 分钟 | 拉取各平台计划+报表，写入统一表，清仪表盘缓存 |
| AlertCheckTask | 每 5 分钟 | 遍历启用告警规则，评估阈值，触发推送 |

---

## 许可证

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
