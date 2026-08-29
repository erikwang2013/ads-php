# Документ по функциональному дизайну

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Определения всех API-интерфейсов (запросы/ответы/параметры) см. в [api.ru.md](api.ru.md)。

---

## Обзор модулей

| # | Модуль | Контроллер/Сервис | API-маршрутов | Vue-страниц |
|---|------|--------|-----------|----------|
| 1 | Аутентификация и авторизация | AuthController | 3 | LoginPage |
| 2 | Управление платформами | PlatformController | 3 | — |
| 3 | Управление аккаунтами | AccountController | 5 | AccountList, AccountBind |
| 4 | Рекламные кампании | CampaignController | 6 | CampaignList |
| 5 | Группы объявлений | AdGroupController | 5 | AdGroupList |
| 6 | Креативы | CreativeController | 2 | CreativeList |
| 7 | Отчеты по данным | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | Мониторинг оповещений | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | Центр уведомлений | NotificationController | 4 | NotificationList |
| 10 | Автоматические ставки | BidRuleController | 5 | BidRuleList |
| 11 | Шаблоны таргетинга | TargetingTemplateController | 5 | — |
| 12 | Системное управление | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | Синхронизация данных | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | Библиотека материалов | AssetController | 6 | AssetGallery |
| 15 | Предупреждение о бюджете | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | Календарь кампаний | CalendarService | 1 | CampaignCalendar |
| 17 | Кросс-платформенная атрибуция | AttributionEngine | 2 | AttributionReport |
| 18 | Проверка здоровья | HealthController | 2 | — |
| 19 | Капча | CaptchaController | 2 | — |
| 20 | API-документация | DocController | 1 | — |

**Итого**: 21 модуль, 75+ маршрутов, 19 Vue-страниц

---

## Модуль 1: Аутентификация и авторизация

- Проверка капчи (опционально)
- Запрос к таблице `admin_users`
- Проверка bcrypt через `password_verify()`
- Генерация JWT-токена (TTL 24ч)
- Старые токены автоматически попадают в черный список
- Извлечение `uid` из токена для запроса информации о пользователе

Интерфейсы: Вход / Обновление токена / Текущий пользователь → [api.md модуль 2](api.ru.md#模块-2-认证)

---

## Модуль 2-3: Управление платформами и аккаунтами

- Кэш списка платформ на 1 час (Redis), интеграция emoji флагов Season
- OAuth-процесс: генерация случайного state → построение URL авторизации → обработка callback → сохранение токена
- Кэш списка/деталей аккаунтов на 5 минут

Интерфейсы: Список платформ / OAuth / CRUD аккаунтов + синхронизация → [api.md модуль 3](api.ru.md#模块-3-平台--账户)

---

## Модуль 4-6: Иерархия рекламных кампаний

### Структура данных

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- Создание кампаний через адаптеры платформ + запись локально
- Фильтрация по платформе/статусу/ключевым словам, список с итогами за сегодня
- Создание группы объявлений поддерживает `targeting_template_id` для загрузки шаблона таргетинга

Интерфейсы: Кампании / Группы объявлений / Креативы → [api.md модуль 4-6](api.ru.md#模块-4-广告计划)

---

## Модуль 7: Отчеты по данным

- Кэш сводки дашборда на 5 минут: 8 карточек KPI + график дневного тренда + столбчатая диаграмма по платформам
- Измерения пользовательских отчетов: date, platform, campaign
- Метрики: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Форматы экспорта: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (HTML печать)

Интерфейсы: Сводка / Пользовательский отчет / Экспорт → [api.md модуль 7](api.ru.md#模块-7-报表)

---

## Модуль 8: Мониторинг оповещений

### Процесс оценки AlertEngine

```
遍历 enabled=1 的规则
  → 查询 ads_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### Каналы уведомлений

| Канал | Статус | Реализация |
|------|------|------|
| web | ✅ | Запись в ads_notifications |
| email | Заглушка | echo-заглушка |
| sms | Заглушка | echo-заглушка |
| Redis pub/sub | ✅ | push JSON в канал `alert:new` |

Интерфейсы: CRUD правил / записи оповещений / подтверждение / непрочитанные → [api.md модуль 8](api.ru.md#模块-8-告警)

---

## Модуль 9: Центр уведомлений

- Фронтенд: Pinia store, опрос каждые 30 секунд
- Иконка колокольчика в сайдбаре + бейдж с числом непрочитанных

Интерфейсы: Список / Непрочитанные / Отметить прочитанным / Все прочитаны → [api.md модуль 9](api.ru.md#模块-9-通知)

---

## Модуль 10: Движок автоматических ставок

### Процесс оценки BidEngine

```
遍历 enabled=1 的规则
  → 查询 ads_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 冷却检查 (cooldown_minutes 内是否有过操作)
  → 执行动作:
    - adjust_budget: 新预算 = current + adjust_step, 限制 [budget_min, budget_max]
    - toggle_pause: 暂停计划
    - toggle_enable: 启用计划
  → 通过 AdapterRegistry → PlatformAdapter 调用平台 API
  → 更新本地 DB + 写入 BidLog
```

### Поля правил

| Поле | Тип | Описание |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Отслеживаемая метрика |
| condition | gt/gte/lt/lte | Условие срабатывания |
| threshold | DECIMAL(12,2) | Порог |
| scope | tenant/platform/campaign | Область действия |
| action_type | adjust_budget/toggle_pause/toggle_enable | Действие |
| adjust_step | INT (分) | Шаг корректировки бюджета (полож. = увеличить, отриц. = уменьшить) |
| budget_min, budget_max | BIGINT | Границы бюджета |
| cooldown_minutes | INT | Период охлаждения |

Интерфейсы: CRUD правил / история ставок → [api.md модуль 10](api.ru.md#模块-10-自动出价)

---

## Модуль 11: Шаблоны таргетинга аудитории

### Интеграция в группы объявлений

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### Общая JSON-схема

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

Интерфейсы: CRUD шаблонов → [api.md модуль 11](api.ru.md#模块-11-定向模板)

---

## Модуль 12: Системное управление (Admin)

- Список пользователей с кодированием ID через hashids
- Создание пользователей с bcrypt-хешированием пароля
- Отключение пользователей — мягкое (status=0)

Поля журнала аудита: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

Интерфейсы: Управление пользователями / Журнал аудита / Роли → [api.md эндпоинты Admin](api.ru.md#admin-端点端口-8789)

---

## Модуль 13: Синхронизация данных

### Процесс DataSyncTask (каждые 10 минут)

```
遍历 sync_enabled=1 的账户
  → 获取平台适配器
  → 同步 Campaigns (fetchCampaigns → updateOrInsert)
  → 同步 AdGroups (fetchAdGroups → 遍历每 campaign)
  → 同步 Creatives (fetchCreatives → 遍历每 ad_group)
  → 同步 Reports (fetchReports → 过去 2 天 daily, 9 个指标)
  → 清除 Dashboard 缓存
  → 更新 last_sync_at
```

---

## Формат ответа

### Успех
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### Пагинация
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### Ошибка
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## Модуль 14: Библиотека рекламных материалов

- Поддерживаемые типы: image/jpeg, image/png, image/gif, image/webp, video/mp4
- Хранение файлов: `public/uploads/assets/` (локально по умолчанию), мультидрайвер объектного хранилища (local/oss/cos/s3)
- После настройки провайдера CDN по умолчанию URL ресурсов автоматически собираются с cdn_domain для ускорения доставки
- Прямая загрузка по предварительно подписанному URL: `POST /api/assets/presign` выдаёт URL, `POST /api/assets/register` регистрирует загруженный файл (недоступно на локальном драйвере)
- Удаление ресурса автоматически очищает кэш CDN (purge)
- Фронтенд: сеточная галерея + загрузка перетаскиванием + предпросмотр изображений + воспроизведение видео + копирование URL

Интерфейсы: Загрузка / Список / Детали / Удаление / Предподпись / Регистрация → [api.md модуль 12](api.ru.md#模块-12-素材库)

---

## Модуль 15: Предупреждение о бюджете

- Трёхуровневые оповещения: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask выполняется каждые 15 минут
- Дедупликация: одна кампания на одном уровне уведомляется не чаще раза в день
- Запись в таблицу `ads_notifications`

Интерфейсы: Предупреждение о бюджете → [api.md модуль 7](api.ru.md#模块-7-报表)

---

## Модуль 16: Календарь кампаний

- Агрегация расписаний кампаний по датам
- Фронтенд Gantt-диаграмма: ось x — даты, ось y — кампании, раскраска по платформам
- Поддержка переключения месячного/недельного вида

Интерфейсы: Календарь кампаний → [api.md модуль 7](api.ru.md#模块-7-报表)

---

## Модуль 17: Кросс-платформенная атрибуция

### Модели атрибуции

| Модель | Алгоритм |
|------|------|
| first_touch | Первая точка касания 100% |
| last_touch | Последняя точка касания 100% |
| linear | Все точки касания поровну (1/N) |
| time_decay | e^(-λ×Δt), период полураспада 7 дней |
| position_based | Первые 40% + последние 40% + середина 20% |

- Окно ретроспективы: 30 дней
- Источник точек касания: `ads_report_metrics` (клики > 0)
- Результаты записываются в `ads_attribution_results`
- Фронтенд: AttributionReport.vue переключение моделей + карточки статистики + столбчатая диаграмма ECharts + таблица деталей

### Таблицы данных

| Таблица | Поля |
|----|------|
| `ads_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `ads_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

Интерфейсы: Атрибутивный анализ / Список моделей → [api.md модуль 7](api.ru.md#模块-7-报表)

### Проверка здоровья
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```

---

## Модуль 18: Отказоустойчивость вызовов платформ (предохранитель/деградация)

### Конечный автомат предохранителя

`CircuitBreaker` (service/plugin/ads-platform/src/CircuitBreaker.php) — состояние на платформу:

| Состояние | Триггер | Поведение |
|-----------|---------|-----------|
| CLOSED | Норма | Вызовы пропускаются |
| OPEN | 5 последовательных сбоев | Быстрый отказ, пропуск платформы |
| HALF_OPEN | После 30 с охлаждения | Один пробный запрос |
| CLOSED | Проба успешна | Восстановление, сброс счётчика |
| OPEN | Проба снова провалилась | Повторный разрыв |

### Прокси GuardedAdapter

- `AdapterRegistry::get()` возвращает прокси GuardedAdapter; 14 точек вызова без изменений
- При OPEN выбрасывается `CircuitBreakerOpenException` (быстрый отказ); слой задач ловит и поглощает = деградация с пропуском платформы
- Метод Generator: полная итерация → success, прерывание → failure

### Проверка таймаутов

- Все 29 адаптеров содержат CURLOPT_TIMEOUT (30/60 с) + CURLOPT_CONNECTTIMEOUT (10 с)

### Покрытие тестами

- CircuitBreakerTest 8 случаев + GuardedAdapterTest 13 случаев

### Известное ограничение

- Состояние в памяти одного узла; для многоузлового развёртывания нужен общий Redis
---

## Модуль 21: Управление CDN-провайдерами

- Управление доступно только главному тенанту платформы (tenant 1), проверка AdminMiddleware
- Драйверы: local / oss (Aliyun) / cos (Tencent Cloud) / s3 (AWS S3 / Cloudflare R2 / MinIO)
- Учётные данные (access_key / secret_key / cdn_token) шифруются на уровне полей (Erikwang2013\Encryptable), API возвращает только маскированные поля
- Поддержка: провайдер по умолчанию / вкл-выкл / проверка соединения / очистка кэша (purge)
- Таблица: `ads_cdn_providers`
- Фронтенд: CdnProviderList.vue (системное меню)

Интерфейсы: Список / Создание / Обновление / Удаление / По умолчанию / Вкл-выкл / Тест / Очистка → [api.md CDN-провайдеры](api.ru.md#управление-cdn-провайдерами-только-главный-тенант-платформы-tenant-1-adminmiddleware)
