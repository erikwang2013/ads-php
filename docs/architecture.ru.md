# Документ по архитектуре

[中文](docs/architecture.md) | [English](docs/architecture.en.md) | [한국어](docs/architecture.ko.md) | [Русский](docs/architecture.ru.md) | [Deutsch](docs/architecture.de.md) | [Français](docs/architecture.fr.md) | [Español](docs/architecture.es.md) | [Português](docs/architecture.pt.md) | [हिन्दी](docs/architecture.hi.md) | [العربية](docs/architecture.ar.md) | [বাংলা](docs/architecture.bn.md) | [Bahasa Indonesia](docs/architecture.id.md) | [日本語](docs/architecture.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 1. Обзор системы

Многофункциональная система управления рекламой, интегрированная с **29 рекламными платформами**, охватывает управление кампаниями, кросс-платформенные отчеты, мониторинг оповещений, автоматические ставки и таргетинг аудитории. Поддерживает три режима: SaaS-мультитенантность, управление по поручению (агентский) и самостоятельное использование.

---

## 2. Архитектура развертывания

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

## 3. Конвейер обработки запросов

### 3.1 Сторона Service (15 слоев middleware)

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

### 3.2 Сторона Admin (6 слоев middleware)

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

## 4. Структура каталогов

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
│   │   ├── ads-tenant/                    # 多租户
│   │   └── ads-storage/                   # Абстракция хранилища (local/OSS/COS/S3) + CDN-провайдеры
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

## 5. Модель данных

### 5.1 Классификация таблиц

| Категория | Таблица | Первичный ключ | Назначение |
|------|------|------|------|
| Базовые | `ads_tenants` | BIGINT Snowflake | Мультитенантность |
| Аккаунты | `ads_platform_accounts`, `ads_auth_tokens` | BIGINT Snowflake | OAuth-аккаунты платформ |
| Иерархия кампаний | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | BIGINT Snowflake | Рекламные кампании |
| Отчеты | `ads_report_metrics`, `ads_report_extras` | BIGINT Snowflake | Унифицированные метрики |
| Оповещения | `ads_alert_rules`, `ads_alert_logs` | BIGINT Snowflake | Мониторинг и оповещения |
| Ставки | `ads_bid_rules`, `ads_bid_logs` | BIGINT Snowflake | Автоматические ставки |
| Таргетинг | `ads_targeting_templates` | BIGINT Snowflake | Шаблоны аудитории |
| Материалы | `ads_assets` | BIGINT Snowflake | Библиотека креативов |
| CDN | `ads_cdn_providers` | BIGINT Snowflake | Конфигурация CDN-провайдера (учётные данные с шифрованием на уровне полей) |
| Уведомления | `ads_notifications` | BIGINT Snowflake | Внутренние уведомления |
| Атрибуция | `ads_conversions`, `ads_attribution_results` | BIGINT Snowflake | Отслеживание конверсий + атрибуция |
| Системные | `ads_sync_errors` | BIGINT Snowflake | Ошибки синхронизации |
| Управление | `admin_users`, `admin_roles`, `admin_audit_logs` | BIGINT Snowflake | RBAC + аудит |

### 5.2 Правила именования

- Префикс таблиц: `ads_`
- Первичный ключ: `BIGINT UNSIGNED PRIMARY KEY` (без автоинкремента, Snowflake ID)
- Движок: InnoDB, кодировка: utf8mb4
- Метки времени: `created_at`, `updated_at` (DATETIME)

---

## 6. Архитектура безопасности

### 6.1 Уровни защиты

| Слой | Механизм | Охват |
|----|------|----------|
| Передача | Nginx (терминация SSL) | Все |
| Сеть | Белый список CORS + проверка Origin + HSTS | Service |
| Ввод | AttackGuard (XSS 11 шаблонов/обход путей 7 шаблонов/инъекция заголовков) | Service + Admin |
| Инъекции | SQLGuard (обнаружение шаблонов SQL-инъекций) | Service |
| Очистка | ValidationMiddleware (strip_tags) | Service |
| Аутентификация | JWT Bearer + bcrypt + привязка IP/UA + ротация refresh | Service |
| Аутентификация | Session + JWT двойной канал + CSRF Token | Admin |
| Авторизация | RBAC (роли + JSON-права) | Admin |
| Троттлинг | RateLimit (скользящее окно) + LoginThrottle (5 раз → 15 минут) | Service + Admin |
| Сессии | SessionLimit (максимум 3 активных токена) + черный список | Service |
| Шифрование | EncryptionMiddleware (передача) + Encryptable (хранение) | Service |
| Повторные атаки | ReplayGuard (Nonce+Timestamp ±5мин, не для браузерных клиентов) | Service + клиенты |
| Отказоустойчивость | CircuitBreaker (на платформу: 5 сбоев → OPEN → 30 с полуоткрытое) + GuardedAdapter (fast-fail при деградации) | Service |
| Аудит | Траектория операций (IP/UA/платформа) | Admin |
| Маскирование | Маскирование чувствительных полей в логах (password/token/secret → ***) | Service |

### 6.2 Определение платформы клиента

Через заголовок `X-Client-Platform`:

| Значение | Источник |
|----|------|
| `web` | Vue Admin, Flutter Web |
| `ios` / `android` | Flutter Mobile |
| `ipados` / `macos` / `windows` / `linux` | Flutter Desktop |
| `harmonyos` | HarmonyOS App |

---

## 7. Механизм версионирования маршрутов API

Номер версии не появляется в пути URL. Версия передается через заголовок `X-API-Version`, `VersionMiddleware` считывает ее и устанавливает `$request->apiVersion`. Вспомогательная функция `versioned()` во время выполнения заменяет сегмент версии в классе контроллера на версию запроса.

```
请求: GET /api/campaigns
Header: X-API-Version: v1

VersionMiddleware → $request->apiVersion = 'v1'
versioned(CampaignController::class, 'index')
  → controller\v1\CampaignController::index()
```

---

## 8. Планирование задач по расписанию

| Задача | Cron | Функция |
|------|------|------|
| TokenRefreshTask | `55 */1 * * *` | Обновление просроченных OAuth-токенов |
| DataSyncTask | `*/10 * * * *` | Синхронизация Campaigns→AdGroups→Creatives→Reports→очистка кэша |
| AlertCheckTask | `*/5 * * * *` | Оценка правил оповещений, запуск уведомлений |
| BidCheckTask | `*/10 * * * *` | Оценка правил ставок, корректировка бюджета/включение-отключение |
| RetrySyncTask | `*/3 * * * *` | Повтор неудачных синхронизаций (максимум 3 раза, экспоненциальная задержка) |

---

## 9. Интеграция пакетов Erik Stack

| Пакет | Место интеграции | Назначение |
|----|----------|------|
| `erikwang2013/snowflake-php` | 10 моделей (SnowflakeTrait) + admin helpers.php | Генерация первичных ключей |
| `erikwang2013/hashids` | ApiResponse + 2 контроллера Admin | Кодирование ID |
| `erikwang2013/jwt-webman` | JwtService (encode/decode/refresh) | Токены аутентификации |
| `erikwang2013/encryption` | EncryptionMiddleware | Шифрование при передаче |
| `erikwang2013/encryptable` | PlatformAccount + AuthToken Model | Шифрование полей БД |
| `erikwang2013/webman-scout` | Campaign Model (Searchable trait) | Поиск ES |
| `erikwang2013/season` | PlatformController (getCountryFlagEmoji) | Флаги стран |
| `erikwang2013/poster-php` | AuthController (CaptchaService) | Слайдер-капча |
| `hg/apidoc` | Аннотации → генерация документации (Web UI: :8788/apidoc) | API-документация |

---

## 10. Архитектура высокой нагрузки

### 10.1 Уровень базы данных

| Оптимизация | Описание |
|------|------|
| Разделение чтения/записи | Основная БД `shared` (запись) + реплика только для чтения `read_replica` (отчеты/аналитические запросы) |
| Постоянные соединения | `PDO::ATTR_PERSISTENT` + `mysqli max_persistent` для избежания частых TCP-рукопожатий |
| Прогрев соединений | При старте worker выполняется `SELECT 1`, пул соединений готов до приема запросов |

### 10.2 Уровень кэша

```
L1: 进程内存数组 (< 1µs, 最大快但也最局部)
L2: APCu 共享内存 (< 100µs, 进程间共享)
L3: Redis (< 1ms, 跨服务器共享, 持久化)
```

### 10.3 Очередь сообщений

```
HTTP Request → Controller → AsyncJobService::dispatch()
  → Redis List (queue:async:sync)
  → Queue Worker (BidCheckTask / DataSyncTask)
  → 异步处理 (无需阻塞 HTTP 响应)
```

4 канала: `sync` | `report` | `export` | `notification`

### 10.4 Горизонтальное масштабирование

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

- **keepalive**: 32 длинных соединений с переиспользованием
- **failover**: `proxy_next_upstream` автоматическое переключение при сбое, 2 повтора
- **Лимитирование**: `limit_req_zone` 30r/s + burst 20 + `limit_conn` 20

### 10.5 CDN для статических ресурсов

- `expires 30d` + `Cache-Control: public, immutable`
- `gzip_static on` — предварительно сжатые js/css файлы
- В продакшене подключение CDN (CloudFront/Aliyun CDN)

### 10.6 CDN-ускорение материалов

Сборка URL материалов, стратегии кэша и очистки — см. [главу 12 «Хранилище материалов и CDN-ускорение»](#12-хранилище-материалов-и-cdn-ускорение).

---

## 11. Развертывание и CI/CD

### Docker-сервисы

| Сервис | Порт | Образ |
|------|------|------|
| mysql | 3306 | mysql:8.0 |
| redis | 6379 | redis:7-alpine |
| php (service) | 8788 | Dockerfile |
| admin-php | 8789 | Dockerfile.admin-php |
| nginx | 80 | Dockerfile.admin |

### CI/CD

- **CI** (`.github/workflows/ci.yml`): PHP Syntax → PHPUnit → TypeScript → Docker Build
- **CD** (`.github/workflows/deploy.yml`): Docker Buildx → GHCR Push (service/admin/admin-php) → Deploy

---

## 12. Хранилище материалов и CDN-ускорение

### 12.1 Слой абстракции хранилища

`service/plugin/ads-storage/` предоставляет единый фасад `Storage` + интерфейс `StorageDriver` (put/delete/signedUrl/publicUrl/putFile/deleteUrl/purge) с переключением реализации по driver:

| driver | реализация | применение |
|--------|------------|------------|
| `local` | LocalStorage | По умолчанию, локально `public/uploads/assets/` |
| `oss` | AlibabaOssStorage | Alibaba Cloud OSS |
| `cos` | TencentCosStorage | Tencent Cloud COS (по протоколу S3) |
| `s3` | S3CompatibleStorage | S3-совместимые: AWS S3 / Cloudflare R2 / MinIO |

При раздаче приоритет у провайдера по умолчанию из БД (настраивается в админке), при отсутствии — env/local.

### 12.2 Управление CDN-провайдерами

Новая таблица `ads_cdn_providers` (name/driver/bucket/region/endpoint/access_key/secret_key/cdn_domain/cdn_driver/cdn_token/enabled/is_default/status):

- Учётные данные (access_key/secret_key/cdn_token) шифруются на уровне полей через `Erikwang2013\Encryptable`; API отвечает только маскированными полями
- Управлять может только главный тенант платформы (tenantId=1, AdminMiddleware); 8 эндпоинтов `/api/admin/cdn/providers`: список/создание/обновление/удаление/по умолчанию/вкл-выкл/проверка связи/очистка кэша
- purge реально реализован для `aliyun` cdn_driver (подпись OpenAPI); cloudflare/cloudfront — в планах

### 12.3 Стратегия сборки URL

`ads_assets.url` всегда хранит относительный путь (`/uploads/assets/...`); при чтении к нему подставляется `cdn_domain` провайдера по умолчанию — получается полный HTTPS URL (`https://{cdn_domain}/{url}`); без CDN возвращается как есть.

### 12.4 Стратегия кэша

| тип | стратегия |
|-----|-----------|
| изображения | `immutable` долгий кэш (случайные имена файлов, уникальные URL — безопасно) |
| видео | короткий кэш + поддержка Range (сегментированное воспроизведение) |

При удалении материала его URL автоматически очищается из кэша CDN.

### 12.5 Изоляция путей между тенантами

Ключи материалов содержат префикс изоляции тенанта и группируются по tenant_id; материалы разных тенантов невидимы друг для друга.

### 12.6 Предподписанная прямая загрузка и перенос

- `POST /api/assets/presign`: получение предподписанного URL загрузки (клиент грузит напрямую в объектное хранилище, напр. видео 50 МиБ); формат `key` — `Ymd/32hex.расширение`
- `POST /api/assets/register`: регистрация загруженного напрямую материала; формат key строго проверяется от path traversal
- presign недоступен на драйвере `local` (нет подписи объектного хранилища)
- `service/scripts/backfill-assets.php`: переносит существующие локальные материалы в объектное хранилище (`--dry-run` предпросмотр); колонка `url` не меняется

### 12.7 Путь источника

`service/config/static.php` включает раздачу статики webman; `/uploads/assets` отдаётся напрямую по HTTP на 8788 и служит путём источника для CDN.
