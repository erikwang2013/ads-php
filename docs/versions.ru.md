# Сравнение версий

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| Версия | Лицензия | Как получить |
|------|------|----------|
| **简化版 (Lite)** | Открытый исходный код (MIT) | Публичный репозиторий GitHub |
| **标准版 (Standard)** | Коммерческая лицензия | Связаться: erik@erik.xyz |
| **完整版 (Full)** | Коммерческая лицензия | Связаться: erik@erik.xyz |

---

## Сравнение функций

### Базовые функции

| Функция | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Аутентификация (вход/обновление токена/текущий пользователь) | ✅ | ✅ | ✅ |
| Управление платформами (список 29 платформ + OAuth) | ✅ | ✅ | ✅ |
| Управление аккаунтами (CRUD + синхронизация) | ✅ | ✅ | ✅ |
| Рекламные кампании (CRUD + вкл/выкл + массовые) | ✅ | ✅ | ✅ |
| Отчеты (дашборд + пользовательский + экспорт CSV/Excel/PDF) | ✅ | ✅ | ✅ |
| Проверка здоровья + API-документация + капча | ✅ | ✅ | ✅ |
| Синхронизация данных (Campaign + Report) | ✅ | ✅ | ✅ |

### Управление кампаниями

| Функция | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Группы объявлений (CRUD + вкл/выкл) | — | ✅ | ✅ |
| Креативы (список + детали) | — | ✅ | ✅ |
| Синхронизация групп объявлений/креативов | — | ✅ | ✅ |

### Мониторинг и уведомления

| Функция | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Движок правил оповещений (7 метрик/4 условия/3 области) | — | ✅ | ✅ |
| Записи оповещений + подтверждение + непрочитанные | — | ✅ | ✅ |
| Центр уведомлений (список/прочитано/все прочитаны) | — | ✅ | ✅ |

### Расширенные функции

| Функция | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Движок правил автоматических ставок (3 действия/охлаждение) | — | — | ✅ |
| Шаблоны таргетинга аудитории (общая JSON-схема) | — | — | ✅ |
| Библиотека рекламных материалов (загрузка/галерея/просмотр) | — | — | ✅ |
| Предупреждение о бюджете (трёхуровневые оповещения 50/80/100%) | — | — | ✅ |
| Календарь кампаний (Gantt-визуализация) | — | — | ✅ |
| Кросс-платформенная атрибуция (5 моделей/окно 30 дней) | — | — | ✅ |

---

## Сравнение защитных мер

| Мера защиты | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| Белый список CORS | ✅ | ✅ | ✅ |
| Защитные заголовки ответа (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| Версионирование маршрутов (X-API-Version) | ✅ | ✅ | ✅ |
| Лимитирование API (скользящее окно) | ✅ | ✅ | ✅ |
| Обнаружение SQL-инъекций (сопоставление шаблонов) | ✅ | ✅ | ✅ |
| Фильтрация ввода (strip_tags + trim) | ✅ | ✅ | ✅ |
| Шифрование при передаче (X-Encrypted) | ✅ | ✅ | ✅ |
| JWT Bearer аутентификация | ✅ | ✅ | ✅ |
| Обнаружение XSS-атак (11 шаблонов) | — | ✅ | ✅ |
| Обнаружение обхода путей (7 шаблонов) | — | ✅ | ✅ |
| Обнаружение инъекций в заголовки | — | ✅ | ✅ |
| Ограничение размера Body (10 MiB) | — | ✅ | ✅ |
| Белый список Content-Type | — | ✅ | ✅ |
| Определение источника клиента (8 платформ) | — | ✅ | ✅ |
| Троттлинг входа (5 раз → 15 минут) | — | ✅ | ✅ |
| Мониторинг времени ответа (X-Response-Time) | — | ✅ | ✅ |
| Проверка Origin/Referer | — | — | ✅ |
| Защита от повторных атак (Nonce+Timestamp) | — | — | ✅ |
| Ограничение параллельных сессий (максимум 3) | — | — | ✅ |
| CSRF Token (сторона Admin) | — | — | ✅ |
| Защита SSRF (OAuth белый список) | — | — | ✅ |
| Маскирование данных в логах | — | — | ✅ |
| Привязка JWT IP/UA | — | — | ✅ |

---

## Сравнение цепочек middleware

### Сторона Service

| Lite (7 слоев) | Standard (11 слоев) | Full (15 слоев) |
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

### Сторона Admin

| Lite (1 слой) | Standard (4 слоя) | Full (5 слоев) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## Сравнение плановых задач

| Задача | Частота | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55 мин | ✅ | ✅ | ✅ |
| DataSyncTask | 10 мин | ✅ (только Campaign+Report) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3 мин | ✅ | ✅ | ✅ |
| AlertCheckTask | 5 мин | — | ✅ | ✅ |
| BidCheckTask | 10 мин | — | — | ✅ |
| BudgetCheckTask | 15 мин | — | — | ✅ |

---

## Сравнение таблиц БД

| Категория | Таблица | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| Базовые | ads_tenants | ✅ | ✅ | ✅ |
| Аккаунты | ads_platform_accounts | ✅ | ✅ | ✅ |
| | ads_auth_tokens | ✅ | ✅ | ✅ |
| Кампании | ads_campaigns | ✅ | ✅ | ✅ |
| | ads_report_metrics | ✅ | ✅ | ✅ |
| | ads_report_extras | ✅ | ✅ | ✅ |
| | ads_ad_groups | — | ✅ | ✅ |
| | ads_creatives | — | ✅ | ✅ |
| Оповещения | ads_alert_rules | — | ✅ | ✅ |
| | ads_alert_logs | — | ✅ | ✅ |
| Уведомления | ads_notifications | — | ✅ | ✅ |
| Ставки | ads_bid_rules | — | — | ✅ |
| | ads_bid_logs | — | — | ✅ |
| Таргетинг | ads_targeting_templates | — | — | ✅ |
| Материалы | ads_assets | — | — | ✅ |
| Атрибуция | ads_conversions | — | — | ✅ |
| | ads_attribution_results | — | — | ✅ |
| Системные | ads_sync_errors | ✅ | ✅ | ✅ |
| Управление | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **Итого** | | **8** | **13** | **18** |

---

## Сравнение страниц фронтенда

### Vue Admin SPA

| Страница | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Вход | ✅ | ✅ | ✅ |
| Дашборд | ✅ | ✅ | ✅ |
| Список аккаунтов + привязка | ✅ | ✅ | ✅ |
| Рекламные кампании | ✅ | ✅ | ✅ |
| Экспорт отчетов | ✅ | ✅ | ✅ |
| Управление пользователями | ✅ | ✅ | ✅ |
| Журнал аудита | ✅ | ✅ | ✅ |
| Группы объявлений | — | ✅ | ✅ |
| Креативы | — | ✅ | ✅ |
| Анализ отчетов (ECharts) | — | ✅ | ✅ |
| Правила оповещений | — | ✅ | ✅ |
| Записи оповещений | — | ✅ | ✅ |
| Центр уведомлений | — | ✅ | ✅ |
| Автоматические ставки | — | — | ✅ |
| Библиотека материалов | — | — | ✅ |
| Календарь кампаний | — | — | ✅ |
| Атрибутивный анализ | — | — | ✅ |
| **Итого** | **7** | **13** | **17** |

### Flutter

| Страница | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Вход | ✅ | ✅ | ✅ |
| Дашборд | ✅ | ✅ | ✅ |
| Рекламные кампании (список+детали) | ✅ | ✅ | ✅ |
| Отчеты по данным | ✅ | ✅ | ✅ |
| Аккаунты платформ | ✅ | ✅ | ✅ |
| Управление оповещениями | ✅ | ✅ | ✅ |
| Группы объявлений | — | ✅ | ✅ |
| Креативы | — | ✅ | ✅ |
| Анализ отчетов | — | ✅ | ✅ |
| Центр уведомлений | — | ✅ | ✅ |
| Автоматические ставки | — | — | ✅ |
| **Итого** | **6** | **10** | **11** |

---

## Сравнение API-эндпоинтов

| Модуль | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Система (health/ping/docs/captcha) | 6 | 6 | 6 |
| Аутентификация (login/me/refresh) | 3 | 3 | 3 |
| Платформы (list/oauthUrl/callback) | 3 | 3 | 3 |
| Аккаунты (index/show/destroy/sync) | 4 | 4 | 4 |
| Рекламные кампании (CRUD/toggle/batch) | 6 | 6 | 6 |
| Группы объявлений (CRUD/toggle) | — | 5 | 5 |
| Креативы (index/show) | — | 2 | 2 |
| Отчеты (summary/custom/export×2) | 4 | 4 | 4 |
| Отчеты (calendar/budget/attribution/models) | — | — | 4 |
| Оповещения (CRUD правил + logs + acknowledge + unread) | — | 7 | 7 |
| Уведомления (index/unread/read/readAll) | — | 4 | 4 |
| Автоматические ставки (CRUD + logs) | — | — | 5 |
| Шаблоны таргетинга (CRUD) | — | — | 5 |
| Библиотека материалов (index/upload/show/destroy) | — | — | 4 |
| **Итого** | **26** | **44** | **62** |

---

## Технологический стек

Все три версии используют единый технологический стек:

| Слой | Технология |
|----|------|
| Бэкенд-фреймворк | webman v2, PHP 8.2+ |
| База данных | MySQL 8.0 (InnoDB, utf8mb4) |
| Кэш | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| Аутентификация | erikwang2013/jwt-webman |
| Генерация ID | erikwang2013/snowflake-php |
| Кодирование ID | erikwang2013/hashids |
| Фронтенд | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| Деплой | Docker + Nginx + Docker Compose |

---

## Пути обновления

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
