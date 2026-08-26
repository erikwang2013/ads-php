# Phase 7: План реализации исправления межклиентских контрактов

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **Обновление статуса (2026-08-16):** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ всё выполнено, регрессионная проверка tester пройдена (35 tests OK, перекрёстная сверка контрактов без фантомных эндпоинтов, Phase 7 можно принимать).

**Goal:** исправить выявленные командным аудитом проблемы межклиентских API-контрактов: 3 фантомных эндпоинта Flutter (404), баг двойного префикса в Admin `admin.ts`, отсутствие маршрута `/system/info`, неподключённый ServiceProxy, устаревшая документация. Восстановить согласованное потребление service API тремя клиентами (Admin/Flutter/HarmonyOS).

**Источник:** параллельный аудит команды 2026-08-07 (backend-dev инвентаризация 61 эндпоинта маршрутов, vue-dev инвентаризация 50 точек вызовов Admin, mobile-dev инвентаризация мобильной стороны, researcher перекрёстное сравнение реализованного/запланированного)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1: Исправить фантомные эндпоинты Flutter (🔴 высший приоритет)

### Предыстория
3 страницы Flutter вызывают несуществующие в service маршруты, все 404:

| Вызов Flutter | Фактический маршрут service | Решение |
|---|---|---|
| `GET /dashboard` | нет (сводка дашборда в `/reports/summary`) | изменить на `GET /reports/summary` |
| `GET /alerts` | нет (оповещения в `/alerts/rules`, `/alerts/logs`, `/alerts/unread-count`) | изменить на `GET /alerts/logs` (семантика списка оповещений) |
| `GET /reports` | нет (отчёты в `/reports/summary`, `/reports/custom`) | изменить на `GET /reports/custom` (с параметрами даты/измерений/метрик, соответствует ReportBuilder::buildCustom) |

### Files:
- Modify: `apps/flutter/lib/features/dashboard/dashboard_page.dart` (`/dashboard` → `/reports/summary` ×2 диапазона, адаптация структуры ответа `data.overview`/`by_platform`/`daily`) ✅
- Modify: `apps/flutter/lib/features/alert/alert_page.dart` (`/alerts` → `/alerts/logs`, адаптация структуры пагинации `data.list`, поля AlertLog rule_name/metric/current_value/condition/threshold) ✅
- Modify: `apps/flutter/lib/features/report/report_page.dart` (`/reports` → `/reports/custom`, параметры date_start/date_end/dimensions[]/metrics[], разбор `data.list`, поле cost) ✅
- Verify: поля ответа соответствуют фактическому возврату `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` ✅

### Приёмка
- [x] Правки трёх путей выполнены, параметры запроса сохранены (параметры даты страницы отчёта → date_start/date_end + dimensions/metrics) ✅
- [x] Разбор ответа согласован с фактической JSON-структурой бэкенда (overview / paginated list / custom list) ✅
- [x] После правок `flutter analyze` без ошибок — в данном окружении кэш Flutter SDK доступен только для чтения, запустить нельзя, использован встроенный в SDK `dart analyze` по всему проекту **0 errors** (15 существующих предупреждений были до правок, новые проблемы не внесены) ✅

---

## Task 2: Исправить баг двойного префикса в Admin `admin.ts`

### Предыстория
- В `admin/public/web/src/api/admin.ts` пути записаны как `/api/admin/...`, а baseURL axios уже `/api` (`src/api/index.ts`), фактически склеивается `/api/api/admin/...`, 5 вызовов UserManage.vue / AuditLog.vue с высокой вероятностью 404.
- **Глубокая архитектурная проблема (подтверждена итоговым отчётом vue-dev)**: бэкенд admin (8789) сам предоставляет 12 локальных маршрутов (`/api/admin/login`, `me`, `logout`, `users` CRUD, `roles`, `audit-logs`, `/api/install/*`), но:
  - в `docker/nginx/admin.conf` `location /api/` **весь** proxy_pass на `service_api` (php:8788);
  - `upstream admin_backend` (admin-php:8789) хоть и определён, но **ни один location на него не ссылается** → в проде `/api/admin/*` никогда не дойдёт до 8789;
  - Vite dev-прокси также направляет весь `/api` на 8788.
  - Вывод: даже после исправления двойного префикса `/api/admin/*` всё ещё 404 — локальные маршруты бэкенда admin не подключены в продакшн-цепочке.

### Точка решения (требуется подтверждение backend-dev + vue-dev + devops)
- Вариант A (рекомендуется): vue-dev меняет пути в `admin.ts` на относительные `/admin/users`, `/admin/audit-logs`, при этом **devops добавляет в Nginx `location /api/admin/` → `proxy_pass http://admin_backend`** (перед `location /api/`, точное совпадение префикса приоритетнее), чтобы маршруты admin обслуживались напрямую 8789, а бизнес-маршруты по-прежнему шли через 8788
- Вариант B: backend-dev добавляет в service маршруты `/api/admin/*` (пересечение обязанностей с Admin-стороной, не рекомендуется)
- Вариант C: бизнес-запросы тоже перевести на ServiceProxy (нужно подключение, самые большие изменения, рассматривать только при необходимости единой авторизации на admin-стороне)

### Files:
- Modify: `admin/public/web/src/api/admin.ts` (убрать префикс `/api`)
- Modify: `docker/nginx/admin.conf` (добавить `location /api/admin/` → upstream admin_backend)
- Modify: `admin/public/web/vite.config.ts` (в dev-прокси добавить правило `/api/admin` → 8789, перед `/api`)
- Verify: маршруты бэкенда admin в `admin/config/route.php` (/api/admin/users и т.д.) совпадают с вызовами фронтенда

### Приёмка
- [x] Пути запросов фронтенда совпадают с фактически существующими маршрутами бэкенда (без 404) — все 9 методов admin.ts сверены с route.php ✅, vue-tsc проходит
- [x] Nginx / Vite корректно разделяют трафик `/api/admin/*` → 8789, остальной `/api/*` → 8788 — в Nginx добавлен `location /api/admin/`, в Vite прокси `/api/admin` (перед `/api`) ✅
- [x] Функциональность страниц UserManage / AuditLog работает — пути согласованы (включая решение listRoles → `/admin/users/roles`) ✅

---

## Task 3: `/system/info` без маршрута + решение по ServiceProxy

### Предыстория
- `SystemInfo.vue` / `stores/admin.ts` вызывают `GET /api/system/info`, в service такого маршрута нет (только /health, /ping), 404 проглатывается try/catch
- `admin/app/controller/ServiceProxy.php` определён, но по всему репозиторию 0 активных вызывающих («определён, но не подключён»)

### Точка решения
- `/system/info`: вариант A — фронтенд переходит на `/health` (в service уже есть); вариант B — backend-dev добавляет в service эндпоинт `/api/system/info` (возвращает версию/информацию об окружении, полезен и для HarmonyOS/Flutter, рекомендуется)
- ServiceProxy: вариант A — подключить к admin-эксклюзивным API, нужным admin (например, пересылка журнала аудита); вариант B — удалить класс и обновить документацию «Admin напрямую обращается к service» (фактическая текущая архитектура)

### Выполнено (2026-08-16)
- **`/system/info` → вариант A (фронтенд перешёл на `/health`)** : SystemInfo.vue на нативном axios вызывает `GET /health`, проверяет `checks.database === 'ok'`; маршрут `/health` на стороне service без префикса `/api`, в Vite добавлен прокси `/health`, в Nginx уже существовал `location /health`; мёртвый код в `stores/admin.ts` синхронно изменён на `/health` ✅
- **ServiceProxy → вариант B (сохранить + пояснить в документации)**: класс сохранён как резервная инфраструктура (`ServiceProxy::init()` самодостаточен и безвреден), комментарий в `admin/config/app.php` обновлён на «резервная инфраструктура, в настоящее время активных вызывающих нет» ✅

### Приёмка
- [x] Решение по `/system/info` реализовано: фронтенд убрал вызов (перешёл на /health), фантомных 404-запросов нет ✅
- [x] Решение по ServiceProxy реализовано: класс сохранён, в комментарии config отражено текущее состояние ✅

---

## Task 4: Заполнение документации и унификация формулировок

### Предыстория
- В README «14 контроллеров / 45+ эндпоинтов» устарело (фактически 17 контроллеров / 61 эндпоинт)
- Checkbox'ы фаз в `docs/superpowers/plans/` не заполнены (код реализован, но в документации не отмечено)
- Статус HarmonyOS «UI в стадии планирования» устарел (фактически 6 страниц + ApiClient готовы)
- В install.html / InstallController по умолчанию `.../api/v1`, а в config по умолчанию `/api` (механизм заголовка X-API-Version) — несоответствие
- Комментарий CacheService говорит о двухуровневом кэше, а фактически он трёхуровневый (L1 память / APCu / Redis)

### Files:
- Modify: `README.md` / `README.en.md` (число контроллеров, число эндпоинтов, статус HarmonyOS, уровень кэша)
- Modify: `admin/public/install.html` / `admin/app/controller/InstallController.php` (унификация формулировки префикса версии)
- Modify: `service/support/CacheService.php` (исправление комментария)
- Optional: заполнить checkbox'ы в `docs/superpowers/plans/*.md`

### Выполнено (2026-08-16)
- README.md / README.en.md: 17 контроллеров / 61 эндпоинт / 6 страниц HarmonyOS / 19 Vue-страниц / формулировка прямого доступа SPA — всё обновлено ✅
- install.html / InstallController: значение по умолчанию `/api/v1` → `/api` (механизм заголовка X-API-Version) ✅
- Checkbox'ы 8 фазовых планов заполнены ✅ (кроме phase7, до выполнения)

### Приёмка
- [x] Данные README совпадают с кодом (17 контроллеров / 61 эндпоинт / 6 страниц HarmonyOS) ✅
- [x] Префикс версии в мастере установки согласован с механизмом X-API-Version ✅

---

## Планирование последующих фаз (Phase 8-10, вне данного плана)

| Phase | Содержание | Статус |
|---|---|---|
| Phase 8 | Реализация многоканальных оповещений: в ads-alert новые channel/ (Email SMTP, Webhook, заглушка SMS-шлюза) — закрыть оставшийся разрыв Phase 5 | Ожидает запуска |
| Phase 9 | Реальная интеграция HarmonyOS: 6 страниц подключаются к ApiClient (сейчас 0 реальных вызовов, всё на имитации данных) | Ожидает запуска |
| Phase 10 | Углубление и монетизация: реальная интеграция 29 платформ, визуализация статуса синхронизации, замкнутый цикл данных о конверсиях, CI-сборка Flutter/HarmonyOS, мультитенантные SaaS-квоты | Ожидает запуска |
