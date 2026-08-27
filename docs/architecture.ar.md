# وثيقة تصميم البنية

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. نظرة عامة على النظام

نظام إدارة إعلانات متعدد المنصات، يتكامل مع **29 منصة إعلانية**، ويغطي إدارة النشر والتقارير عبر المنصات ومراقبة التنبيهات والمزايدة التلقائية واستهداف الجمهور. يدعم ثلاث أنماط: SaaS متعدد المستأجرين، ووكالة التشغيل، والاستخدام الذاتي.

---

## 2. بنية النشر

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

## 3. خط أنابيب معالجة الطلبات

### 3.1 طرف Service (15 طبقة وسيطة)

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

### 3.2 طرف Admin (6 طبقات وسيطة)

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

## 4. هيكل الدلائل

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

## 5. نموذج البيانات

### 5.1 تصنيف الجداول

| التصنيف | اسم الجدول | المفتاح الرئيسي | الاستخدام |
|------|------|------|------|
| أساسيات | `ads_tenants` | BIGINT Snowflake | متعدد المستأجرين |
| الحسابات | `ads_platform_accounts`, `ads_auth_tokens` | BIGINT Snowflake | حسابات منصات OAuth |
| مستويات النشر | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | BIGINT Snowflake | نشر الإعلانات |
| التقارير | `ads_report_metrics`, `ads_report_extras` | BIGINT Snowflake | مقاييس موحدة |
| التنبيهات | `ads_alert_rules`, `ads_alert_logs` | BIGINT Snowflake | مراقبة التنبيهات |
| المزايدة | `ads_bid_rules`, `ads_bid_logs` | BIGINT Snowflake | المزايدة التلقائية |
| الاستهداف | `ads_targeting_templates` | BIGINT Snowflake | قوالب الجمهور |
| المواد | `ads_assets` | BIGINT Snowflake | مكتبة المواد الإبداعية |
| الإشعارات | `ads_notifications` | BIGINT Snowflake | إشعارات داخلية |
| الإسناد | `ads_conversions`, `ads_attribution_results` | BIGINT Snowflake | تتبع التحويلات + الإسناد |
| النظام | `ads_sync_errors` | BIGINT Snowflake | أخطاء المزامنة |
| الإدارة | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + التدقيق |

### 5.2 قواعد التسمية

- بادئة الجداول: `ads_`
- المفتاح الرئيسي: `BIGINT UNSIGNED PRIMARY KEY` (بدون زيادة تلقائية، معرفات Snowflake)
- المحرك: InnoDB، الترميز: utf8mb4
- الطوابع الزمنية: `created_at`, `updated_at` (DATETIME)

---

## 6. البنية الأمنية

### 6.1 طبقات الحماية

| الطبقة | الآلية | نطاق التغطية |
|----|------|----------|
| النقل | Nginx (إنهاء SSL) | بالكامل |
| الشبكة | قائمة CORS المسموحة + التحقق من Origin + HSTS | Service |
| الإدخال | AttackGuard (XSS 11 نمطًا/اجتياز المسار 7 أنماط/حقن الترويسات) | Service + Admin |
| الحقن | SQLGuard (كشف أنماط حقن SQL) | Service |
| التنظيف | ValidationMiddleware (strip_tags) | Service |
| المصادقة | JWT Bearer + bcrypt + ربط IP/UA + تدوير refresh | Service |
| المصادقة | قناتا Session + JWT + CSRF Token | Admin |
| التفويض | RBAC (الأدوار + صلاحيات JSON) | Admin |
| التقييد | RateLimit (نافذة منزلقة) + LoginThrottle (5 مرات←15 دقيقة) | Service + Admin |
| الجلسات | SessionLimit (بحد أقصى 3 رموز نشطة) + قائمة الحظر | Service |
| التشفير | EncryptionMiddleware (النقل) + Encryptable (التخزين) | Service |
| إعادة التشغيل | ReplayGuard (Nonce+Timestamp ±5د، لغير المتصفحات) | Service + العميل |
| المرونة | CircuitBreaker (لكل منصة: 5 إخفاقات → OPEN → 30 ثانية نصف مفتوح) + GuardedAdapter (فشل سريع عند التدهور) | Service |
| التدقيق | مسار العمليات (IP/UA/المنصة) | Admin |
| الإخفاء | إخفاء الحقول الحساسة في السجلات (password/token/secret → ***) | Service |

### 6.2 التعرف على منصة العميل

عبر ترويسة `X-Client-Platform`:

| القيمة | المصدر |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | HarmonyOS App |

---

## 7. آلية توجيه إصدارات API

لا يظهر رقم الإصدار في مسار URL. يُمرَّر الإصدار عبر ترويسة `X-API-Version`، ويقرؤه `VersionMiddleware` ويضبط `$request->apiVersion`. وتستبدل الدالة المساعدة `versioned()` مقطع الإصدار في فئة وحدة التحكم بإصدار الطلب وقت التشغيل.

```
请求: GET /api/campaigns
Header: X-API-Version: v1

VersionMiddleware → $request->apiVersion = 'v1'
versioned(CampaignController::class, 'index')
  → controller\v1\CampaignController::index()
```

---

## 8. جدولة المهام

| المهمة | Cron | الوظيفة |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | تحديث رموز OAuth منتهية الصلاحية |
| DataSyncTask | `*/10 * * * *` | مزامنة Campaigns←AdGroups←Creatives←Reports←مسح التخزين المؤقت |
| AlertCheckTask | `*/5 * * * *` | تقييم قواعد التنبيه وتشغيل الإشعارات |
| BidCheckTask | `*/10 * * * *` | تقييم قواعد المزايدة وتنفيذ تعديل الميزانية/التشغيل والإيقاف |
| RetrySyncTask | `*/3 * * * *` | إعادة محاولة المزامنات الفاشلة (بحد أقصى 3 مرات، تراجع أسي) |

---

## 9. تكامل حزم Erik Stack

| الحزمة | موضع التكامل | الاستخدام |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10 نماذج (SnowflakeTrait) + admin helpers.php | توليد المفاتيح الرئيسية |
| `erikwang2013/hashids` | ApiResponse + وحدتا تحكم Admin | ترميز ID |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | رموز المصادقة |
| `erikwang2013/encryption` | EncryptionMiddleware | تشفير وفك تشفير النقل |
| `erikwang2013/encryptable` | نموذجا PlatformAccount + AuthToken | تشفير حقول قاعدة البيانات |
| `erikwang2013/webman-scout` | نموذج Campaign (خاصية Searchable) | بحث ES |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | أعلام الدول |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | رمز تحقق منزلق |
| `hg/apidoc` | الشروح ← توليد الوثائق (Web UI: :8788/apidoc) | وثائق API |

---

## 10. بنية التزامن العالي

### 10.1 طبقة قاعدة البيانات

| التحسين | الوصف |
|------|------|
| فصل القراءة/الكتابة | قاعدة رئيسية `shared` (الكتابة) + نسخة قراءة فقط `read_replica` (استعلامات التقارير/التحليل) |
| اتصالات دائمة | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent` لتجنب مصافحة TCP المتكررة |
| تسخين الاتصالات | تنفيذ `SELECT 1` عند بدء worker، لاستقبال الطلبات بعد جاهزية تجمع الاتصالات |

### 10.2 طبقة التخزين المؤقت

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 قائمة انتظار الرسائل

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4 قنوات: `sync` | `report` | `export` | `notification`

### 10.4 التوسع الأفقي

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

- **keepalive**: إعادة استخدام 32 اتصالًا طويلًا
- **failover**: تجاوز الأعطال تلقائيًا عبر `proxy_next_upstream`، مع محاولتين لإعادة المحاولة
- **تحديد المعدل**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 CDN للموارد الثابتة

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — ضغط مسبق لملفات js/css
- ربط بيئة الإنتاج بـ CDN (CloudFront/Aliyun CDN)

---

## 11. النشر و CI/CD

### خدمات Docker

| الخدمة | المنفذ | الصورة |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP Syntax → PHPUnit → TypeScript → Docker Build
- **CD** (`.github/workflows/deploy.yml`): Docker Buildx → GHCR Push (service/admin/admin-php) → Deploy
