# Phase 9: План реализации реальной интеграции HarmonyOS

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**Goal:** перевести 6 страниц HarmonyOS с имитационных данных на реальные вызовы API (service :8788), исправить проблему жёстко закодированного baseUrl в ApiClient, сделать вход по-настоящему рабочим, превратив клиент HarmonyOS в полноценный третий клиент.

**Источник:** командный аудит Phase 7 (инвентаризация mobile-dev: все 6 страниц HarmonyOS на имитационных данных, 0 реальных вызовов, в ApiClient жёстко закодирован baseUrl `http://127.0.0.1:8788/api`)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## Текущее состояние (проверено)

| Компонент | Статус |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login готовы; baseUrl жёстко закодирован `http://127.0.0.1:8788/api` (в Flutter используется относительный путь `/api` того же источника); вызывающих login() нет |
| `pages/LoginPage.ets` | имитация входа (setTimeout 1s и переход), комментарий «replace with actual API call» |
| `pages/DashboardPage.ets` | метрики жёстко закодированы в `@State` (totalCost=1250000 и т.д.) |
| `pages/CampaignListPage.ets` | L187 комментарий-заглушка `/campaigns` |
| `pages/AccountPage.ets` | L138 комментарий-заглушка `/accounts` |
| `pages/AlertPage.ets` | L146 комментарий-заглушка `/alerts` |
| `pages/ReportPage.ets` | L242 комментарий-заглушка `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric уже есть |
| i18n | StringResources.ets (15+ ключей) |

## Task 1: Расширение ApiClient

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### Ключевые моменты дизайна
- **baseUrl сделать конфигурируемым**: сохранить setBaseUrl, значение по умолчанию остаётся `http://127.0.0.1:8788/api` (на реальном устройстве/эмуляторе нужно указывать адрес локальной сети, пояснить в комментарии); избегать относительного пути как во Flutter (в ArkTS обязателен абсолютный URL)
- **Исправить баг двойного replayHeaders**: `{ ...this.replayHeaders(), ...this.replayHeaders() }` двойное разворачивание (в методе get) → одиночное
- **Адаптировать возвращаемое значение login()**: service `POST /api/auth/login` возвращает `{access_token, token_type, expires_in, user}` (сверить с фактическими полями `service/plugin/ads-api/controller/v1/AuthController.php` — access_token, а не token, после проверки исправить условие `data.token`)
- **Обработка ошибок**: при resp.responseCode не 2xx бросать ошибку/возвращать явное сообщение об ошибке; защита от сбоя JSON.parse
- Сохранить существующую конвенцию: get/post/put/delete возвращают `data.data` (распаковка ApiResponse)

## Task 2: Реальный вход в LoginPage

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### Ключевые моменты дизайна
- `handleLogin()` вызывает `ApiClient.login(username, password)`; успех → setToken + переход на Dashboard; сбой → toast с сообщением об ошибке
- Состояние загрузки isLoading уже есть, переиспользовать
- Сообщение об ошибке приоритетно из возвращённого service message (обёртка ApiResponse), если нет — универсальный текст

## Task 3: Перевод пяти бизнес-страниц на реальные данные

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`, `CampaignListPage.ets`, `AccountPage.ets`, `AlertPage.ets`, `ReportPage.ets`

### Соответствие эндпоинтов (подтверждено аудитом Phase 7, совпадает с исправленным Flutter)
| Страница | Вызов | Разбор |
|---|---|---|
| DashboardPage | `GET /reports/summary` (интервал «сегодня») | `data.overview` → totalCost/total_impressions/avg_ctr и т.д. (суммы в фэнях, formatFen уже есть) |
| CampaignListPage | `GET /campaigns` | `data.list` (пагинация) → модель Campaign |
| AccountPage | `GET /accounts` | `data.list` → модель PlatformAccount |
| AlertPage | `GET /alerts/logs` | `data.list` → поля AlertLog (metric/rule_name/current_value/condition/threshold/status) |
| ReportPage | `GET /reports/custom` (date_start/date_end/dimensions[]/metrics[]) | `data.list` → ReportMetric |

### Ключевые моменты дизайна
- Загрузка страницы (aboutToAppear) инициирует запрос; инициализация данных @State пустыми/0, чтобы не оставалось имитационных значений
- При сбое загрузки показывать ошибку + повтор (по образцу страниц Flutter с ошибкой/повтором)
- Единица суммы: service возвращает числа в фэнях, formatFen уже обрабатывает
- **Новых файлов не добавлять**, сохранить существующую структуру UI страниц и i18n

## Task 4: Проверка

### Приёмка
- [ ] В ApiClient нет двойного replayHeaders, поля возврата login совпадают с AuthController
- [ ] На 6 страницах нет остатков жёстко закодированных имитационных бизнес-данных (проверка grep)
- [ ] Пути вызовов 5 бизнес-страниц один к одному соответствуют маршрутам service (сверка с `service/plugin/ads-api/config/route.php`)
- [ ] Проверка синтаксиса ArkTS (если в данном окружении есть тулчейн hvigor/DevEco — запустить; если нет — пояснить и проверить вручную)
- [ ] Регрессия: PHPUnit service не затронут
