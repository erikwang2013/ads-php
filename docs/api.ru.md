# API-документация

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **Онлайн-документация hg/apidoc**: после запуска сервиса откройте `http://127.0.0.1:8788/apidoc`（переключение между приложениями Service + Admin）  
> Файл конфигурации: `service/config/plugin/hg/apidoc/app.php`

---

## Общие правила

### Base URL

```
http://your-domain.com/api
```

### Обязательные Headers

| Header | Значение | Описание |
|--------|----|------|
| `X-API-Version` | `v1` | Номер версии API (обязательно, не появляется в пути URL) |
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | Источник операции (обязательно) |
| `Authorization` | `Bearer <token>` | JWT-токен аутентификации (обязателен, кроме логина/списка платформ/проверки здоровья) |

### Headers защиты от повтора (не для браузерных клиентов)

| Header | Описание |
|--------|------|
| `X-Nonce` | Случайная строка (уникальна для каждого запроса) |
| `X-Timestamp` | Unix-таймстамп в секундах (окно ±5 минут) |

### Необязательные Headers

| Header | Описание |
|--------|------|
| `X-Tenant-Id` | ID арендатора (мультитенантный режим) |
| `X-Encrypted` | `1` = тело запроса необходимо расшифровать, тело ответа зашифровать |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| Значение | Описание |
|----|------|
| `application/json` | JSON-тело запроса (рекомендуется) |
| `application/x-www-form-urlencoded` | Формовый запрос |
| `multipart/form-data` | Загрузка файлов |

### Формат ответа

**Успех**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**Пагинация**:
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [ ... ],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

**Ошибка**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**Проверка здоровья**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### HTTP-статусы

| Код | Значение |
|--------|------|
| 200 | Успех |
| 204 | OPTIONS-предзапрос успешен |
| 400 | Ошибка параметров запроса, неподдерживаемая версия API |
| 401 | Не аутентифицирован, токен истек, IP/UA токена не совпадает |
| 403 | Доступ запрещен (XSS/обход путей/CSRF/SQL-инъекция/несовпадение Origin) |
| 404 | Ресурс не найден |
| 429 | Слишком много запросов (лимит/троттлинг входа/ограничение параллельных сессий) |
| 500 | Ошибка сервера |
| 503 | Деградация сервиса (БД или Redis недоступны) |

### Параметры пагинации

| Параметр | По умолчанию | Максимум | Описание |
|------|--------|--------|------|
| `page` | 1 | — | Номер страницы |
| `per_page` | 20 | 100 | Элементов на странице (сверх лимита автоматически обрезается) |
| `sort` | `id` | — | Поле сортировки (должно быть в белом списке) |

### Стратегия кэширования

| Эндпоинт | TTL | Слой |
|------|-----|------|
| `/api/platforms` | 1 час | L1 память → L2 APCu → L3 Redis |
| `/api/accounts` + `/api/accounts/:id` | 5 минут | То же |
| `/api/reports/summary` | 5 минут | То же |
| `/api/alerts/rules` | 2 минуты | То же |
| `/api/alerts/unread-count` | 30 секунд | То же |

---

## Модуль 1: Система

### GET /health — Проверка здоровья

```
GET /health
```

**Ответ**:
```json
{
  "status": "healthy",
  "timestamp": "2026-05-22T00:00:00+08:00",
  "checks": {
    "database": "ok",
    "redis": "ok"
  }
}
```

- `status`: `healthy` (200) или `degraded` (503)
- Аутентификация не требуется, не проходит через версионирование маршрутов

---

### GET /ping — Проверка доступности

```
GET /ping
```

**Ответ**: `{ "pong": true }`

---

### GET /docs — API-документация

```
GET /docs
```

Возвращает HTML-страницу документации API (без аутентификации).

---

### GET /api/captcha/generate — Генерация капчи

Без аутентификации.

**Ответ**:
```json
{
  "code": 0,
  "data": {
    "captcha_token": "aes-encrypted-token",
    "background": "base64...",
    "puzzle": "base64..."
  }
}
```

- Срок действия токена — 5 минут
- Допуск смещения — 5px

---

### POST /api/captcha/verify — Проверка капчи

Без аутентификации.

**Запрос**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**Ответ**: `{ "code": 0, "message": "验证通过" }`

---

## Модуль 2: Аутентификация

### POST /api/auth/login — Вход

Без аутентификации.

**Запрос**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**Ответ**:
```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "username": "admin",
      "name": "超级管理员",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

- Срок действия JWT-токена — 24 часа
- В токен встроен хеш IP + User-Agent
- 5 неудачных попыток → блокировка в Redis на 15 минут

---

### GET /api/auth/me — Текущий пользователь

**Заголовок**: `Authorization: Bearer <token>`

**Ответ**:
```json
{
  "code": 0,
  "data": {
    "id": 1,
    "username": "admin",
    "name": "超级管理员",
    "email": "admin@example.com",
    "role": "admin",
    "tenant_id": 1
  }
}
```

---

### POST /api/auth/refresh — Обновление токена

**Заголовок**: `Authorization: Bearer <old_token>`

**Ответ**:
```json
{
  "code": 0,
  "message": "Token 已刷新",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

- Старые токены автоматически попадают в черный список
- Максимум 3 активных токена на пользователя

---

## Модуль 3: Платформы & Аккаунты

### GET /api/platforms — Список платформ

Без аутентификации. Кэш 1 час.

**Ответ**:
```json
{
  "code": 0,
  "data": [
    { "code": "juliang", "name": "巨量引擎", "flag": "🇨🇳", "capabilities": ["campaign", "report"] },
    { "code": "meta", "name": "Meta Ads", "flag": "🇺🇸", "capabilities": ["campaign", "report"] }
  ]
}
```

---

### GET /api/platforms/:code/oauth-url — OAuth-URL авторизации

**Параметры**: `?redirect_uri=https://your-domain.com/callback`

**Ответ**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` должен пройти проверку белого списка SSRF (переменная окружения `OAUTH_ALLOWED_REDIRECTS`)

---

### POST /api/platforms/:code/callback — OAuth-колбэк

**Запрос**: `{ "state": "...", "code": "..." }`

**Ответ**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/accounts — Список аккаунтов

Кэш 5 минут.

**Параметры**:

| Параметр | Описание |
|------|------|
| `platform` | Фильтр по коду платформы |
| `page` | Номер страницы |
| `per_page` | Элементов на странице |

**Ответ**: формат пагинации, каждый элемент списка содержит `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at`

---

### GET /api/accounts/:id — Детали аккаунта

Кэш 5 минут.

---

### DELETE /api/accounts/:id — Отвязка аккаунта

---

### POST /api/accounts/:id/sync — Ручная синхронизация

---

## Модуль 4: Рекламные кампании

### GET /api/campaigns — Список кампаний

**Параметры**:

| Параметр | Описание | Допустимые значения |
|------|------|--------|
| `platform` | Фильтр по платформе | juliang, meta, google... |
| `status` | Фильтр по статусу | enabled, paused |
| `keyword` | Поиск по названию | любой текст |
| `sort` | Поле сортировки | id, name, platform, daily_budget, status, created_at |
| `page` | Номер страницы | — |
| `per_page` | Элементов на странице | ≤100 |

**Ответ**: формат пагинации + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/campaigns — Создание кампании

**Запрос**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**Ответ**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- Единица `daily_budget`: фэни (20000 = ¥200.00)

---

### GET /api/campaigns/:id — Детали кампании

**Ответ**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/campaigns/:id — Обновление кампании

**Запрос**: `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/campaigns/:id/toggle — Включение/отключение кампании

**Запрос**: `{ "enabled": false }`

---

### POST /api/campaigns/batch/toggle — Массовое включение/отключение

**Запрос**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**Ответ**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## Модуль 5: Группы объявлений

### GET /api/ad-groups — Список групп объявлений

**Параметры**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/ad-groups — Создание группы объявлений

**Запрос**:
```json
{
  "campaign_id": 1,
  "name": "测试广告组",
  "bid_amount": 100,
  "bid_type": "cpc",
  "targeting": { "age": { "min": 18, "max": 45 } },
  "targeting_template_id": "hashids-encoded-template-id"
}
```

- `targeting_template_id`: необязательно, загружает targeting JSON из шаблона таргетинга и объединяет

### GET /api/ad-groups/:id — Детали группы объявлений

### PUT /api/ad-groups/:id — Обновление группы объявлений

### POST /api/ad-groups/:id/toggle — Включение/отключение группы объявлений

---

## Модуль 6: Креативы

### GET /api/creatives — Список креативов

**Параметры**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/creatives/:id — Детали креатива

---

## Модуль 7: Отчеты

### GET /api/reports/summary — Сводка дашборда

Кэш 5 минут.

**Параметры**: `date_start`, `date_end`

**Ответ**:
```json
{
  "code": 0,
  "data": {
    "overview": { "cost": 123456, "impressions": 10000, ... },
    "by_platform": [ ... ],
    "daily": [ ... ]
  }
}
```

---

### GET /api/reports/custom — Пользовательский отчет

**Параметры**:

| Параметр | Описание |
|------|------|
| `dimensions[]` | Измерения: date, platform, campaign |
| `metrics[]` | Метрики: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | Дата начала |
| `date_end` | Дата окончания |
| `platform` | Фильтр по платформе |

---

### GET /api/reports/export — Экспорт отчета

**Параметры**: `format=csv`, `date_start`, `date_end`, `metrics[]`

Возвращает файл для скачивания (CSV UTF-8 BOM или Excel .xls).

---

### GET /api/reports/export-dashboard — Экспорт дашборда в PDF

---

### GET /api/reports/calendar — Календарь кампаний

**Параметры**: `date_start`, `date_end`, `platform`

**Ответ**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/reports/budget-alerts — Предупреждения о бюджете

**Ответ**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/reports/attribution — Атрибутивный анализ

**Параметры**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**Ответ**:
```json
{
  "code": 0,
  "data": {
    "total_conversions": 42,
    "total_value": 123456.78,
    "by_campaign": [ { "campaign_id": 1, "credit": 5000.00 } ]
  }
}
```

---

### GET /api/reports/attribution/models — Список моделей атрибуции

**Ответ**: `[{ code: "last_touch", name: "末次触点", description: "..." }]`

Всего 5 моделей.

---

## Модуль 8: Оповещения

### GET /api/alerts/rules — Список правил оповещений

Кэш 2 минуты.

**Параметры**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/alerts/rules — Создание правила оповещения

**Запрос**:
```json
{
  "name": "花费超限",
  "metric": "cost",
  "condition": "gt",
  "threshold": 100000,
  "scope": "tenant",
  "platform": null,
  "campaign_id": null,
  "channels": ["web"]
}
```

### PUT /api/alerts/rules/:id — Обновление правила оповещения

### DELETE /api/alerts/rules/:id — Удаление правила оповещения

### GET /api/alerts/logs — Записи оповещений

**Параметры**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/alerts/logs/:id/acknowledge — Подтверждение оповещения

### GET /api/alerts/unread-count — Количество непрочитанных оповещений

Кэш 30 секунд. Фронтенд опрашивает каждые 30 секунд.

---

## Модуль 9: Уведомления

### GET /api/notifications — Список уведомлений

**Параметры**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/notifications/unread-count — Количество непрочитанных уведомлений

### POST /api/notifications/:id/read — Отметить прочитанным

### POST /api/notifications/read-all — Отметить все прочитанными

---

## Модуль 10: Автоматические ставки

### GET /api/bid-rules — Список правил

### POST /api/bid-rules — Создание правила

**Запрос**:
```json
{
  "name": "ROI 达标加预算",
  "metric": "roi",
  "condition": "gte",
  "threshold": 3.0,
  "action_type": "adjust_budget",
  "adjust_step": 5000,
  "budget_min": 0,
  "budget_max": 100000,
  "cooldown_minutes": 60
}
```

**Описание полей**:

| Поле | Тип | Описание |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Отслеживаемая метрика |
| condition | gt/gte/lt/lte | Условие срабатывания |
| threshold | decimal | Порог |
| action_type | adjust_budget/toggle_pause/toggle_enable | Тип действия |
| adjust_step | int (фэни) | Шаг корректировки бюджета (полож. = увеличить, отриц. = уменьшить) |
| budget_min | int | Нижняя граница бюджета (фэни) |
| budget_max | int | Верхняя граница бюджета (фэни) |
| cooldown_minutes | int | Время охлаждения (по умолчанию 60) |

### PUT /api/bid-rules/:id — Обновление правила

### DELETE /api/bid-rules/:id — Удаление правила

### GET /api/bid-rules/logs — История ставок

**Параметры**: `rule_id`, `campaign_id`

---

## Модуль 11: Шаблоны таргетинга

### GET /api/targeting-templates — Список шаблонов

**Параметры**: `platform`

### GET /api/targeting-templates/:id — Детали шаблона

### POST /api/targeting-templates — Создание шаблона

**Запрос**:
```json
{
  "name": "核心受众",
  "platform": "",
  "targeting": {
    "age": { "min": 18, "max": 45 },
    "gender": "all",
    "interests": ["sports", "tech"],
    "devices": { "os": ["android", "ios"] }
  },
  "is_shared": 0
}
```

### PUT /api/targeting-templates/:id — Обновление шаблона

### DELETE /api/targeting-templates/:id — Удаление шаблона

---

## Модуль 12: Библиотека материалов

### GET /api/assets — Список материалов

**Параметры**: `type`(image/video), `page`, `per_page`

### POST /api/assets/upload — Загрузка материала

**Запрос**: `multipart/form-data`, поле `file`

- Изображения: максимум 5 MB (jpeg/png/gif/webp)
- Видео: максимум 50 MB (mp4)

**Ответ**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

- При настроенном CDN `url` собирается с `cdn_domain` провайдера по умолчанию в полный HTTPS-адрес

### POST /api/assets/presign — Получение предподписанного URL загрузки

**Запрос**: `{ "filename": "demo.mp4", "mime_type": "video/mp4" }`

**Ответ**: `{ "code": 0, "data": { "key": "20260829/ab12...cd34.mp4", "upload_url": "https://signed-url", "expires_in": 3600, "url": "https://cdn.example.com/uploads/assets/..." } }`

- Формат `key`: `Ymd/32hex.расширение`; после прямой загрузки вернуть в `/api/assets/register`
- Для видео до 50 МиБ клиент грузит напрямую в объектное хранилище; на драйвере `local` недоступно

### POST /api/assets/register — Регистрация материала, загруженного напрямую

**Запрос**: `{ "key": "20260829/ab12...cd34.mp4", "filename": "demo.mp4", "mime_type": "video/mp4", "size": 52428800 }`

**Ответ**: `{ "code": 0, "message": "Created", "data": { "id": "hashids", "url": "https://cdn.example.com/uploads/assets/...", "type": "video" } }`

- `key` строго проверяется (`^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$`) — защита от path traversal

### GET /api/assets/:id — Детали материала

### DELETE /api/assets/:id — Удаление материала

---

## Эндпоинты Admin (порт 8789)

### POST /api/admin/login — Вход администратора

**Запрос**: `{ "username": "admin", "password": "..." }`

**Ответ**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Токен сохраняется в localStorage
- `csrf_token` необходимо передавать в заголовке `X-CSRF-Token` при последующих POST/PUT/DELETE-запросах

### GET /api/admin/me — Текущий администратор

### POST /api/admin/logout — Выход

### GET /api/admin/users — Список пользователей

**Параметры**: `keyword`, `role_id`, `page`, `per_page`

В ответе `id` и `role_id` закодированы через hashids.

### POST /api/admin/users — Создание пользователя

### PUT /api/admin/users/:id — Обновление пользователя

### DELETE /api/admin/users/:id — Отключение пользователя

### GET /api/admin/users/roles — Список ролей

### GET /api/admin/audit-logs — Журнал аудита

**Параметры**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

### Управление CDN-провайдерами (только главный тенант платформы tenant 1, AdminMiddleware)

### GET /api/admin/cdn/providers — Список провайдеров

### POST /api/admin/cdn/providers — Создать провайдера

**Запрос**: `{ "name": "Aliyun OSS", "driver": "oss", "bucket": "ads-assets", "region": "oss-cn-hangzhou", "endpoint": "https://oss-cn-hangzhou.aliyuncs.com", "access_key": "...", "secret_key": "...", "cdn_domain": "cdn.example.com", "cdn_driver": "aliyun", "cdn_token": "...", "is_default": 1 }`

- `driver`: `local` / `oss` (Alibaba Cloud OSS) / `cos` (Tencent Cloud COS, протокол S3) / `s3` (S3-совместимые: AWS S3 / Cloudflare R2 / MinIO)
- Учётные данные (access_key/secret_key/cdn_token) шифруются по полям через Encryptable; в ответе только маскированные поля

### PUT /api/admin/cdn/providers/:id — Обновить провайдера

### DELETE /api/admin/cdn/providers/:id — Удалить (умолчание автоматически передаётся следующему enabled)

### PUT /api/admin/cdn/providers/:id/default — Сделать провайдером по умолчанию

### PUT /api/admin/cdn/providers/:id/toggle — Включить/отключить (при отключении умолчание передаётся автоматически)

### POST /api/admin/cdn/providers/:id/test — Проверка связи

**Ответ**: `{ "code": 0, "data": { "ok": true, "driver": "oss", "status": "ok" } }`

### POST /api/admin/cdn/providers/:id/purge — Очистка кэша CDN

**Запрос**: `{ "paths": ["/uploads/assets/20260829/xxx.mp4"] }`

- Требуются `cdn_driver` и `cdn_domain`; для `aliyun` реализовано (подпись OpenAPI), cloudflare/cloudfront — в планах

---

## Справочник кодов ошибок

| code | HTTP | Описание |
|------|------|------|
| 0 | 200 | Успех |
| 1 | 200/400 | Общая бизнес-ошибка |
| 401 | 401 | Не аутентифицирован / токен истек / IP/UA не совпадает |
| 403 | 403 | Доступ запрещен (защитное блокирование) |
| 404 | 404 | Ресурс не найден |
| 422 | 422 | Ошибка валидации параметров |
| 429 | 429 | Слишком много запросов / троттлинг входа / ограничение параллельности |
| 1001 | 200 | Ошибка аутентификации (неверное имя пользователя или пароль) |

---

## Ответы при защитном блокировании

Когда запрос блокируется middleware безопасности, возвращается 403:

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## Ответ при лимитировании

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

Заголовок `Retry-After` содержит оставшееся время ожидания в секундах.
