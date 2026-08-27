# Ads Platform — نظام إدارة الإعلانات متعدد المنصات

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## نظرة عامة

يتكامل مع **29 منصة إعلانية**، ويدير بشكل موحّد نشر الإعلانات وتقارير البيانات عبر المنصات، مع دعم مراقبة التنبيهات والمزايدة التلقائية والوصول متعدد الأطراف.

> البنية المعمارية → [docs/architecture.ar.md](docs/architecture.ar.md)  
> الوحدات الوظيفية → [docs/features.ar.md](docs/features.ar.md)  
> توثيق API → [docs/api.ar.md](docs/api.ar.md) | hg/apidoc: `http://127.0.0.1:8788/apidoc`  
> مقارنة الإصدارات → [docs/versions.ar.md](docs/versions.ar.md)（Lite مفتوح المصدر / Standard و Full: تواصل مع erik@erik.xyz）

### المنصات المدعومة

#### المحلية (16)
| المنصة | المحول | المصادقة |
|------|--------|------|
| 巨量引擎 (Juliang) | Juliang | OAuth2 Access-Token |
| 百度营销 (Baidu) | Baidu | OAuth2 + توقيع المغلف |
| 淘宝/阿里妈妈 (Taobao) | Taobao | OAuth2 + MD5 |
| 腾讯广告 (Tencent) | Tencent | OAuth2 + nonce |
| 快手磁力引擎 (Kuaishou) | Kuaishou | OAuth2 عبر معاملات URL |
| 小红书蒲公英 (Xiaohongshu) | Xiaohongshu | OAuth2 Bearer |
| 微博粉丝通 (Weibo) | Weibo | OAuth2 Bearer |
| B站花火 (Bilibili) | Bilibili | OAuth2 Bearer |
| 优酷广告 (Youku) | Youku | OAuth2 + MD5 |
| 美团广告 (Meituan) | Meituan | OAuth2 Bearer |
| 知乎广告 (Zhihu) | Zhihu | OAuth2 Bearer |
| 360推广 (Qihoo360) | Qihoo360 | API Key + Sign |
| 搜狗推广 (Sogou) | Sogou | API Key + Sign |
| 友盟 (Umeng) | Umeng | API Key + MD5 |
| 京东京准通 (Jingdong) | Jingdong | OAuth2 + MD5 |
| 拼多多广告 (Pinduoduo) | Pinduoduo | OAuth2 + Sign مخصص |

#### الدولية (13)
| المنصة | المحول | المصادقة |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 عبر معاملات URL |
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

## حزمة التقنيات

| الطبقة | التقنية | الوصف |
|----|------|------|
| الخادم | webman v2 + PHP 8.2+ | 7 إضافات، 65+ نقطة نهاية API |
| قاعدة البيانات | MySQL 8.0 | 28 جدولًا، بادئة ads_، مفاتيح رئيسية Snowflake BIGINT |
| التخزين المؤقت | Redis 7 | تخزين مؤقت ثلاثي المستويات (L1 ذاكرة/L2 APCu/L3 Redis)、عدادات تحديد المعدل وPub/Sub وقائمة انتظار الرسائل |
| البحث | Elasticsearch | مزامنة فهرسة تلقائية عبر webman-scout (مُهيأ) |
| لوحة الإدارة | webman-admin v2 + Vue 3 + TypeScript + Element Plus | خلفية PHP (المنفذ 8789)، SPA يتصل مباشرة بـ API الأعمال (المنفذ 8788)، 19 صفحة، تصوير ECharts |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | متجاوب PC/Mobile، تخطيط Desktop Shell، 12 صفحة |
| HarmonyOS | ArkTS + ArkUI | 6 صفحات منفذة، عميل HTTP جاهز |
| النشر | Docker + Nginx + GHCR | تشغيل بنقرة واحدة عبر Docker Compose، بناء ودفع تلقائي عبر GitHub Actions |

## مخطط البنية

![مخطط بنية النظام](docs/diagrams/svg/architecture.ar.svg)

### مخطط تدفق الطلبات

![مخطط تدفق الطلبات](docs/diagrams/svg/request-flow.ar.svg)

### مخطط الوحدات الوظيفية

![مخطط الوحدات الوظيفية](docs/diagrams/svg/functional-modules.ar.svg)

### مخطط دورة حياة البيانات

![مخطط دورة حياة البيانات](docs/diagrams/svg/data-lifecycle.ar.svg)

> النسخة الكاملة تشمل جميع التعليقات التفصيلية، وخط أنابيب طرف Admin، ومخطط جانت للمهام المجدولة، وآلة حالات التخزين المؤقت → [docs/diagrams/](docs/diagrams/) |

> شرح البنية التفصيلي والبنية الأمنية وتصميم التزامن العالي في [وثيقة تصميم البنية](docs/architecture.ar.md) | المواصفات التصميمية التاريخية في [design.ar.md](docs/superpowers/specs/design.ar.md)

## شرح البنية

- **`service/`** — خدمة API الأعمال للمستخدمين webman v2، تستمع على المنفذ **8788**. تعالج منطق الأعمال: ربط منصات الإعلانات، تفويض OAuth، مزامنة البيانات، محرك التقارير، مراقبة التنبيهات وغيرها.
- **`admin/`** — لوحة إدارة مستقلة webman-admin v2، تستمع على المنفذ **8789**. تتضمن خلفية PHP (المصادقة والتفويض، إدارة المستخدمين، إعدادات النظام) وواجهة Vue 3 SPA.
- **الاتصال بين لوحة الإدارة وخدمة الأعمال** — تتصل Vue SPA مباشرة بخدمة API عبر axios (baseURL `/api`)؛ وتُقدَّم مسارات admin الحصرية (`/api/admin/*`) من خلفية PHP الخاصة بـ admin (8789)، بينما يوجّه Nginx الطلبات وفقًا للبادئة (يوزّع Nginx الطلبات حسب المسار).
- **وضع التطوير** — يوكّل Vite dev server (المنفذ 5173) طلبات `/api` إلى service:8788؛ وتوفر خلفية admin PHP مصادقة الجلسة وخدمة SPA الثابتة على 8789.
- **وضع الإنتاج** — يوجّه Nginx مسار `/` إلى admin:8789 (SPA لوحة الإدارة)، ومسار `/api/` إلى service:8788 (API الأعمال).

## تكامل Erik Stack

| الحزمة | الاستخدام |
|----|------|
| `erikwang2013/snowflake-php` | توليد معرفات Snowflake الموزعة |
| `erikwang2013/hashids` | تشفير وفك تشفير معاملات ID في API |
| `erikwang2013/jwt-webman` | رموز مصادقة JWT |
| `erikwang2013/encryption` | تشفير وفك تشفير البيانات الحساسة في طبقة API |
| `erikwang2013/encryptable` | تشفير وفك تشفير تلقائي على مستوى حقول قاعدة البيانات |
| `erikwang2013/webman-scout` | مزامنة بيانات Elasticsearch |
| `erikwang2013/season` | أعلام الدول |
| `erikwang2013/poster-php` | رمز تحقق منزلق (حماية تسجيل الدخول) |
| `hg/apidoc` | توليد تلقائي لوثائق API (شروح + واجهة ويب) |

## التدويل

جميع الواجهات تدعم التبديل الثنائي **中文 (zh-CN)** / **English (en)**:

| الطرف | التقنية | طريقة التبديل |
|----|------|---------|
| Admin | vue-i18n v9 | قائمة لغة منسدلة في TopBar، مع حفظ دائم في localStorage |
| Service API | `erik\support\I18n` | ترويسة الطلب Accept-Language / معامل `?lang=` |
| Flutter | AppLocalizations + Delegate | كشف تلقائي للغة النظام |
| HarmonyOS | StringResources | التبديل عبر `setLang()` |

## الأمان

### طرف Service (14 طبقة عامة + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware (طبقة التوجيه)

### طرف Admin (10 طبقات عامة + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck (طبقة التوجيه)

### نظرة عامة على الحماية (22 بندًا)

| التصنيف | بند الحماية | الوصف |
|------|--------|------|
| فحص الإدخال | XSS (11 نمطًا) | script/iframe/event handler/javascript:/data: |
| | اجتياز المسار (7 أنماط) | ../ / null byte / /etc/passwd / .env / .git |
| | حقن الترويسات | كشف CRLF |
| | حد حجم الجسم | 10 MiB |
| | قائمة Content-Type المسموحة | JSON/Form/Multipart/Plain |
| | حقن SQL | كشف أنماط UNION/DROP/ALTER |
| المصادقة | ربط JWT Token | تحقق IP + تجزئة User-Agent |
| | تحديث Token + قائمة حظر | انتهاء صلاحية تلقائي للرموز القديمة |
| | تقييد تسجيل الدخول | 5 محاولات فاشلة → قفل 15 دقيقة (Redis) |
| | حد الجلسات المتزامنة | بحد أقصى 3 رموز نشطة لكل مستخدم |
| | رمز التحقق | رمز تحقق منزلق (صالح 5 دقائق، تسامح 5px) |
| التحقق من الطلبات | قائمة CORS المسموحة | قائمة نطاقات مسموحة لبيئة الإنتاج |
| | التحقق من Origin/Referer | التحقق من مصدر الطلبات العابرة للنطاقات |
| | CSRF Token | التحقق من رمز الجلسة في طرف Admin |
| | منع إعادة التشغيل | Nonce + Timestamp ±5د (لغير المتصفحات) |
| | تحديد معدل الواجهات | نافذة منزلقة 60 طلبًا/60ث |
| | حماية SSRF | قائمة redirect_uri المسموحة في OAuth |
| ترويسات الاستجابة | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | منع اختطاف النقر + إجبار HTTPS |
| | X-Content-Type-Options | nosniff |
| حماية البيانات | تشفير النقل | EncryptionMiddleware (X-Encrypted) |
| | تشفير التخزين | Encryptable (مستوى حقول قاعدة البيانات) |
| | إخفاء بيانات السجلات | password/token/secret → \*\*\* |

### مخطط البنية الأمنية

![مخطط البنية الأمنية](docs/diagrams/svg/security.ar.svg)

**الدفاع المتعمق**: الطبقة الخارجية (Nginx) ← حراس الدخول (5 طبقات وسطية) ← مصادقة الهوية (7 بنود) ← التحقق من الإدخال (4 بنود) ← التحكم في التكرار ← تشفير البيانات ← تتبع التدقيق

**المصادقة**: يستخدم الخادم وadmin جدول `admin_users` + تجزئة bcrypt بشكل موحّد، JWT 24 ساعة + تدوير refresh

**التدقيق**: تسجل جميع العمليات IP / User-Agent / Client-Platform / تفاصيل العملية

**التأكيد الثانوي**: تعتمد عمليات الحذف/إلغاء الربط/الدفعات نمط "كلمة تأكيد الإدخال" (`GlobalConfirm` + `useConfirmStore`)

---

## الميزات المتقدمة

| الوظيفة | الوصف | التقنية |
|------|------|------|
| مكتبة المواد | إدارة رفع الصور/الفيديو، معاينة المعرض، نسخ URL | AssetController + معرض Vue |
| تنبيه الميزانية | تتبع فوري لاستهلاك الميزانية اليومية، تنبيه ثلاثي المراحل (50/80/100%) | BudgetAlertService + Cron 15د |
| تقويم النشر | مخطط Gantt عبر المنصات، عرض شهري/أسبوعي، تلوين حسب المنصة | CalendarService + Vue Gantt |
| الإسناد عبر المنصات | إسناد بـ 5 نماذج (first/last/linear/time_decay/position_based)، رجوع 30 يومًا | AttributionEngine + ECharts |

---

## التزامن العالي

| التحسين | الحل | الملف |
|------|------|------|
| فصل القراءة/الكتابة لقاعدة البيانات | قاعدة رئيسية `shared` + نسخة قراءة فقط `read_replica`، توجيه SELECT تلقائيًا للنسخة | `config/database.php` |
| تجمع اتصالات قاعدة البيانات | اتصالات دائمة `PDO::ATTR_PERSISTENT` + تسخين بتهيئة المنطقة الزمنية | `config/database.php` |
| تجمع اتصالات Redis | اتصالات دائمة `persistent` + إعداد `readonly` لفصل القراءة/الكتابة | `config/redis.php` |
| تخزين مؤقت ثلاثي المستويات | ذاكرة العمليات L1 → ذاكرة مشتركة L2 APCu → L3 Redis | `support/CacheService.php` |
| قائمة انتظار رسائل غير متزامنة | Redis List بقنوات 4 (sync/report/export/notification) | `support/AsyncJobService.php` |
| تحديد معدل متدرج في Nginx | 30r/s + burst 20 + 20 اتصال متزامن + keepalive 32 | `docker/nginx/admin.conf` |
| توسع أفقي | upstream متعدد المثيلات + تجاوز الأعطال + sticky session | `docker/nginx/admin.conf` |
| تسريع CDN | موارد ثابتة `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## البدء السريع

### تثبيت ويب بنقرة واحدة (موصى به)

بعد تشغيل الخدمة، افتح `/install` في المتصفح للدخول إلى معالج التثبيت:

```bash
# 启动管理后台 (端口 8789)
cd admin && composer install && php start.php start

# 打开浏览器访问 http://localhost:8789/install
# 在安装向导中填写数据库信息、管理员账户，点击「开始安装」
```

سيقودك معالج التثبيت على صفحة الويب خلال:
1. **اتصال قاعدة البيانات** — أدخل مضيف MySQL والمنفذ واسم قاعدة البيانات واسم المستخدم وكلمة المرور، مع دعم اختبار الاتصال
2. **إعداد Redis** — أدخل معلومات اتصال Redis (اختياري)
3. **حساب المسؤول** — عيّن اسم مستخدم وكلمة مرور واسمًا ظاهرًا لتسجيل دخول لوحة الإدارة
4. **تثبيت بنقرة واحدة** — إنشاء قاعدة البيانات تلقائيًا، وتنفيذ `install.sql` لإنشاء 28 جدولًا وكتابة بيانات أولية، وتحديث كلمة مرور المسؤول

بعد اكتمال التثبيت، افتح `/` للدخول إلى لوحة الإدارة وسجّل الدخول باسم المستخدم وكلمة المرور اللذين عيّنتهما.

### Docker (موصى به لبيئة الإنتاج)

```bash
# 启动全部服务 (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# 初始化数据库（创建表 + 种子数据）
make db-init

# 访问
# 管理后台: http://localhost
# 安装向导: http://localhost/install
# API: http://localhost/api（Header: X-API-Version: v1）
```

### التطوير المحلي

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

## هيكل المشروع

```
ads-php/
├── service/                           # 用户端业务服务 (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (61 端点，版本路由)
│   │   │   ├── controller/v1/         # 17 个控制器
│   │   │   ├── middleware/            # 15 个中间件
│   │   │   ├── config/route.php       # 路由定义
│   │   │   └── route_helpers.php      # versioned() 辅助函数
│   │   ├── ads-platform/              # 平台适配器核心
│   │   │   ├── adapter/               # 29 个平台适配器
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # SQL 迁移 + 性能索引
│   │   ├── ads-account/               # OAuth 账户管理
│   │   ├── ads-task/                  # 定时任务调度 (6 cron)
│   │   ├── ads-alert/                 # 告警监控引擎 + 预算预警
│   │   ├── ads-report/                # 报表引擎 (CSV/Excel/PDF) + 归因引擎 + 投放日历
│   │   └── ads-tenant/                # 多租户管理
│   ├── support/                       # Erik Stack 工具类
│   │   ├── ControllerTrait.php        # 控制器公共 trait
│   │   ├── JwtService.php             # JWT 包装类
│   │   ├── CacheService.php           # Redis 缓存服务
│   │   ├── ExceptionHandler.php       # API 异常处理器
│   │   └── ApiResponse.php            # 统一响应格式
│   ├── config/                        # 全局配置 (DB/Redis/Log/Middleware)
│   ├── tests/                         # PHPUnit 测试 (244 tests)
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

## نقاط نهاية API

> جميع تعريفات نقاط نهاية API في [docs/api.ar.md](docs/api.ar.md) (تتضمن أمثلة الطلب/الاستجابة وأكواد الخطأ وسياسات تحديد المعدل).
> توثيق hg/apidoc عبر الإنترنت: بعد تشغيل الخدمة افتح `http://127.0.0.1:8788/apidoc`

## قاعدة البيانات

**قواعد التسمية**: بادئة الجداول `ads_`، مفتاح رئيسي `BIGINT UNSIGNED PRIMARY KEY` (بدون زيادة تلقائية، معرفات Snowflake)، محرك InnoDB، ترميز utf8mb4

| التصنيف | اسم الجدول | الاستخدام |
|------|------|------|
| أساسيات | `ads_tenants` | متعدد المستأجرين |
| الحسابات | `ads_platform_accounts`, `ads_auth_tokens` | حسابات منصات OAuth |
| النشر | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | مستويات نشر الإعلانات |
| التقارير | `ads_report_metrics`, `ads_report_extras` | مقاييس تقارير موحدة |
| المواد | `ads_assets` | مكتبة المواد الإبداعية |
| الاستهداف | `ads_targeting_templates` | قوالب استهداف الجمهور |
| الإسناد | `ads_conversions`, `ads_attribution_results` | تتبع التحويلات + نتائج الإسناد |
| المزايدة | `ads_bid_rules`, `ads_bid_logs` | قواعد المزايدة التلقائية + السجل |
| التنبيهات | `ads_alert_rules`, `ads_alert_logs` | مراقبة التنبيهات |
| الإشعارات | `ads_notifications` | إشعارات داخلية |
| النظام | `ads_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | أخطاء المزامنة، RBAC، التدقيق |

---

## المهام المجدولة

| المهمة | التكرار | الوظيفة |
|------|------|------|
| TokenRefreshTask | كل 55 دقيقة | فحص رموز OAuth منتهية الصلاحية وتحديثها تلقائيًا |
| DataSyncTask | كل 10 دقائق | سحب خطط+مجموعات إعلانية+مواد+تقارير كل منصة، كتابتها في جداول موحدة، ومسح التخزين المؤقت |
| AlertCheckTask | كل 5 دقائق | استعراض قواعد التنبيه المفعّلة وتقييم العتبات وتشغيل الدفع |
| BidCheckTask | كل 10 دقائق | استعراض قواعد المزايدة التلقائية والاستعلام عن المقاييس وتنفيذ تعديل الميزانية/التشغيل والإيقاف |
| BudgetCheckTask | كل 15 دقيقة | استعراض الخطط قيد النشر، تتبع استهلاك الميزانية اليومية، إنذار ثلاثي المراحل (50/80/100%) |
| RetrySyncTask | كل 3 دقائق | إعادة محاولة مهام المزامنة الفاشلة (بحد أقصى 3 مرات، تراجع أسي) |

---

## الاختبارات

```bash
cd service && ./vendor/bin/phpunit
# 244 测试 / 654 断言
```

**نطاق التغطية**: الوسائط الوسطية (Version/SQLGuard/SecurityHeaders) · كائنات البيانات (CampaignData/FieldMapping/Hashids) · المحركات (ReportBuilder/AdapterRegistry) · اختبارات التكامل (Auth/Health)

```bash
# TypeScript 检查
cd admin/public/web && npx vue-tsc --noEmit   # 零错误

# Dart 分析
cd apps/flutter && dart analyze   # 零错误
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): خط أنابيب تلقائي — **PHP Syntax ← PHPUnit ← TypeScript ← Docker Build**

**CD** (`.github/workflows/deploy.yml`): تشغيل يدوي — **Docker Buildx ← دفع GHCR (service/admin/admin-php) ← إشعار النشر**

يحدّث `.github/dependabot.yml` تلقائيًا تبعيات Composer + npm + Docker أسبوعيًا.

---

## المهارات

`docs/skills/` — 11 مهارة مشروع قابلة لإعادة الاستخدام:

| Skill | الوصف |
|------|------|
| `adapter-generator` | توليد محول منصة إعلانية جديد (قالب 14 دالة) |
| `migration-generator` | توليد ملفات ترحيل SQL (بادئة ads_ + BIGINT PK) |
| `erik-stack` | دليل استخدام تكامل حزم Erik Stack الثماني |
| `admin-page-generator` | توليد صفحات لوحة إدارة Vue3 |
| `api-endpoint` | إضافة نقاط نهاية RESTful API |
| `tdd-workflow` | عملية تحقق TDD (اختبار←تنفيذ←صياغة←TypeScript←تسليم) |
| `security-middleware` | إضافة طبقة وسيطة أمنية (مواصفات الواجهة + التسجيل + مرجع السلاسل الحالية) |
| `version-split` | تقسيم الإصدارات الثلاثة Lite/Standard/Full (خطوات التشغيل + تحديث الإعدادات) |
| `cache-strategy` | استراتيجية التخزين المؤقت ثلاثية المستويات (L1 ذاكرة/L2 APCu/L3 Redis + اقتراحات TTL) |
| `attribution-setup` | محرك الإسناد عبر المنصات (5 نماذج + استدعاءات API + تجهيز البيانات) |
| `high-concurrency` | 8 تحسينات للتزامن العالي (فصل القراءة/الكتابة/تجمع الاتصالات/قائمة انتظار الرسائل/التوسع الأفقي/CDN) |


## فتح المصدر ليس سهلاً، نرحب بدعمك

| WeChat | Alipay |
|:---:|:---:|
| ![WeChat](./docs/weixinpay.png "WeChat") | ![Alipay](./docs/alipay.png "Alipay") |

### التبرع بالتحويل العالمي (Global Transfer Donation)

**معلومات المستلم (Beneficiary)**

| الحقل | القيمة |
|------|-----|
| اسم المستلم (Name) | WANG KEXUN |
| رقم حساب المستلم (Account No.) | 881015918251 |

**البنك المستلم (Receiving Bank) — ZA Bank**

| الحقل | القيمة |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| اسم البنك (Bank Name) | ZA Bank Limited |
| رمز البنك (Bank Code) | 387 |
| عنوان البنك (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **البنك الوسيط للتحويلات الدولية (Correspondent Bank، عند الحاجة)**: هذه معلومات البنك الوسيط (المحوّل)، وليست معلومات بنك المستلم، يرجى الاستفسار من بنك التحويل عما إذا كان مطلوبًا تقديمها.
>
> - **دولار هونغ كونغ واليوان الصيني والدولار الأمريكي**: Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · البنك رقم 006 · فرع هونغ كونغ (رقم الفرع 391) · Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **العملات الأخرى**: THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

---

## الترخيص

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
