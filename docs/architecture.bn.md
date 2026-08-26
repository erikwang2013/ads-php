# আর্কিটেকচার ডিজাইন ডকুমেন্ট

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. সিস্টেম ওভারভিউ

মাল্টি-প্ল্যাটফর্ম বিজ্ঞাপন ব্যবস্থাপনা সিস্টেম, **29টি বিজ্ঞাপন প্ল্যাটফর্ম**-এর সাথে সংযুক্ত, ডেলিভারি ম্যানেজমেন্ট, ক্রস-প্ল্যাটফর্ম রিপোর্ট, অ্যালার্ট মনিটরিং, অটো বিডিং, অডিয়েন্স টার্গেটিং কভার করে। SaaS মাল্টি-টেন্যান্সি, অ্যাজেন্সি অপারেশন, সেলফ-ইউজ — তিনটি মোড সাপোর্ট করে।

---

## 2. ডিপ্লয়মেন্ট আর্কিটেকচার

```
                         ┌──────────────────────────┐
                         │  客户端                   │
                         │  Vue Admin / Flutter      │
                         │  HarmonyOS / Browser      │
                         └──────────┬───────────────┘
                                    │ HTTP + JWT
                                    v
                         ┌──────────────────────────┐
                         │   Nginx :80               │
                         │   /          → admin:8789 │
                         │   /api       → service:8788│
                         └──────┬──────────┬────────┘
                                │          │
                   ┌────────────┘          └────────────┐
                   v                                    v
         ┌─────────────────┐                ┌─────────────────┐
         │  Admin :8789     │  ServiceProxy  │  Service :8788  │
         │  webman-admin v2 │───────────────→│  webman v2      │
         │  Vue 3 SPA       │   cURL HTTP    │  7 插件         │
         └────────┬────────┘                └────────┬────────┘
                  │                                   │
                  └──────────────┬────────────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              v                  v                  v
        ┌──────────┐      ┌──────────┐      ┌───────────┐
        │ MySQL 8.0│      │ Redis 7  │      │    ES     │
        │ 18 张表  │      │ 缓存/队列│      │ 搜索索引  │
        └──────────┘      └──────────┘      └───────────┘
```

---

## 3. রিকোয়েস্ট প্রসেসিং পাইপলাইন

### 3.1 Service এন্ড (15 লেয়ার মিডলওয়্যার)

```
Request
  → CorsMiddleware            (CORS 白名单、OPTIONS 预检)
  → OriginGuardMiddleware     (Origin/Referer 校验 + 拦截 TRACE/DEBUG/CONNECT)
  → SecurityHeadersMiddleware (CSP/X-Frame-Options/X-Content-Type-Options/HSTS)
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body 10MiB/Content-Type白名单)
  → ClientPlatformMiddleware  (X-Client-Platform 8端来源识别)
  → ReplayGuardMiddleware     (Nonce+Timestamp 防重放, 非浏览器端强校验)
  → VersionMiddleware         (X-API-Version 版本路由)
  → RateLimitMiddleware       (Redis 滑动窗口 60次/60s)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟锁定)
  → SessionLimitMiddleware    (并发会话限制 最大3个活跃Token)
  → SqlGuardMiddleware        (SQL 注入模式检测)
  → ValidationMiddleware      (输入 trim + strip_tags)
  → ResponseTimeMiddleware    (X-Response-Time 头 + 慢请求日志)
  → EncryptionMiddleware      (X-Encrypted 请求解密/响应加密)
  → AuthMiddleware            (JWT Bearer Token + IP/UA 绑定)
  → Controller
```

### 3.2 Admin এন্ড (6 লেয়ার মিডলওয়্যার)

```
Request
  → AttackGuardMiddleware     (XSS/路径遍历/Header注入/Body限制/Content-Type)
  → LoginThrottleMiddleware   (登录节流 5次失败→15分钟)
  → ClientPlatformMiddleware  (X-Client-Platform 来源识别)
  → CsrfMiddleware            (CSRF Token 验证)
  → VersionMiddleware         (API 版本)
  → AuthCheck                 (Session + JWT 双通道)
  → Controller
```

---

## 4. ডিরেক্টরি স্ট্রাকচার

```
ads-php/
├── service/                               # 业务 API 服务 :8788
│   ├── config/                            # 全局配置
│   │   ├── app.php, database.php, redis.php
│   │   ├── log.php                        # Monolog (JSON/Line 双模式)
│   │   ├── middleware.php                 # 11 层全局中间件
│   │   ├── exception.php                  # API 异常处理器
│   │   └── scout.php                      # ES 配置
│   ├── support/                           # 共享工具类 (erik\support)
│   │   ├── ApiResponse.php                # 统一 JSON 响应
│   │   ├── ControllerTrait.php            # 控制器公共 trait
│   │   ├── JwtService.php                 # JWT 包装 (erikwang2013/jwt-webman)
│   │   ├── CacheService.php               # Redis 缓存
│   │   ├── HashidsService.php             # ID 加解密
│   │   ├── SnowflakeTrait.php             # Snowflake ID 生成
│   │   └── ExceptionHandler.php           # JSON 异常渲染
│   ├── plugin/
│   │   ├── ads-api/                       # REST API 层
│   │   │   ├── controller/v1/             # 14 个控制器
│   │   │   ├── middleware/                # 7 个中间件
│   │   │   ├── config/route.php           # 45+ 路由
│   │   │   └── route_helpers.php          # versioned() 版本路由
│   │   ├── ads-platform/                  # 平台适配器核心
│   │   │   ├── adapter/                   # 29 个平台适配器
│   │   │   ├── src/                       # AdapterRegistry, CampaignData
│   │   │   ├── model/                     # Campaign, BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/                   # BidEngine
│   │   │   └── migration/                # SQL DDL + 性能索引
│   │   ├── ads-account/                   # OAuth 账户 + 平台账户
│   │   ├── ads-task/                      # 5 个 cron 任务
│   │   ├── ads-alert/                     # 告警引擎 + 通知
│   │   ├── ads-report/                    # 报表引擎 (CSV/Excel/PDF)
│   │   └── ads-tenant/                    # 多租户
│   ├── tests/                             # PHPUnit
│   │   ├── Unit/Middleware/               # 中间件测试
│   │   ├── Unit/Task/                     # 任务测试 (规划)
│   │   └── Integration/                   # 控制器集成测试
│   └── start.php                          # 入口
├── admin/                                 # 管理后台 :8789
│   ├── app/
│   │   ├── controller/                    # Auth, AdminUser, AuditLog
│   │   ├── middleware/                    # AttackGuard, LoginThrottle, ClientPlatform, Csrf, Version, AuthCheck
│   │   ├── service/                       # AuditService, ServiceProxy
│   │   └── support/                       # HashidsService
│   ├── public/web/                        # Vue 3 + TS SPA
│   │   └── src/
│   │       ├── views/                     # 14 页面 (dashboard/campaign/adgroup/creative/report/alert/notification/bid/system)
│   │       ├── api/                       # 9 个 API 客户端
│   │       ├── stores/                    # 4 个 Pinia Store
│   │       └── components/                # ListPageLayout 等共享组件
│   └── config/                            # Admin 配置
├── apps/
│   ├── flutter/                           # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/                  # 12 功能页面 + Shell 布局
│   │       ├── config/menu_config.dart    # 两级菜单 + 面包屑
│   │       ├── router.dart                # GoRouter + ShellRoute + 路由守卫
│   │       ├── stores/auth_provider.dart  # Riverpod Auth
│   │       └── shared/api/api_client.dart # Dio + JWT + 平台检测
│   └── harmonyos/                         # HarmonyOS (API Client 就绪)
├── docker/                                # Nginx 配置 + Dockerfiles
├── .github/workflows/                     # CI (语法→测试→TS→Docker) + CD (构建推送)
└── docs/                                  # 设计文档
```

---

## 5. ডেটা মডেল

### 5.1 টেবিল ক্যাটাগরি

| ক্যাটাগরি | টেবিল নাম | প্রাইমারি কী | ব্যবহার |
|------|------|------|------|
| বেসিক | `erik_tenants` | BIGINT Snowflake | মাল্টি-টেন্যান্সি |
| অ্যাকাউন্ট | `erik_platform_accounts`, `erik_auth_tokens` | BIGINT Snowflake | OAuth প্ল্যাটফর্ম অ্যাকাউন্ট |
| ডেলিভারি হায়ারার্কি | `erik_campaigns`, `erik_ad_groups`, `erik_creatives` | BIGINT Snowflake | বিজ্ঞাপন ডেলিভারি |
| রিপোর্ট | `erik_report_metrics`, `erik_report_extras` | BIGINT Snowflake | ইউনিফাইড মেট্রিক |
| অ্যালার্ট | `erik_alert_rules`, `erik_alert_logs` | BIGINT Snowflake | মনিটরিং অ্যালার্ট |
| বিডিং | `erik_bid_rules`, `erik_bid_logs` | BIGINT Snowflake | অটো বিডিং |
| টার্গেটিং | `erik_targeting_templates` | BIGINT Snowflake | অডিয়েন্স টেমপ্লেট |
| অ্যাসেট | `erik_assets` | BIGINT Snowflake | ক্রিয়েটিভ অ্যাসেট লাইব্রেরি |
| নোটিফিকেশন | `erik_notifications` | BIGINT Snowflake | সাইট-ইন নোটিফিকেশন |
| অ্যাট্রিবিউশন | `erik_conversions`, `erik_attribution_results` | BIGINT Snowflake | কনভার্সন ট্র্যাকিং + অ্যাট্রিবিউশন |
| সিস্টেম | `erik_sync_errors` | BIGINT Snowflake | সিঙ্ক এরর |
| ম্যানেজমেন্ট | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + অডিট |

### 5.2 নেমিং কনভেনশন

- টেবিল প্রিফিক্স: `erik_`
- প্রাইমারি কী: `BIGINT UNSIGNED PRIMARY KEY` (নো অটো-ইনক্রিমেন্ট, Snowflake ID)
- ইঞ্জিন: InnoDB, ক্যারেক্টার সেট: utf8mb4
- টাইমস্ট্যাম্প: `created_at`, `updated_at` (DATETIME)

---

## 6. সিকিউরিটি আর্কিটেকচার

### 6.1 প্রোটেকশন লেয়ার

| স্তর | মেকানিজম | কভারেজ |
|----|------|----------|
| ট্রান্সমিশন | Nginx (SSL টার্মিনেশন) | ফুল |
| নেটওয়ার্ক | CORS হোয়াইটলিস্ট + Origin ভ্যালিডেশন + HSTS | Service |
| ইনপুট | AttackGuard (XSS 11 প্যাটার্ন/পাথ ট্রাভার্সাল 7 প্যাটার্ন/Header ইনজেকশন) | Service + Admin |
| ইনজেকশন | SQLGuard (SQL ইনজেকশন প্যাটার্ন ডিটেকশন) | Service |
| ক্লিনিং | ValidationMiddleware (strip_tags) | Service |
| অথেনটিকেশন | JWT Bearer + bcrypt + IP/UA বাইন্ডিং + refresh রোটেশন | Service |
| অথেনটিকেশন | Session + JWT ডুয়াল চ্যানেল + CSRF Token | Admin |
| অথরাইজেশন | RBAC (রোল + পারমিশন JSON) | Admin |
| থ্রটলিং | RateLimit (স্লাইডিং উইন্ডো) + LoginThrottle (5 বার→15 মিনিট) | Service + Admin |
| সেশন | SessionLimit (সর্বোচ্চ 3টি অ্যাক্টিভ Token) + ব্ল্যাকলিস্ট | Service |
| এনক্রিপশন | EncryptionMiddleware (ট্রান্সমিশন) + Encryptable (স্টোরেজ) | Service |
| রিপ্লে | ReplayGuard (Nonce+Timestamp ±5min, নন-ব্রাউজার এন্ড) | Service + ক্লায়েন্ট |
| অডিট | অপারেশন ট্রেইল (IP/UA/প্ল্যাটফর্ম) | Admin |
| ডিসেনসিটাইজেশন | লগ সেনসিটিভ ফিল্ড মাস্কিং (password/token/secret → ***) | Service |

### 6.2 ক্লায়েন্ট প্ল্যাটফর্ম আইডেন্টিফিকেশন

`X-Client-Platform` header-এর মাধ্যমে:

| মান | উৎস |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | HarmonyOS App |

---

## 7. API ভার্সন রাউটিং মেকানিজম

ভার্সন নম্বর URL পাথে থাকে না। ভার্সন `X-API-Version` header দিয়ে পাস হয়, `VersionMiddleware` পড়ে `$request->apiVersion` সেট করে। `versioned()` হেল্পার ফাংশন রানটাইমে কন্ট্রোলার ক্লাসের ভার্সন অংশকে রিকোয়েস্ট ভার্সন দিয়ে প্রতিস্থাপন করে।

```
请求: GET /api/campaigns
Header: X-API-Version: v1

VersionMiddleware → $request->apiVersion = 'v1'
versioned(CampaignController::class, 'index')
  → controller\v1\CampaignController::index()
```

---

## 8. ক্রন টাস্ক শিডিউলিং

| টাস্ক | Cron | ফাংশন |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | এক্সপায়ারড OAuth Token রিফ্রেশ |
| DataSyncTask | `*/10 * * * *` | Campaigns→AdGroups→Creatives→Reports→ক্যাশ ক্লিয়ার সিঙ্ক |
| AlertCheckTask | `*/5 * * * *` | অ্যালার্ট রুল মূল্যায়ন, নোটিফিকেশন ট্রিগার |
| BidCheckTask | `*/10 * * * *` | বিডিং রুল মূল্যায়ন, বাজেট অ্যাডজাস্টমেন্ট/স্টার্ট-স্টপ এক্সিকিউট |
| RetrySyncTask | `*/3 * * * *` | ব্যর্থ সিঙ্ক রিট্রাই (সর্বোচ্চ 3 বার, এক্সপোনেনশিয়াল ব্যাকঅফ) |

---

## 9. Erik Stack প্যাকেজ ইন্টিগ্রেশন

| প্যাকেজ | ইন্টিগ্রেশন অবস্থান | ব্যবহার |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10টি Model (SnowflakeTrait) + admin helpers.php | প্রাইমারি কী জেনারেশন |
| `erikwang2013/hashids` | ApiResponse + 2টি Admin Controller | ID এনকোডিং |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | অথেনটিকেশন টোকেন |
| `erikwang2013/encryption` | EncryptionMiddleware | ট্রান্সমিশন এনক্রিপশন/ডিক্রিপশন |
| `erikwang2013/encryptable` | PlatformAccount + AuthToken Model | DB ফিল্ড এনক্রিপশন |
| `erikwang2013/webman-scout` | Campaign Model (Searchable trait) | ES সার্চ |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | দেশের ফ্ল্যাগ |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | স্লাইডার ক্যাপচা |
| `hg/apidoc` | অ্যানোটেশন → ডকুমেন্ট জেনারেশন (Web UI: :8788/apidoc) | API ডকুমেন্টেশন |

---

## 10. হাই কনকারেন্সি আর্কিটেকচার

### 10.1 ডেটাবেস লেয়ার

| অপটিমাইজেশন | বিবরণ |
|------|------|
| রিড/রাইট সেপারেশন | প্রাইমারি `shared` (লেখা) + রিড-অনলি রেপ্লিকা `read_replica` (রিপোর্ট/অ্যানালিটিক্স কোয়েরি) |
| পার্সিস্টেন্ট কানেকশন | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent` ঘন ঘন TCP হ্যান্ডশেক এড়াতে |
| কানেকশন প্রিহিট | worker স্টার্টে `SELECT 1` এক্সিকিউট, কানেকশন পুল প্রস্তুত হওয়ার পর রিকোয়েস্ট নেয় |

### 10.2 ক্যাশ লেয়ার

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 মেসেজ কিউ

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4টি চ্যানেল: `sync` | `report` | `export` | `notification`

### 10.4 হরাইজন্টাল স্কেলিং

```
                    ┌──────────────────┐
                    │   Nginx :80      │
                    │ upstream service │
                    └────────┬─────────┘
                             │
              ┌──────────────┼──────────────┐
              v              v              v
        ┌──────────┐  ┌──────────┐  ┌──────────┐
        │ php:8788 │  │ php2:8788│  │ php3:8788│
        │ worker 1 │  │ worker 2 │  │ worker 3 │
        └──────────┘  └──────────┘  └──────────┘
              │              │              │
              └──────────────┼──────────────┘
                             v
                    ┌──────────────────┐
                    │   MySQL + Redis  │
                    └──────────────────┘
```

- **keepalive**: 32 লং কানেকশন রিইউজ
- **failover**: `proxy_next_upstream` অটো ফেইলওভার, 2 বার রিট্রাই
- **রেট লিমিট**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 স্ট্যাটিক রিসোর্স CDN

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — প্রি-কমপ্রেসড js/css ফাইল
- প্রোডাকশনে CDN ইন্টিগ্রেশন (CloudFront/Aliyun CDN)

---

## 11. ডিপ্লয়মেন্ট ও CI/CD

### Docker সার্ভিস

| সার্ভিস | পোর্ট | ইমেজ |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP Syntax → PHPUnit → TypeScript → Docker Build
- **CD** (`.github/workflows/deploy.yml`): Docker Buildx → GHCR Push (service/admin/admin-php) → Deploy
