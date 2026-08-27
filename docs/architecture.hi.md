# आर्किटेक्चर डिज़ाइन दस्तावेज़

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. सिस्टम अवलोकन

मल्टी-प्लेटफ़ॉर्म विज्ञापन प्रबंधन प्रणाली, **29 विज्ञापन प्लेटफ़ॉर्म** से जुड़ती है, जिसमें डिलीवरी प्रबंधन, क्रॉस-प्लेटफ़ॉर्म रिपोर्ट, अलर्ट मॉनिटरिंग, स्वचालित बिडिंग और ऑडियंस टार्गेटिंग शामिल हैं। SaaS मल्टी-टेनेंट, एजेंसी ऑपरेशन और सेल्फ-यूज़ तीन मोड का समर्थन करती है।

---

## 2. डिप्लॉयमेंट आर्किटेक्चर

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

## 3. अनुरोध प्रोसेसिंग पाइपलाइन

### 3.1 Service साइड (15 मिडलवेयर परतें)

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

### 3.2 Admin साइड (6 मिडलवेयर परतें)

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

## 4. निर्देशिका संरचना

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

## 5. डेटा मॉडल

### 5.1 टेबल श्रेणियाँ

| श्रेणी | टेबल नाम | प्राइमरी की | उपयोग |
|------|------|------|------|
| बेसिक | `ads_tenants` | BIGINT Snowflake | मल्टी-टेनेंट |
| खाता | `ads_platform_accounts`, `ads_auth_tokens` | BIGINT Snowflake | OAuth प्लेटफ़ॉर्म खाते |
| डिलीवरी पदानुक्रम | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | BIGINT Snowflake | विज्ञापन डिलीवरी |
| रिपोर्ट | `ads_report_metrics`, `ads_report_extras` | BIGINT Snowflake | एकीकृत मेट्रिक्स |
| अलर्ट | `ads_alert_rules`, `ads_alert_logs` | BIGINT Snowflake | मॉनिटरिंग अलर्ट |
| बिडिंग | `ads_bid_rules`, `ads_bid_logs` | BIGINT Snowflake | स्वचालित बिडिंग |
| टार्गेटिंग | `ads_targeting_templates` | BIGINT Snowflake | ऑडियंस टेम्पलेट |
| एसेट | `ads_assets` | BIGINT Snowflake | क्रिएटिव एसेट लाइब्रेरी |
| नोटिफिकेशन | `ads_notifications` | BIGINT Snowflake | साइट-इन नोटिफिकेशन |
| एट्रिब्यूशन | `ads_conversions`, `ads_attribution_results` | BIGINT Snowflake | कन्वर्ज़न ट्रैकिंग + एट्रिब्यूशन |
| सिस्टम | `ads_sync_errors` | BIGINT Snowflake | सिंक त्रुटियाँ |
| प्रशासन | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + ऑडिट |

### 5.2 नामकरण मानक

- टेबल प्रीफ़िक्स: `ads_`
- प्राइमरी की: `BIGINT UNSIGNED PRIMARY KEY` (बिना auto-increment, Snowflake ID)
- इंजन: InnoDB, कैरेक्टर सेट: utf8mb4
- टाइमस्टैम्प: `created_at`, `updated_at` (DATETIME)

---

## 6. सुरक्षा आर्किटेक्चर

### 6.1 सुरक्षा परतें

| परत | तंत्र | कवरेज |
|----|------|----------|
| ट्रांसमिशन | Nginx (SSL समाप्ति) | पूर्ण |
| नेटवर्क | CORS व्हाइटलिस्ट + Origin सत्यापन + HSTS | Service |
| इनपुट | AttackGuard (XSS 11 पैटर्न/पाथ ट्रैवर्सल 7 पैटर्न/Header इंजेक्शन) | Service + Admin |
| इंजेक्शन | SQLGuard (SQL इंजेक्शन पैटर्न डिटेक्शन) | Service |
| सफ़ाई | ValidationMiddleware (strip_tags) | Service |
| प्रमाणीकरण | JWT Bearer + bcrypt + IP/UA बाइंडिंग + refresh रोटेशन | Service |
| प्रमाणीकरण | Session + JWT दोहरा चैनल + CSRF Token | Admin |
| प्राधिकरण | RBAC (भूमिका + अनुमति JSON) | Admin |
| थ्रॉटलिंग | RateLimit (स्लाइडिंग विंडो) + LoginThrottle (5 बार→15 मिनट) | Service + Admin |
| सत्र | SessionLimit (अधिकतम 3 सक्रिय Token) + ब्लैकलिस्ट | Service |
| एन्क्रिप्शन | EncryptionMiddleware (ट्रांसमिशन) + Encryptable (स्टोरेज) | Service |
| रिप्ले | ReplayGuard (Nonce+Timestamp ±5min, गैर-ब्राउज़र साइड) | Service + क्लाइंट |
| लचीलापन | CircuitBreaker (प्रति-प्लेटफ़ॉर्म: 5 विफलताएँ → OPEN → 30s हाफ-ओपन) + GuardedAdapter (डिग्रेडेशन fast-fail) | Service |
| ऑडिट | ऑपरेशन ट्रेस (IP/UA/प्लेटफ़ॉर्म) | Admin |
| डी-सेंसिटाइज़ेशन | लॉग संवेदनशील फ़ील्ड मास्किंग (password/token/secret → ***) | Service |

### 6.2 क्लाइंट प्लेटफ़ॉर्म पहचान

`X-Client-Platform` header के माध्यम से:

| मान | स्रोत |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | HarmonyOS App |

---

## 7. API वर्शन रूटिंग तंत्र

वर्शन नंबर URL पाथ में नहीं आता। वर्शन `X-API-Version` header के माध्यम से भेजा जाता है, `VersionMiddleware` इसे पढ़कर `$request->apiVersion` सेट करता है। `versioned()` सहायक फ़ंक्शन रनटाइम पर कंट्रोलर क्लास के वर्शन सेगमेंट को अनुरोध वर्शन से बदल देता है।

```
请求: GET /api/campaigns
Header: X-API-Version: v1

VersionMiddleware → $request->apiVersion = 'v1'
versioned(CampaignController::class, 'index')
  → controller\v1\CampaignController::index()
```

---

## 8. निर्धारित कार्य शेड्यूलिंग

| कार्य | Cron | फ़ंक्शन |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | समाप्त OAuth Token रिफ़्रेश करें |
| DataSyncTask | `*/10 * * * *` | Campaigns→AdGroups→Creatives→Reports सिंक करें→कैश साफ़ करें |
| AlertCheckTask | `*/5 * * * *` | अलर्ट नियमों का मूल्यांकन करें, नोटिफिकेशन ट्रिगर करें |
| BidCheckTask | `*/10 * * * *` | बिडिंग नियमों का मूल्यांकन करें, बजट समायोजन/स्टार्ट-स्टॉप करें |
| RetrySyncTask | `*/3 * * * *` | असफल सिंक पुनः प्रयास करें (अधिकतम 3 बार, एक्सपोनेंशियल बैकऑफ़) |

---

## 9. Erik Stack पैकेज एकीकरण

| पैकेज | एकीकरण स्थान | उपयोग |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10 Model (SnowflakeTrait) + admin helpers.php | प्राइमरी की जनरेशन |
| `erikwang2013/hashids` | ApiResponse + 2 Admin Controller | ID एन्कोडिंग |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | प्रमाणीकरण टोकन |
| `erikwang2013/encryption` | EncryptionMiddleware | ट्रांसमिशन एन्क्रिप्शन/डिक्रिप्शन |
| `erikwang2013/encryptable` | PlatformAccount + AuthToken Model | DB फ़ील्ड एन्क्रिप्शन |
| `erikwang2013/webman-scout` | Campaign Model (Searchable trait) | ES सर्च |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | देश ध्वज |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | स्लाइडर कैप्चा |
| `hg/apidoc` | एनोटेशन → दस्तावेज़ जनरेशन (Web UI: :8788/apidoc) | API दस्तावेज़ |

---

## 10. उच्च-समवर्ती आर्किटेक्चर

### 10.1 डेटाबेस परत

| अनुकूलन | विवरण |
|------|------|
| रीड/राइट सेपरेशन | मास्टर `shared` (लिखना) + रीड-ओनली रेप्लिका `read_replica` (रिपोर्ट/विश्लेषण क्वेरी) |
| पर्सिस्टेंट कनेक्शन | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent` बार-बार TCP हैंडशेक से बचने के लिए |
| कनेक्शन प्रीवार्मिंग | worker स्टार्टअप पर `SELECT 1` निष्पादित करें, कनेक्शन पूल तैयार होने के बाद ही अनुरोध स्वीकारें |

### 10.2 कैश परत

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 मैसेज क्यू

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4 चैनल: `sync` | `report` | `export` | `notification`

### 10.4 क्षैतिज स्केलिंग

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

- **keepalive**: 32 लंबे कनेक्शन पुन: उपयोग
- **failover**: `proxy_next_upstream` स्वचालित फ़ेलओवर, 2 रीट्राई
- **रेट-लिमिट**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 स्टैटिक एसेट CDN

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — प्री-कंप्रेस्ड js/css फ़ाइलें
- प्रोडक्शन में CDN (CloudFront/Aliyun CDN) से जोड़ें

---

## 11. डिप्लॉयमेंट और CI/CD

### Docker सेवाएँ

| सेवा | पोर्ट | इमेज |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP Syntax → PHPUnit → TypeScript → Docker Build
- **CD** (`.github/workflows/deploy.yml`): Docker Buildx → GHCR Push (service/admin/admin-php) → Deploy
