# Ads Platform — 多平台广告管理系统

（多语言版本文档见历史 tag：`git checkout <tag> -- docs/README.*.md`）

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## 项目宠物「阿章」

<p align="center"><img src="assets/diagrams/pet.svg" width="160" alt="项目宠物 阿章"></p>

**阿章**（zhāng）是本项目的吉祥物：一只章鱼。它八爪齐下，每条触手各管一摊、同时伸向 29 个平台——正是这套系统在做的事。

| 形象 | 寓意 | 对应实现 |
|------|------|---------|
| 八条触手 | 六个插件各司其职、并行不悖 | ads-account / ads-api / ads-platform / ads-storage / ads-task / ads-tenant |
| 触手吸盘 | 数据一路吸附汇总 | 29 家平台 → 统一 `/api/v1` |
| 双目前视 | 多端同时盯屏 | Vue Admin / Flutter / HarmonyOS 三端共用一套 API |
| 眨眼 + 起伏 | 周期性轮询与守护 | 4 个定时任务（3/5/10/55 分钟）循环采集；22 项防护静默运行 |
| 外环 29 个节点 | 29 个平台常驻在线 | 16 国内 + 13 国际，逐个盯住 |

阿章已整合进代码：管理后台 **登录页** 与 **favicon**、Service 端 **`/docs` API 文档页**，以及全部文档页。

> SVG 源码 [assets/diagrams/pet.svg](assets/diagrams/pet.svg)（无文字，13 种语言文档共用同一份；含 SMIL 眨眼与呼吸动画）

---


<!-- pet-expressions:start -->
## 项目宠物表情形态 · 阿章

<table align="center">
<tr><td align="center"><img src="assets/pet-expressions/smile.svg" width="88" alt="笑"><br><b>笑</b><br><code>:emoji-smile:</code></td><td align="center"><img src="assets/pet-expressions/joy.svg" width="88" alt="欢乐"><br><b>欢乐</b><br><code>:emoji-joy:</code></td><td align="center"><img src="assets/pet-expressions/cry.svg" width="88" alt="哭"><br><b>哭</b><br><code>:emoji-cry:</code></td><td align="center"><img src="assets/pet-expressions/depressed.svg" width="88" alt="沮丧"><br><b>沮丧</b><br><code>:emoji-depressed:</code></td><td align="center"><img src="assets/pet-expressions/terrified.svg" width="88" alt="惊恐"><br><b>惊恐</b><br><code>:emoji-terrified:</code></td><td align="center"><img src="assets/pet-expressions/angry.svg" width="88" alt="愤怒"><br><b>愤怒</b><br><code>:emoji-angry:</code></td></tr>
<tr><td align="center"><img src="assets/pet-expressions/coquettish.svg" width="88" alt="撒娇"><br><b>撒娇</b><br><code>:emoji-coquettish:</code></td><td align="center"><img src="assets/pet-expressions/cute.svg" width="88" alt="卖萌"><br><b>卖萌</b><br><code>:emoji-cute:</code></td><td align="center"><img src="assets/pet-expressions/pleasing.svg" width="88" alt="讨好"><br><b>讨好</b><br><code>:emoji-pleasing:</code></td><td align="center"><img src="assets/pet-expressions/knocking.svg" width="88" alt="敲打"><br><b>敲打</b><br><code>:emoji-knocking:</code></td><td align="center"><img src="assets/pet-expressions/facepalm.svg" width="88" alt="捂脸"><br><b>捂脸</b><br><code>:emoji-facepalm:</code></td><td align="center"><img src="assets/pet-expressions/tongue.svg" width="88" alt="吐舌头"><br><b>吐舌头</b><br><code>:emoji-tongue:</code></td></tr>
</table>

> 12 种彩色动态形态：笑 / 欢乐 / 哭 / 沮丧 / 惊恐 / 愤怒 / 撒娇 / 卖萌 / 讨好 / 敲打 / 捂脸 / 吐舌头。每个 SVG 自带表情、情绪特效与整体动效，并遵循 `prefers-reduced-motion`。
<!-- pet-expressions:end -->

<!-- assets-license:start -->
## 视觉资产授权 / Visual Assets License

本项目的项目宠物、图片、图标、表情（包括 `assets/pet-expressions/` 等目录下的
SVG / PNG）**不是开源资产**，版权归作者所有。未经书面许可不得复制、修改、再分发或
用于商业用途；商业使用需取得商业授权，联系 **erik@erik.xyz**。详见
[ASSETS-LICENSE.md](ASSETS-LICENSE.md)。

> 代码部分仍遵循仓库原有 `LICENSE`；第三方素材（如明确标注的 Go Gopher 等）仍遵循其原许可证。
<!-- assets-license:end -->


## 概述

**Ads Platform** 是一套多平台广告数据管理系统，对接 **29 个广告平台**（国内 16 家 + 国际 13 家），统一管理多平台账户与投放数据概览。

- **账户管理** — OAuth 授权与多平台账户绑定
- **数据概览** — 仪表盘跨平台指标汇总
- **素材库** — 图片/视频素材管理、画廊预览
- **素材存储** — 本地/阿里云 OSS/腾讯云 COS/S3 兼容多驱动（环境变量配置）
- **多端访问** — Web 管理后台 (Vue 3)、Flutter PC/Mobile、HarmonyOS
- **稳定可靠** — 平台调用熔断/降级/超时、三级缓存、高并发优化、22 项安全防护
- **国际化** — 界面中英双语
- **项目宠物** — 章鱼「阿章」，八爪并行，24 小时值守 29 个平台（[pet.svg](assets/diagrams/pet.svg)）

> API 文档：hg/apidoc 自动生成，启动服务后访问 `http://127.0.0.1:8788/apidoc`  
> 版本对比：Lite 开源 / Standard & Full 联系 erik@erik.xyz

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
| 服务端 | webman v2 + PHP 8.4+ | 6 个插件，26 API 端点 |
| 数据库 | MySQL 8.0 | 19 张表，ads_ 前缀，Snowflake BIGINT 主键 |
| 缓存 | Redis 7 | 三级缓存 (L1内存/L2 APCu/L3 Redis)、限流计数、Pub/Sub、消息队列 |
| 搜索 | Elasticsearch | webman-scout 自动索引同步（已配置） |
| 管理后台 | webman-admin v2 + Vue 3 + TypeScript + Element Plus | PHP 后端(端口 8789)，SPA 直连业务 API(端口 8788)，9 页面，ECharts 可视化 |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | PC/Mobile 响应式，Desktop Shell 布局，3 页面 |
| HarmonyOS | ArkTS + ArkUI | 3 个页面已实现，HTTP 客户端已就绪 |
| 部署 | Docker + Nginx + GHCR | Docker Compose 一键启动，GitHub Actions 自动构建推送 |

## 架构图

![系统架构图](assets/diagrams/architecture.svg)

### 请求流程图

![请求流程图](assets/diagrams/request-flow.svg)

### 功能模块图

![功能模块图](assets/diagrams/functional-modules.svg)

### 数据生命周期图

![数据生命周期图](assets/diagrams/data-lifecycle.svg)


## 架构说明

- **`service/`** — webman v2 用户端业务 API 服务，监听端口 **8788**。处理广告平台对接、OAuth 授权、数据同步、仪表盘指标等业务逻辑。
- **`admin/`** — webman-admin v2 独立管理后台，监听端口 **8789**。包含 PHP 后端（认证鉴权、用户管理、系统配置）和 Vue 3 SPA 前端。
- **管理后台与业务服务的通信** — Vue SPA 通过 axios（baseURL `/api`）直连 service API；admin 专属路由（`/api/admin/*`）由 admin PHP 后端（8789）提供服务，Nginx 按路径分流。
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
| `hg/apidoc` | API 文档自动生成（注解 + Web UI） |

## 国际化

全部界面支持 **中文 (zh-CN)** / **English (en)** 双语切换：

| 端 | 技术 | 切换方式 |
|----|------|---------|
| Admin | vue-i18n v9 | TopBar 语言下拉菜单，localStorage 持久化 |
| Service API | `erik\support\I18n` | Accept-Language 请求头 / `?lang=` 参数 |
| Flutter | AppLocalizations + Delegate | 系统语言自动检测 |
| HarmonyOS | StringResources | `setLang()` 切换 |

## 安全

### Service 端 (13 层全局 + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware（路由层）

### Admin 端 (9 层全局 + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck（路由层）

### 防护能力总览 (22 项)

| 分类 | 防护项 | 说明 |
|------|--------|------|
| 输入检测 | XSS (11模式) | script/iframe/event handler/javascript:/data: |
| | 路径遍历 (7模式) | ../ / null byte / /etc/passwd / .env / .git |
| | Header 注入 | CRLF 检测 |
| | Body 大小限制 | 10 MiB |
| | Content-Type 白名单 | JSON/Form/Multipart/Plain |
| | SQL 注入 | UNION/DROP/ALTER 模式检测 |
| 认证 | JWT Token 绑定 | IP + User-Agent hash 验证 |
| | Token 刷新 + 黑名单 | 旧 Token 自动失效 |
| | 登录节流 | 5 次失败 → 15 分钟锁定 (Redis) |
| | 并发会话限制 | 每用户最大 3 个活跃 Token |
| | 验证码 | 滑块验证码 (5分钟有效, 5px 容差) |
| 请求校验 | CORS 白名单 | 生产环境域名白名单 |
| | Origin/Referer 校验 | 跨域来源验证 |
| | CSRF Token | Admin 端 session token 验证 |
| | 防重放攻击 | Nonce + Timestamp ±5min (非浏览器端) |
| | 接口限流 | 滑动窗口 60次/60s |
| | SSRF 防护 | OAuth redirect_uri 白名单 |
| 响应头 | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | 防点击劫持 + HTTPS 强制 |
| | X-Content-Type-Options | nosniff |
| 数据保护 | 传输加密 | EncryptionMiddleware (X-Encrypted) |
| | 存储加密 | Encryptable (DB 字段级) |
| | 日志脱敏 | password/token/secret → \*\*\* |

### 安全架构图

![安全架构图](assets/diagrams/security.svg)

**防御纵深**：外层（Nginx）→ 入口守卫（5层中间件）→ 身份认证（7项）→ 输入校验（4项）→ 频率控制 → 数据加密 → 审计追溯

**认证**：服务端和 admin 统一用 `admin_users` 表 + bcrypt 哈希，JWT 24h + refresh 轮换

**审计**：所有操作记录 IP / User-Agent / Client-Platform / 操作详情

**二次确认**：删除/解绑/批量操作采用"输入确认词"模式（`GlobalConfirm` + `useConfirmStore`）

---

## 高级功能

| 功能 | 说明 | 技术 |
|------|------|------|
| 素材库 | 图片/视频上传管理、画廊预览、复制 URL | AssetController + Vue 画廊 |
| 平台调用弹性 | per-platform 熔断状态机 (5 次失败→OPEN→30s 半开探活)、降级快速失败、29 适配器超时核查 | CircuitBreaker + GuardedAdapter |
| 素材存储 | 对象存储多驱动 (local/oss/cos/s3)、预签名直传 | ads-storage 插件 |

---

## 高并发

| 优化 | 方案 | 文件 |
|------|------|------|
| 数据库读写分离 | 主库 `shared` + 只读副本 `read_replica`，SELECT 自动路由到副本 | `config/database.php` |
| DB 连接池 | `PDO::ATTR_PERSISTENT` 持久连接 + 时区初始化预热 | `config/database.php` |
| Redis 连接池 | `persistent` 持久连接 + 读写分离 `readonly` 配置 | `config/redis.php` |
| 三级缓存 | L1 进程内存 → L2 APCu 共享内存 → L3 Redis | `support/CacheService.php` |
| 消息队列异步 | Redis List 2 通道 (sync/notification) | `support/AsyncJobService.php` |
| Nginx 分级限流 | 30r/s + burst 20 + 20 并发连接 + keepalive 32 | `docker/nginx/admin.conf` |
| 水平扩展 | upstream 多实例 + 故障转移 + sticky session | `docker/nginx/admin.conf` |
| 静态资源缓存 | 静态资源 `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## 快速启动

### 一键 Web 安装（推荐）

启动服务后浏览器访问 `/install` 进入安装向导：

```bash
# 启动管理后台 (端口 8789)
cd admin && composer install && php start.php start

# 打开浏览器访问 http://localhost:8789/install
# 在安装向导中填写数据库信息、管理员账户，点击「开始安装」
```

安装向导将在网页上引导你完成：
1. **数据库连接** — 填写 MySQL 主机、端口、数据库名、用户名密码，支持连接测试
2. **Redis 配置** — 填写 Redis 连接信息（可选）
3. **管理员账户** — 设置后台登录用户名、密码、显示名称
4. **一键安装** — 自动建库、执行 `install.sql` 创建 19 张表并写入种子数据、更新管理员密码

安装完成后访问 `/` 进入管理后台，使用设置的用户名和密码登录。

### Docker (推荐生产环境)

```bash
# 启动全部服务 (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# 初始化数据库（创建表 + 种子数据）
make db-init

# 访问
# 管理后台: http://localhost
# 安装向导: http://localhost/install
# API: http://localhost/api/v1（版本号固定于 URL 路径，不在 Header 传递）
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
│   │   ├── ads-api/                   # REST API (30 端点，版本路由 /api/v1)
│   │   │   ├── controller/v1/         # 12 个控制器
│   │   │   ├── middleware/            # 14 个中间件
│   │   │   ├── config/route.php       # 路由定义
│   │   ├── ads-platform/              # 平台适配器核心
│   │   │   ├── adapter/               # 29 个平台适配器
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   └── model/                 # Campaign
│   │   ├── ads-account/               # OAuth 账户管理
│   │   ├── ads-task/                  # 定时任务调度 (3 cron)
│   │   ├── ads-tenant/                # 多租户管理
│   │   └── ads-storage/               # 存储抽象层 (local/OSS/COS/S3)
│   ├── public/                        # 静态资源 (webman 内置静态处理)
│   │   └── img/pet.svg                # 项目宠物「阿章」，/docs 页展示
│   ├── scripts/backfill-assets.php    # 存量素材回填对象存储
│   ├── support/                       # Erik Stack 工具类
│   │   ├── ControllerTrait.php        # 控制器公共 trait
│   │   ├── JwtService.php             # JWT 包装类
│   │   ├── CacheService.php           # Redis 缓存服务
│   │   ├── ExceptionHandler.php       # API 异常处理器
│   │   └── ApiResponse.php            # 统一响应格式
│   ├── config/                        # 全局配置 (DB/Redis/Log/Middleware)
│   ├── tests/                         # PHPUnit 测试 (154 tests)
│   │   ├── Unit/                      # 单元测试 (Middleware, Task, Engines)
│   │   └── Integration/               # 集成测试 (Auth, Health, API)
│   └── start.php                      # 服务入口
├── admin/                             # 独立管理后台 (webman-admin v2 :8789)
│   ├── public/web/
│   │   ├── public/pet.svg             # 项目宠物「阿章」，favicon + 登录页
│   │   ├── src/
│   │   │   ├── views/                 # 9 个 Vue 页面
│   │   │   │   ├── dashboard/         # 仪表盘 (ECharts)
│   │   │   │   ├── account/           # 账户管理 + 绑定
│   │   │   │   ├── asset/             # 素材库
│   │   │   │   ├── sync/              # 同步状态
│   │   │   │   ├── login/             # 登录页 (阿章)
│   │   │   │   └── system/            # 用户管理 + 审计日志 + 系统信息
│   │   │   ├── api/                   # 10 个 API 客户端
│   │   │   ├── stores/                # 4 个 Pinia Store
│   │   │   └── components/            # 共享组件 (ListPageLayout 等 6 个)
│   │   └── dist/                      # 构建产物 (不入库，CI 构建)
│   ├── app/                           # PHP 后端 (controller/middleware)
│   └── config/                        # Admin 配置
├── apps/
│   ├── flutter/                       # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/              # 3 个功能页面 + Shell 布局
│   │       ├── config/menu_config.dart # 两级菜单配置
│   │       ├── router.dart            # GoRouter (ShellRoute + 路由守卫)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API Client 就绪)
├── docker/                            # Docker & Nginx 配置
├── .github/workflows/                 # CI (语法→测试→TS→Docker) + CD (构建推送)
├── assets/                            # 公开视觉资产（版权归属见 ASSETS-LICENSE.md）
│   ├── diagrams/                      # 架构/请求流程/功能模块/数据生命周期/安全架构 + pet.svg
│   ├── pet-expressions/               # 阿章 12 种表情 SVG
│   └── weixinpay.png / alipay.png     # 打赏收款码
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## API 端点

> 全部 API 端点定义（请求/响应示例、错误码、限流策略）见 hg/apidoc 在线文档：启动服务后访问 `http://127.0.0.1:8788/apidoc`

## 数据库

**命名规范**: 表前缀 `ads_`，主键 `BIGINT UNSIGNED PRIMARY KEY`（无自增，Snowflake ID），引擎 InnoDB，字符集 utf8mb4

| 分类 | 表名 | 用途 |
|------|------|------|
| 基础 | `ads_tenants` | 多租户 |
| 账户 | `ads_platform_accounts`, `ads_auth_tokens` | OAuth 平台账户 |
| 投放数据 | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | 平台同步的只读投放层级 |
| 指标 | `ads_report_metrics` | 仪表盘使用的同步指标 |
| 素材 | `ads_assets` | 创意素材库 |
| 系统 | `ads_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | 同步错误、RBAC、审计 |

---

## 定时任务

| 任务 | 频率 | 功能 |
|------|------|------|
| TokenRefreshTask | 每 55 分钟 | 扫描过期 OAuth Token，自动刷新 |
| DataSyncTask | 每 10 分钟 | 拉取各平台计划+广告组+创意+指标，写入统一表，清除缓存 |
| RetrySyncTask | 每 3 分钟 | 重试失败的同步任务（最多3次，指数退避） |

---

## 测试

```bash
cd service && ./vendor/bin/phpunit
# 154 测试 / 385 断言
```

**覆盖范围**: 14 个中间件 · 6 插件业务层 (账户/平台/任务/租户/存储) · API 集成测试 (26 路由) · UI E2E (9 页面)

**测试报告**: 覆盖 PHP 单元 / API 自动化 / UI E2E / Go N/A / Rust N/A + Bug 汇总）

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

## 开源不易，欢迎支持

| 微信 | 支付宝 |
|:---:|:---:|
| ![微信](assets/weixinpay.png "微信") | ![支付宝](assets/alipay.png "支付宝") |

## 项目授权 / Project License

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

- 本项目仅供个人学习、研究使用。
- 未经作者书面授权，禁止任何形式的商业使用（包括但不限于商业部署、付费服务、二次销售、SaaS 运营及以本项目为基础提供商业服务）。
- 商业授权请联系 **erik@erik.xyz**。
- 未经授权的商业使用，作者将在全球范围内依法追究法律责任，并保留终身追责的权利。
- 第三方组件仍遵循其各自原有的许可证；视觉资产适用仓库内的 ASSETS-LICENSE.md（如适用）。

**English:** This project is provided for personal study and research only. Commercial use of any kind requires prior written authorization from the author (erik@erik.xyz). Unauthorized commercial use will be pursued for legal liability worldwide, and the author reserves the right to pursue such liability for life. Third-party components remain under their original licenses; visual assets are governed by ASSETS-LICENSE.md where applicable.
