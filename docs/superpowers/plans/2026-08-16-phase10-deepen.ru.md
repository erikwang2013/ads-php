# Phase 10: План реализации «Углубление и монетизация»

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**Goal:** на основе контрактов и многоканальности Phase 7-9 реализовать четыре углубляющие возможности: визуализацию статуса синхронизации, замкнутый цикл данных о конверсиях, CI-сборку для мобильных платформ и SaaS-квоты для мультитенантности.

**Источник:** направление, выведенное командным аудитом Phase 7 (researcher: внедрение ES/разделение чтения-записи/очередей, CI Flutter/ HarmonyOS, реальная интеграция 29 платформ, SaaS-биллинг и квоты, замкнутый цикл данных о конверсиях, визуализация статуса синхронизации, AI-ставки)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## Текущее состояние (проверено)

| Кандидат | Текущее состояние |
|---|---|
| Визуализация статуса синхронизации | Таблица `erik_sync_errors` + `RetrySyncTask` (3 повтора, экспоненциальная задержка 5^n минут) уже есть; **нет фронтенд-страницы/API для отображения частоты сбоев и задержек синхронизации** |
| Замкнутый цикл данных о конверсиях | Таблицы `erik_conversions` + `erik_attribution_results` уже есть, движок атрибуции реализован; **нет точки входа для сбора данных о конверсиях** (API ретрансляции/трекинга) |
| CI для мобильных платформ | `ci.yml` только PHP-синтаксис→PHPUnit→vue-tsc→Docker; **нет сборки/пакетирования Flutter/HarmonyOS** |
| Мультитенантный SaaS | Таблица `erik_tenants` + middleware TenantIdentify уже есть; **нет биллинга/квот/статистики использования** |
| Внедрение ES | scout.php настроен + зависимость webman-scout добавлена; **в docker-compose нет сервиса ES** |
| Реальная интеграция 29 платформ | Код всех 29 адаптеров на месте; **нет записей интеграции с песочницами/учётными данными** (требуются внешние учётные данные, помечено как ручной пункт) |

## Task 1: Визуализация статуса синхронизации

### Files:
- Modify: `service/plugin/ads-api/controller/v1/DashboardController.php` или новый `service/plugin/ads-api/controller/v1/SyncController.php` + route
- Create: `admin/public/web/src/api/sync.ts`
- Create: `admin/public/web/src/views/sync/SyncStatus.vue` (или включить в системную страницу)

### Ключевые моменты дизайна
- Эндпоинты: `GET /api/sync/status` (в разрезе аккаунтов: last_sync_at, частота успеха, число сбоев сегодня, pending-число повторов) + `GET /api/sync/errors` (постраничный список ошибок с last_error/retry_count/next_retry_at)
- Фронтенд: страница статуса синхронизации (таблица + сводные карточки), только для линеек версий Full/Standard
- Источники данных: erik_platform_accounts (last_sync_at) + erik_sync_errors

## Task 2: API сбора данных о конверсиях

### Files:
- Modify: `service/plugin/ads-api/controller/v1/` (новый ConversionController + route)
- Create: `service/plugin/ads-report/service/ConversionService.php`

### Ключевые моменты дизайна
- Эндпоинты: `POST /api/conversions` (ретрансляция конверсий бизнес-стороной: platform/campaign_id/order_id/conversion_time/value/currency/channel) + `GET /api/conversions` (запрос)
- Валидация: существование campaign_id, неотрицательная сумма, формат времени; запись в erik_conversions
- Связь с атрибуцией: после ретрансляции можно инициировать пересчёт атрибуции (или пояснить, что пересчёт выполняется существующим AttributionEngine по расписанию/вручную)
- Фронтенд: добавить пояснение/демонстрацию «ретрансляции конверсий» на страницу отчёта об атрибуции (опционально)

## Task 3: CI-сборка для мобильных платформ

### Files:
- Modify: `.github/workflows/ci.yml` (новый job: сборка Flutter (web + linux или apk) + статическая проверка HarmonyOS)

### Ключевые моменты дизайна
- Flutter: `flutter pub get && flutter analyze && flutter build web` (или apk, выбрать собираемую цель по состоянию репозитория; если среда Flutter ограничена — dart analyze)
- HarmonyOS: нет стандартного Linux CI-тулчейна, сделать статическую проверку или пропустить (пометить)
- Параллельно с существующим job php-tests, не блокирует основной конвейер

## Task 4: Мультитенантные SaaS-квоты (MVP)

### Files:
- Modify: `service/plugin/ads-tenant/` (новый QuotaService)
- Modify: `service/plugin/ads-api/config/route.php` + controller

### Ключевые моменты дизайна
- Данные: добавить поле quota в erik_tenants или новая таблица erik_tenant_quotas (plan/account_limit/campaign_limit/sync_quota)
- Точки проверки: число привязанных аккаунтов, число созданных планов, ежедневное число синхронизаций (проверка на входе в AccountController/CampaignController/DataSyncTask)
- Эндпоинт: `GET /api/tenant/quota` (использование + квоты)
- Фронтенд: отображение использования квот на системной странице (опционально, в MVP достаточно API)
- Линейка версий: значения по умолчанию quota различаются для lite/standard/full (константы конфигурации)

## Приёмка (по Task)
- [ ] Task 1: эндпоинты sync API работают, фронтенд-страница отображает, покрытие тестами
- [ ] Task 2: API ретрансляции conversions поддерживает запись и чтение, валидация работает, покрытие тестами
- [ ] Task 3: новый job CI проходит (или явно помечен как пропущенный)
- [ ] Task 4: API quota возвращает корректные данные, перехват превышения лимита работает, покрытие тестами
- [ ] Все: `php vendor/bin/phpunit --no-coverage` полностью проходит, vue-tsc проходит

## Вне рамок данного этапа (требуются внешние ресурсы)
- Реальная интеграция 29 платформ (требуются учётные данные/песочницы каждой платформы)
- Внедрение сервиса ES (нужно добавить сервис ES и инициализацию индексов в docker-compose)
- AI-предложения по ставкам (модель/подготовка данных)
