# Отчет по аудиту безопасности и исправлениям Ads-PHP (раунд 3)

[中文](docs/install-review.md) | [English](docs/install-review.en.md) | [한국어](docs/install-review.ko.md) | [Русский](docs/install-review.ru.md) | [Deutsch](docs/install-review.de.md) | [Français](docs/install-review.fr.md) | [Español](docs/install-review.es.md) | [Português](docs/install-review.pt.md) | [हिन्दी](docs/install-review.hi.md) | [العربية](docs/install-review.ar.md) | [বাংলা](docs/install-review.bn.md) | [Bahasa Indonesia](docs/install-review.id.md) | [日本語](docs/install-review.ja.md)

**Время генерации**: 2026-08-04  
**Область проверки**: все middleware безопасности, процесс аутентификации, контроллер установки, файлы конфигурации  
**Версия PHP**: 8.3.7 | **Фреймворк**: webman v2  

---

## I. Обзор исправлений

В этом раунде полностью устранены 6 проблем, обнаруженных во время аудита безопасности раунда 2.

| # | Проблема | Способ исправления | Статус |
|---|------|---------|:--:|
| 1 | На стороне admin отсутствуют 5 middleware безопасности | Созданы CorsMiddleware / SecurityHeadersMiddleware / RateLimitMiddleware / SqlGuardMiddleware / ValidationMiddleware | Исправлено |
| 2 | AuthCheck в admin не выполняет привязку IP/UA | В payload JWT в AuthController добавлены `_ip` / `_ua`, AuthCheck проверяет привязку | Исправлено |
| 3 | Риск ReDoS в AttackGuardMiddleware | Добавлена предварительная проверка `maxStrLen=8192`, сверхдлинные строки отклоняются напрямую | Исправлено |
| 4 | Спецсимволы пароля в InstallController | Добавлен метод `envQuote()`: автоматическое оборачивание в кавычки + экранирование | Исправлено |
| 5 | Неполная конфигурация middleware в admin | Обновлено до стека из 10 глобальных middleware | Исправлено |
| 6 | Устаревшее число слоев middleware в README | Синхронно обновлены китайский и английский README | Исправлено |

---

## II. Проверка синтаксиса

| Файл | Строк | Синтаксис |
|------|------|:--:|
| `admin/app/middleware/CorsMiddleware.php` | 73 | Пройден |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Пройден |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Пройден |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Пройден |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Пройден |
| `admin/app/middleware/AttackGuardMiddleware.php` | 99 | Пройден |
| `admin/app/middleware/AuthCheck.php` | 48 | Пройден |
| `admin/app/controller/AuthController.php` | 194 | Пройден |
| `admin/app/controller/InstallController.php` | 298 | Пройден |
| `admin/config/middleware.php` | 43 | Пройден |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | 99 | Пройден |

---

## III. Стек middleware (после исправлений)

### Сторона Service (14 глобальных слоев + AuthMiddleware)

```
CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform
  → ReplayGuard → Version → RateLimit → LoginThrottle
  → SessionLimit → SQLGuard → Validation → ResponseTime
  → Encryption → AuthMiddleware（路由层）
```

### Сторона Admin (10 глобальных слоев + AuthCheck)

```
CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version
  → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF
  → AuthCheck（路由层）
```

### Матрица маршрутов (после обновления admin)

| Маршрут | CORS | SecHdr | Attack | Platform | Version | RateLimit | LoginThr | SQLGuard | Valid | CSRF | Auth |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/install/check | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| POST /api/install/run | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | skip | — |
| GET /api/install/status | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| POST /api/admin/login | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | skip | — |
| GET /api/admin/roles | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | — | — |
| /api/admin/* (защищенные) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ | ✓ |

---

## IV. Детали улучшений безопасности

### 4.1 Новые middleware в admin

| Middleware | Файл | Назначение |
|--------|------|------|
| CorsMiddleware | `admin/app/middleware/CorsMiddleware.php` | CORS-предзапрос + заголовки ответа, в debug-режиме пропускает все, в продакшене белый список |
| SecurityHeadersMiddleware | `admin/app/middleware/SecurityHeadersMiddleware.php` | X-Content-Type-Options / X-Frame-Options / X-XSS-Protection / HSTS |
| RateLimitMiddleware | `admin/app/middleware/RateLimitMiddleware.php` | Лимитирование скользящим окном в Redis 60req/60s |
| SqlGuardMiddleware | `admin/app/middleware/SqlGuardMiddleware.php` | Обнаружение SQL-инъекций (UNION/DROP/ALTER/комментарии) |
| ValidationMiddleware | `admin/app/middleware/ValidationMiddleware.php` | Очистка ввода trim + strip_tags (исключая description/content/extra) |

### 4.2 Привязка JWT Token

AuthController при входе добавляет в payload JWT `_ip` и `_ua`:

```php
$token = \Erikwang2013\JwtWebman\Jwt::sign([
    '_ip'  => $request->getRealIp(),
    '_ua'  => md5($request->header('User-Agent', '')),
    'uid'  => $user->id,
    'role' => $role->slug ?? '',
    'exp'  => time() + 86400,
]);
```

Middleware AuthCheck при проверке токена сверяет IP и UA, при несовпадении доступ отклоняется.

### 4.3 Защита от ReDoS

В AttackGuardMiddleware (admin + service) добавлено `maxStrLen = 8192`:

```php
protected function detectXss(string $value): bool
{
    if (strlen($value) > $this->maxStrLen) return true;
    foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
    return false;
}
```

### 4.4 Экранирование пароля в .env

В InstallController добавлен метод `envQuote()`, который обнаруживает спецсимволы в пароле (пробелы, `$`, `#`, кавычки, обратный слэш) и автоматически оборачивает в двойные кавычки с экранированием.

---

## V. Список файлов

### Новые (5 файлов)

| Файл | Строк | Описание |
|------|------|------|
| `admin/app/middleware/CorsMiddleware.php` | 73 | CORS middleware |
| `admin/app/middleware/SecurityHeadersMiddleware.php` | 41 | Защитные заголовки ответа |
| `admin/app/middleware/RateLimitMiddleware.php` | 53 | Глобальное лимитирование |
| `admin/app/middleware/SqlGuardMiddleware.php` | 40 | Обнаружение SQL-инъекций |
| `admin/app/middleware/ValidationMiddleware.php` | 39 | Очистка ввода |

### Измененные (6 файлов)

| Файл | Изменение |
|------|------|
| `admin/config/middleware.php` | Глобальные middleware 5→10 слоев |
| `admin/app/middleware/AttackGuardMiddleware.php` | Добавлена защита от ReDoS (maxStrLen) |
| `admin/app/middleware/AuthCheck.php` | Добавлена проверка привязки JWT IP/UA |
| `admin/app/controller/AuthController.php` | В payload JWT добавлены _ip/_ua |
| `admin/app/controller/InstallController.php` | Добавлено экранирование пароля envQuote() |
| `service/plugin/ads-api/middleware/AttackGuardMiddleware.php` | Добавлена защита от ReDoS (maxStrLen) |

---

## VI. Заключение

Все 6 проблем безопасности устранены. Защита на стороне admin увеличена с 5 до 10 слоев глобальных middleware: добавлены защитные заголовки ответа, лимитирование, обнаружение SQL-инъекций, очистка ввода и CORS — 5 ключевых мер. В JWT-токен добавлена проверка привязки IP/UA. Устранены риск ReDoS и проблема спецсимволов пароля в .env. Все файлы проходят проверку синтаксиса PHP.
