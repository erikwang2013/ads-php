# Ads Platform — Многофункциональная система управления рекламой

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## Обзор

**Ads Platform** — это мультиплатформенная система управления рекламой, интегрированная с **29 рекламными платформами** (16 локальных + 13 международных), с единым управлением рекламными кампаниями и кросс-платформенными отчетами.

- **Управление кампаниями** — авторизация OAuth, единое управление кампаниями/группами объявлений/креативами на всех платформах
- **Отчеты** — агрегация кросс-платформенных метрик, экспорт CSV/Excel/PDF, атрибуция по 5 моделям
- **Умный показ** — автоматические ставки, предупреждения о бюджете, календарь кампаний (Gantt), библиотека креативов
- **Глобальное ускорение** — доставка материалов через CDN (мультидрайвер: локально / Alibaba Cloud OSS / Tencent Cloud COS / S3-совместимо, несколько провайдеров настраиваются в админке)
- **Мониторинг и оповещения** — движок правил оповещений, многоканальные push-уведомления, плановая автосинхронизация
- **Доступ с нескольких устройств** — веб-админка (Vue 3), Flutter PC/Mobile, HarmonyOS
- **Стабильность и надежность** — circuit breaker/деградация/таймаут при вызовах платформ, трёхуровневый кэш, оптимизации высокой нагрузки, 22 меры безопасности
- **Интернационализация** — документация на 12 языках, двуязычный интерфейс (ZH/EN)
- **Талисман проекта** — сова «阿鸮», круглосуточная вахта на 29 платформах ([pet.svg](docs/diagrams/svg/pet.svg))

> Архитектура → [docs/architecture.ru.md](docs/architecture.ru.md)  
> Функциональные модули → [docs/features.ru.md](docs/features.ru.md)  
> API-документация → [docs/api.ru.md](docs/api.ru.md) | hg/apidoc: `http://127.0.0.1:8788/apidoc`  
> Сравнение версий → [docs/versions.ru.md](docs/versions.ru.md)（Lite 开源 / Standard & Full 联系 erik@erik.xyz）

### Поддерживаемые платформы

#### Китайские (16)
| Платформа | Адаптер | Аутентификация |
|------|--------|------|
| 巨量引擎 | Juliang | OAuth2 Access-Token |
| 百度营销 | Baidu | OAuth2 + 信封签名 |
| 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5 |
| 腾讯广告 | Tencent | OAuth2 + nonce |
| 快手磁力引擎 | Kuaishou | OAuth2 URL参数 |
| 小红书蒲公英 | Xiaohongshu | OAuth2 Bearer |
| 微博粉丝通 | Weibo | OAuth2 Bearer |
| B站花火 | Bilibili | OAuth2 Bearer |
| 优酷广告 | Youku | OAuth2 + MD5 |
| 美团广告 | Meituan | OAuth2 Bearer |
| 知乎广告 | Zhihu | OAuth2 Bearer |
| 360推广 | Qihoo360 | API Key + Sign |
| 搜狗推广 | Sogou | API Key + Sign |
| 友盟 | Umeng | API Key + MD5 |
| 京东京准通 | Jingdong | OAuth2 + MD5 |
| 拼多多广告 | Pinduoduo | OAuth2 + 自定义Sign |

#### Международные (13)
| Платформа | Адаптер | Аутентификация |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 URL参数 |
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

## Талисман проекта «阿鸮»

<p align="center"><img src="docs/diagrams/svg/pet.svg" width="160" alt="Талисман проекта 阿鸮"></p>

**阿鸮** (xiāo, «сова») — талисман этого проекта: сова. Днём она спит, а ночью не сводит глаз с каждой платформы — ровно то, чем занимается эта система.

| Образ | Смысл | Реализация |
|------|------|---------|
| Взгляд вперёд обоими глазами | Одновременное наблюдение за несколькими клиентами | Vue Admin / Flutter / HarmonyOS — три клиента на одном API |
| Анимация моргания | Периодический опрос | 6 плановых задач (3/5/10/10/15/55 минут) циклического сбора |
| Радарное кольцо за головой | Непрерывное сканирование и обнаружение аномалий | Вычисление движком оповещений + три уровня порогов предупреждения о бюджете |
| Ночная вахта | Тихая охрана, крик только при происшествии | 22 меры защиты работают молча, уведомление отправляется только при срабатывании |

阿鸮 уже стала частью кода: **страница входа** и **favicon** админ-панели, **страница API-документации `/docs`** на стороне Service, а также все страницы документации.

> Исходник SVG [docs/diagrams/svg/pet.svg](docs/diagrams/svg/pet.svg) (без текста, один общий файл для документации на 13 языках; содержит SMIL-анимацию моргания)

---

## Технологический стек

| Слой | Технология | Описание |
|----|------|------|
| Сервер | webman v2 + PHP 8.2+ | 8 плагинов, 76 API-эндпоинтов |
| База данных | MySQL 8.0 | 29 таблиц, префикс ads_, Snowflake BIGINT первичные ключи |
| Кэш | Redis 7 | Трёхуровневый кэш (L1 память/L2 APCu/L3 Redis)、лимитирование запросов、Pub/Sub、очередь сообщений |
| Поиск | Elasticsearch | webman-scout автоматическая синхронизация индексов (настроено) |
| Админ-панель | webman-admin v2 + Vue 3 + TypeScript + Element Plus | PHP-бэкенд (порт 8789), SPA напрямую обращается к бизнес-API (порт 8788), 21 страница, визуализация ECharts |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | PC/Mobile адаптивно, макет Desktop Shell, 12 страниц |
| HarmonyOS | ArkTS + ArkUI | Реализовано 6 страниц, HTTP-клиент готов |
| Деплой | Docker + Nginx + GHCR | Docker Compose одним кликом, GitHub Actions автоматическая сборка и публикация |

## Архитектурная диаграмма

![Архитектура системы](docs/diagrams/svg/architecture.ru.svg)

### Диаграмма потока запросов

![Диаграмма потока запросов](docs/diagrams/svg/request-flow.ru.svg)

### Диаграмма функциональных модулей

![Диаграмма функциональных модулей](docs/diagrams/svg/functional-modules.ru.svg)

### Диаграмма жизненного цикла данных

![Диаграмма жизненного цикла данных](docs/diagrams/svg/data-lifecycle.ru.svg)

> Полная версия со всеми деталями, конвейером Admin, гант-диаграммой задач по расписанию и конечным автоматом кэша → [docs/diagrams/](docs/diagrams/) |

> Подробное описание архитектуры, архитектуры безопасности и высоконагруженного дизайна см. в [документе по архитектуре](docs/architecture.ru.md) | Исторические спецификации дизайна см. в [design.md](docs/superpowers/specs/design.ru.md)

## Описание архитектуры

- **`service/`** — веб-сервис бизнес-API для пользователей на webman v2, слушает порт **8788**. Обрабатывает интеграцию рекламных платформ, OAuth-авторизацию, синхронизацию данных, движок отчетов, мониторинг оповещений и другую бизнес-логику.
- **`admin/`** — отдельная админ-панель на webman-admin v2, слушает порт **8789**. Включает PHP-бэкенд (аутентификация и авторизация, управление пользователями, системные настройки) и SPA-фронтенд на Vue 3.
- **Взаимодействие админ-панели с бизнес-сервисом** — Vue SPA напрямую обращается к service API через axios (baseURL `/api`); маршруты, специфичные для admin (`/api/admin/*`), обслуживаются PHP-бэкендом admin (8789), Nginx распределяет по путям.
- **Режим разработки** — Vite dev server (порт 5173) проксирует `/api` на service:8788; PHP-бэкенд admin на 8789 обеспечивает session-аутентификацию и статическое обслуживание SPA.
- **Производственный режим** — Nginx направляет `/` на admin:8789 (SPA админ-панели), а `/api/` на service:8788 (бизнес-API).

## Интеграция Erik Stack

| Пакет | Назначение |
|----|------|
| `erikwang2013/snowflake-php` | Распределенная генерация Snowflake ID |
| `erikwang2013/hashids` | Шифрование/дешифрование ID-параметров API |
| `erikwang2013/jwt-webman` | Токены аутентификации JWT |
| `erikwang2013/encryption` | Шифрование/дешифрование чувствительных данных на уровне API |
| `erikwang2013/encryptable` | Автоматическое шифрование/дешифрование на уровне полей БД |
| `erikwang2013/webman-scout` | Синхронизация данных Elasticsearch |
| `erikwang2013/season` | Флаги стран |
| `erikwang2013/poster-php` | Слайдер-капча (защита входа) |
| `hg/apidoc` | Автоматическая генерация API-документации (аннотации + Web UI) |

## Интернационализация

Все интерфейсы поддерживают переключение **中文 (zh-CN)** / **English (en)**:

| Клиент | Технология | Способ переключения |
|----|------|---------|
| Admin | vue-i18n v9 | Выпадающее меню языка в TopBar, сохранение в localStorage |
| Service API | `erik\support\I18n` | Заголовок запроса Accept-Language / параметр `?lang=` |
| Flutter | AppLocalizations + Delegate | Автоматическое определение системного языка |
| HarmonyOS | StringResources | Переключение через `setLang()` |

## Безопасность

### Service (14 глобальных слоев + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware（слой маршрутов）

### Admin (10 глобальных слоев + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck（слой маршрутов）

### Обзор защитных возможностей (22 пункта)

| Категория | Защита | Описание |
|------|--------|------|
| Проверка ввода | XSS (11 шаблонов) | script/iframe/event handler/javascript:/data: |
| | Обход путей (7 шаблонов) | ../ / null byte / /etc/passwd / .env / .git |
| | Инъекция заголовков | Обнаружение CRLF |
| | Ограничение размера Body | 10 MiB |
| | Белый список Content-Type | JSON/Form/Multipart/Plain |
| | SQL-инъекция | Обнаружение шаблонов UNION/DROP/ALTER |
| Аутентификация | Привязка JWT Token | Проверка хеша IP + User-Agent |
| | Обновление Token + черный список | Старые токены автоматически недействительны |
| | Троттлинг входа | 5 неудачных попыток → блокировка на 15 минут (Redis) |
| | Ограничение параллельных сессий | Максимум 3 активных токена на пользователя |
| | Капча | Слайдер-капча (действует 5 минут, допуск 5px) |
| Проверка запросов | Белый список CORS | Белый список доменов для продакшена |
| | Проверка Origin/Referer | Проверка междоменного источника |
| | CSRF Token | Проверка session-токена на стороне Admin |
| | Защита от повторных атак | Nonce + Timestamp ±5мин (не для браузерных клиентов) |
| | Лимитирование API | Скользящее окно 60 раз/60с |
| | Защита SSRF | Белый список OAuth redirect_uri |
| Заголовки ответа | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | Защита от кликджекинга + принудительный HTTPS |
| | X-Content-Type-Options | nosniff |
| Защита данных | Шифрование при передаче | EncryptionMiddleware (X-Encrypted) |
| | Шифрование при хранении | Encryptable (на уровне полей БД) |
| | Маскирование логов | password/token/secret → \*\*\* |

### Схема архитектуры безопасности

![Схема архитектуры безопасности](docs/diagrams/svg/security.ru.svg)

**Эшелонированная защита**: внешний слой (Nginx) → входные шлюзы (5 слоев middleware) → аутентификация (7 пунктов) → проверка ввода (4 пункта) → контроль частоты → шифрование данных → аудит и прослеживаемость

**Аутентификация**: сервер и admin единообразно используют таблицу `admin_users` + хеширование bcrypt, JWT 24h + ротация refresh

**Аудит**: все операции записывают IP / User-Agent / Client-Platform / детали операции

**Повторное подтверждение**: для удаления/отвязки/массовых операций применяется режим «ввода слова подтверждения» (`GlobalConfirm` + `useConfirmStore`)

---

## Расширенные функции

| Функция | Описание | Технология |
|------|------|------|
| Библиотека материалов | Загрузка и управление изображениями/видео, предпросмотр в галерее, копирование URL | AssetController + Vue галерея |
| Предупреждение о бюджете | Отслеживание расходования дневного бюджета в реальном времени, трёхуровневые оповещения (50/80/100%) | BudgetAlertService + Cron каждые 15 мин |
| Календарь кампаний | Кросс-платформенная Gantt-диаграмма, месячный/недельный вид, раскраска по платформам | CalendarService + Vue Gantt |
| Кросс-платформенная атрибуция | Атрибуция по 5 моделям (first/last/linear/time_decay/position_based), окно 30 дней | AttributionEngine + ECharts |
| Устойчивость вызовов платформ | Машина состояний circuit breaker для каждой платформы (5 сбоев → OPEN → 30s half-open проба), деградация fast-fail, проверка таймаутов 29 адаптеров | CircuitBreaker + GuardedAdapter |
| CDN-ускорение материалов | Мультидрайвер объектного хранилища (local/oss/cos/s3), управление CDN-провайдерами в админке, предподписанная прямая загрузка, автоматическая очистка кэша при удалении | плагин ads-storage + CdnProviderController |

---

## Высокая нагрузка

| Оптимизация | Решение | Файл |
|------|------|------|
| Разделение чтения и записи БД | Основная БД `shared` + реплика только для чтения `read_replica`, SELECT автоматически направляется на реплику | `config/database.php` |
| Пул соединений БД | Постоянные соединения `PDO::ATTR_PERSISTENT` + прогрев с инициализацией часового пояса | `config/database.php` |
| Пул соединений Redis | Постоянные соединения `persistent` + конфигурация разделения чтения/записи `readonly` | `config/redis.php` |
| Трёхуровневый кэш | L1 память процесса → L2 общая память APCu → L3 Redis | `support/CacheService.php` |
| Асинхронная очередь сообщений | Redis List 4 канала (sync/report/export/notification) | `support/AsyncJobService.php` |
| Многоуровневый лимит Nginx | 30r/s + burst 20 + 20 параллельных соединений + keepalive 32 | `docker/nginx/admin.conf` |
| Горизонтальное масштабирование | Несколько экземпляров upstream + отказоустойчивость + sticky session | `docker/nginx/admin.conf` |
| Ускорение CDN | Статические ресурсы `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## Быстрый старт

### Веб-установка в один клик (рекомендуется)

После запуска сервиса откройте в браузере `/install` — откроется мастер установки:

```bash
# 启动管理后台 (端口 8789)
cd admin && composer install && php start.php start

# 打开浏览器访问 http://localhost:8789/install
# 在安装向导中填写数据库信息、管理员账户，点击「开始安装」
```

Мастер установки проведет вас через следующие шаги:
1. **Подключение к базе данных** — укажите хост MySQL, порт, имя БД, имя пользователя и пароль, доступно тестирование подключения
2. **Настройка Redis** — укажите данные подключения Redis (необязательно)
3. **Учетная запись администратора** — задайте имя пользователя, пароль и отображаемое имя для входа в админ-панель
4. **Установка в один клик** — автоматическое создание БД, выполнение `install.sql` для создания 29 таблиц и записи seed-данных, обновление пароля администратора

После завершения установки откройте `/` для входа в админ-панель, используя заданные имя пользователя и пароль.

### Docker (рекомендуется для продакшена)

```bash
# 启动全部服务 (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# 初始化数据库（创建表 + 种子数据）
make db-init

# 访问
# 管理后台: http://localhost
# 安装向导: http://localhost/install
# API: http://localhost/api/v1（версия фиксируется в пути URL и не передаётся через Header）
```

### Локальная разработка

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

Руководство по использованию → [docs/usage.ru.md](docs/usage.ru.md)
---

## Структура проекта

```
ads-php/
├── service/                           # Пользовательский бизнес-сервис (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (76 эндпоинтов, версионные маршруты /api/v1)
│   │   │   ├── controller/v1/         # 21 контроллер (включая подкаталог admin/)
│   │   │   ├── middleware/            # 15 middleware
│   │   │   ├── config/route.php       # Определение маршрутов
│   │   ├── ads-platform/              # Ядро адаптеров платформ
│   │   │   ├── adapter/               # 29 адаптеров рекламных платформ
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # SQL-миграции + индексы производительности
│   │   ├── ads-account/               # Управление OAuth-аккаунтами
│   │   ├── ads-task/                  # Планировщик задач (6 cron)
│   │   ├── ads-alert/                 # Движок мониторинга оповещений + предупреждения о бюджете
│   │   ├── ads-report/                # Движок отчётов (CSV/Excel/PDF) + движок атрибуции + календарь кампаний
│   │   ├── ads-tenant/                # Мультитенантность
│   │   └── ads-storage/               # Слой абстракции хранилища (local/OSS/COS/S3) + CDN-провайдеры
│   ├── public/                        # Статические ресурсы (встроенная обработка статики webman)
│   │   └── img/pet.svg                # Талисман проекта «阿鸮», показывается на странице /docs
│   ├── scripts/backfill-assets.php    # Перенос существующих материалов в объектное хранилище
│   ├── support/                       # Утилитарные классы Erik Stack
│   │   ├── ControllerTrait.php        # Общий trait контроллеров
│   │   ├── JwtService.php             # Обёртка JWT
│   │   ├── CacheService.php           # Сервис кэша Redis
│   │   ├── ExceptionHandler.php       # Обработчик исключений API
│   │   └── ApiResponse.php            # Единый формат ответа
│   ├── config/                        # Глобальная конфигурация (DB/Redis/Log/Middleware)
│   ├── tests/                         # Тесты PHPUnit (288 tests)
│   │   ├── Unit/                      # Модульные тесты (Middleware, Task, Engines)
│   │   └── Integration/               # Интеграционные тесты (Auth, Health, API)
│   └── start.php                      # Точка входа сервиса
├── admin/                             # Отдельная админ-панель (webman-admin v2 :8789)
│   ├── public/web/
│   │   ├── public/pet.svg             # Талисман проекта «阿鸮», favicon + страница входа
│   │   ├── src/
│   │   │   ├── views/                 # 21 страница Vue
│   │   │   │   ├── dashboard/         # Дашборд (ECharts)
│   │   │   │   ├── campaign/          # Рекламные кампании
│   │   │   │   ├── adgroup/           # Группы объявлений
│   │   │   │   ├── creative/          # Креативы
│   │   │   │   ├── account/           # Управление аккаунтами + привязка
│   │   │   │   ├── asset/             # Библиотека материалов
│   │   │   │   ├── report/            # Аналитика отчётов + экспорт + атрибуция + календарь кампаний
│   │   │   │   ├── alert/             # Правила оповещений + записи
│   │   │   │   ├── notification/      # Центр уведомлений
│   │   │   │   ├── sync/              # Состояние синхронизации
│   │   │   │   ├── bid/               # Правила автобеттинга
│   │   │   │   ├── cdn/               # CDN-провайдеры
│   │   │   │   ├── login/             # Страница входа (阿鸮)
│   │   │   │   └── system/            # Управление пользователями + журнал аудита + системная информация
│   │   │   ├── api/                   # 15 API-клиентов
│   │   │   ├── stores/                # 5 Pinia Store
│   │   │   └── components/            # Общие компоненты (ListPageLayout и др., 8 шт.)
│   │   └── dist/                      # Артефакты сборки (не в репозитории, собирается в CI)
│   ├── app/                           # PHP-бэкенд (controller/middleware)
│   └── config/                        # Конфигурация Admin
├── apps/
│   ├── flutter/                       # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/              # 12 функциональных страниц + Shell-макет
│   │       ├── config/menu_config.dart # Конфигурация двухуровневого меню
│   │       ├── router.dart            # GoRouter (ShellRoute + guard-маршруты)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API Client готов)
├── docker/                            # Конфигурация Docker & Nginx
├── .github/workflows/                 # CI (синтаксис→тесты→TS→Docker) + CD (сборка-публикация)
├── docs/                              # Проектные документы, планы внедрения, Skills
│   ├── architecture.md                # Архитектура (диаграммы архитектуры/потока запросов/безопасности)
│   ├── features.md                    # Функциональный дизайн (21 модуль, схема функциональных модулей)
│   ├── usage.md                       # Руководство по использованию (диаграмма жизненного цикла данных)
│   ├── diagrams/svg/                  # 5 наборов диаграмм × 13 языков + pet.svg (талисман проекта)
│   └── skills/                        # 143 переиспользуемых навыка проекта
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## API-эндпоинты

> Определения всех API-эндпоинтов см. в [docs/api.ru.md](docs/api.ru.md)（включая примеры запросов/ответов, коды ошибок, политику лимитирования）。
> Онлайн-документация hg/apidoc: после запуска сервиса откройте `http://127.0.0.1:8788/apidoc`

## База данных

**Правила именования**: префикс таблиц `ads_`, первичный ключ `BIGINT UNSIGNED PRIMARY KEY`（без автоинкремента, Snowflake ID）, движок InnoDB, кодировка utf8mb4

| Категория | Таблица | Назначение |
|------|------|------|
| Базовые | `ads_tenants` | Мультитенантность |
| Аккаунты | `ads_platform_accounts`, `ads_auth_tokens` | OAuth-аккаунты платформ |
| Кампании | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | Иерархия рекламных кампаний |
| Отчеты | `ads_report_metrics`, `ads_report_extras` | Унифицированные метрики отчетов |
| Материалы | `ads_assets` | Библиотека креативов |
| CDN | `ads_cdn_providers` | Конфигурация CDN-провайдера (учётные данные зашифрованы) |
| Таргетинг | `ads_targeting_templates` | Шаблоны таргетинга аудитории |
| Атрибуция | `ads_conversions`, `ads_attribution_results` | Отслеживание конверсий + результаты атрибуции |
| Ставки | `ads_bid_rules`, `ads_bid_logs` | Правила автобеттинга + история |
| Оповещения | `ads_alert_rules`, `ads_alert_logs` | Мониторинг оповещений |
| Уведомления | `ads_notifications` | Внутренние уведомления |
| Системные | `ads_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | Ошибки синхронизации, RBAC, аудит |

---

## Плановые задачи

| Задача | Частота | Функция |
|------|------|------|
| TokenRefreshTask | Каждые 55 минут | Сканирует просроченные OAuth-токены, автоматически обновляет |
| DataSyncTask | Каждые 10 минут | Тянет планы+группы объявлений+креативы+отчеты со всех платформ, пишет в унифицированные таблицы, очищает кэш |
| AlertCheckTask | Каждые 5 минут | Обходит активные правила оповещений, оценивает пороги, запускает push-уведомления |
| BidCheckTask | Каждые 10 минут | Обходит правила автобеттинга, запрашивает метрики, выполняет корректировку бюджета/включение-отключение |
| BudgetCheckTask | Каждые 15 минут | Обходит активные кампании, отслеживает расход дневного бюджета, трёхуровневые предупреждения (50/80/100%) |
| RetrySyncTask | Каждые 3 минуты | Повторяет неудачные задачи синхронизации (максимум 3 раза, экспоненциальная задержка) |

---

## Тестирование

```bash
cd service && ./vendor/bin/phpunit
# 288 测试 / 862 断言
```

**Покрытие**: 14 Middleware · 8 бизнес-слоёв плагинов (аккаунт/алерт/платформа/отчёт/задача/тенант/хранилище) · Движки (Bid/Alert/Attribution/Report) · Интеграционные тесты API (76 маршрутов) · UI E2E (18 страниц)

```bash
# TypeScript 检查
cd admin/public/web && npx vue-tsc --noEmit   # 零错误

# Dart 分析
cd apps/flutter && dart analyze   # 零错误
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): автоматический конвейер — **PHP Syntax → PHPUnit → TypeScript → Docker Build**

**CD** (`.github/workflows/deploy.yml`): ручной запуск — **Docker Buildx → публикация в GHCR (service/admin/admin-php) → уведомление о деплое**

`.github/dependabot.yml` еженедельно автоматически обновляет зависимости Composer + npm + Docker.

---

## Skills

`docs/skills/` — 11 переиспользуемых навыков проекта:

| Skill | Описание |
|------|------|
| `adapter-generator` | Генерация нового адаптера рекламной платформы (шаблон из 14 методов) |
| `migration-generator` | Генерация SQL-миграций (префикс ads_ + BIGINT PK) |
| `erik-stack` | Руководство по интеграции 8 пакетов Erik Stack |
| `admin-page-generator` | Генерация страниц админ-панели на Vue3 |
| `api-endpoint` | Добавление RESTful API-эндпоинтов |
| `tdd-workflow` | Процесс TDD-проверки (тесты→реализация→синтаксис→TypeScript→коммит) |
| `security-middleware` | Добавление слоя middleware безопасности (спецификация интерфейса + регистрация + ссылки на существующие цепочки) |
| `version-split` | Разделение на версии Lite/Standard/Full (шаги + обновление конфигурации) |
| `cache-strategy` | Стратегия трёхуровневого кэша (L1 память/L2 APCu/L3 Redis + рекомендации по TTL) |
| `attribution-setup` | Кросс-платформенный движок атрибуции (5 моделей + вызовы API + подготовка данных) |
| `high-concurrency` | 8 оптимизаций для высокой нагрузки (разделение чтения/записи/пулы соединений/очередь сообщений/горизонтальное масштабирование/CDN) |


## 开源不易，欢迎支持

| 微信 | 支付宝 |
|:---:|:---:|
| ![微信](./docs/weixinpay.png "微信") | ![支付宝](./docs/alipay.png "支付宝") |

### 全球转账打赏 (Global Transfer Donation)

**收款人信息 (Beneficiary)**

| 字段 | 值 |
|------|-----|
| 收款人姓名 (Name) | WANG KEXUN |
| 收款账户号码 (Account No.) | 881015918251 |

**收款银行 (Receiving Bank) — ZA Bank**

| 字段 | 值 |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| 银行名称 (Bank Name) | ZA Bank Limited |
| 银行编号 (Bank Code) | 387 |
| 银行地址 (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **跨境汇款代理银行（如需，Correspondent Bank）**：此为代理（中转）银行信息，非收款银行信息，请向汇款银行查询是否需要提供。
>
> - **港元、人民币及美元**：Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · 银行编号 006 · Hong Kong Branch（分行编号 391）· Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **其他币种**：THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

### Пожертвование в криптовалюте (Crypto Donation)

Если этот проект помог вам, отсканируйте QR-код, чтобы сделать пожертвование, спасибо!

| Сеть (Network) | QR-код (QR Code) | Адрес кошелька (Wallet Address) |
|---|---|---|
| BNB Smart Chain (BEP20) | [<img src="./coin/1.jpg" width="150" alt="BNB Smart Chain (BEP20)">](./coin/1.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Tron (TRC20) | [<img src="./coin/2.jpg" width="150" alt="Tron (TRC20)">](./coin/2.jpg) | `TEdDHWLajt1XvqtPDWmQctdrJaC3pzZZzz` |
| Ethereum (ERC20) | [<img src="./coin/3.jpg" width="150" alt="Ethereum (ERC20)">](./coin/3.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Aptos | [<img src="./coin/4.jpg" width="150" alt="Aptos">](./coin/4.jpg) | `0x836e3780edfc3f7b2372b39e2a1a3a5d7adfaccd96c726f21cfde1b50dd68030` |
| Plasma | [<img src="./coin/5.jpg" width="150" alt="Plasma">](./coin/5.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Polygon POS | [<img src="./coin/6.jpg" width="150" alt="Polygon POS">](./coin/6.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Solana | [<img src="./coin/7.jpg" width="150" alt="Solana">](./coin/7.jpg) | `2hfhboHdmdrYsY25XfQSsEWxq5ip4EQsR7f4AzSRMUyr` |
| The Open Network (TON) | [<img src="./coin/8.jpg" width="150" alt="The Open Network (TON)">](./coin/8.jpg) | `UQB9kFQohzmXUir9QSSZq01iwl9aQZIDdBpNmDklljRtCoGK` |
| Arbitrum One | [<img src="./coin/9.jpg" width="150" alt="Arbitrum One">](./coin/9.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| AVAX C-Chain | [<img src="./coin/10.jpg" width="150" alt="AVAX C-Chain">](./coin/10.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |

---

## Лицензия

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
