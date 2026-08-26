# Phase 3: План реализации расширения адаптеров рекламных платформ

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement.

**Goal:** добавить адаптеры четырёх платформ: Tencent Ads, Umeng, Kuaishou Magnetic Engine, Xiaohongshu Bole.

**Существующие адаптеры (Phase 1+2):** Ocean Engine (巨量引擎), Baidu Marketing, Taobao/Alimama

**Architecture:** каждый адаптер реализует интерфейс `PlatformAdapter` и регистрируется в `AdapterRegistry`, после чего единообразно вызывается процессом OAuth-авторизации, задачами синхронизации данных и фронтенд-админкой.

---

## Task 13: Создание адаптера Tencent Ads

**Файлы:**
- Create: `service/plugin/ads-platform/adapter/Tencent.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Спецификация адаптера

API Tencent Ads (广点通):
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- Способ аутентификации: URL-параметр `access_token` + `nonce`/`timestamp` для защиты от повтора
- Рекламные планы: `campaigns/get` + `campaigns/add` + `campaigns/update`
- Отчёты: `daily_reports/get` (асинхронно: создать задачу→опрос→получить)
- Единица суммы: фэнь (совпадает с единой моделью, конвертация не нужна)
- Сопоставление статусов: `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### Специфичная подпись API Tencent

Tencent использует `access_token` как URL-параметр, MD5-подпись не нужна, но обязательны `nonce` (случайное число) + `timestamp` для защиты от повтора.

```php
protected function request(string $method, string $path, array $params, string $accessToken): array
{
    $url = $this->baseUrl . ltrim($path, '/');
    $params['access_token'] = $accessToken;
    $params['nonce'] = bin2hex(random_bytes(8));
    $params['timestamp'] = time();

    $ch = curl_init();
    if ($method === 'GET') {
        $url .= '?' . http_build_query($params);
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new \RuntimeException('Tencent API network error: ' . $err);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($body, true);
    if ($httpCode !== 200 || ($decoded['code'] ?? -1) !== 0) {
        throw new \RuntimeException(
            'Tencent API error: ' . ($decoded['message'] ?? 'HTTP ' . $httpCode)
        );
    }
    return $decoded;
}
```

**Ключевые моменты сопоставления полей:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget` (единица уже фэнь, конвертация не нужна)
- `configured_status` → `status` (AD_STATUS_NORMAL/SUSPEND/DELETE)
- в отчёте `cost` (фэнь)/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14: Создание адаптера Umeng

**Файлы:**
- Create: `service/plugin/ads-platform/adapter/Umeng.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Спецификация адаптера

Umeng (Umeng U-App + U-Ads):
- API Base: `https://api.open.umeng.com/`
- Способ аутентификации: API Key + API Secret + MD5-подпись
- Umeng фокусируется на **мониторинге эффективности продвижения**, в отличие от платформ размещения рекламы — он не создаёт/не управляет рекламными планами напрямую, а отслеживает данные продвижения по каналам
- capabilities: `['report', 'oauth']` (не поддерживает campaign/create/update/toggle)
- Интерфейс отчётов: `/v1/ad_analytics/report` возвращает данные продвижения по каналам/датам
- fetchCampaigns возвращает пусто (Umeng не создаёт планы сам)
- fetchReports тянет данные об эффективности продвижения и сопоставляет с единой моделью отчётов

### Алгоритм подписи Umeng

```
sign = md5(method + url + body + api_secret)
```

Информация аутентификации передаётся через HTTP-заголовки: `X-Umeng-API-Key`, `X-Umeng-Sign`, `X-Umeng-Timestamp`.

**Ключевые моменты сопоставления полей:**
- `channel` → `platform_campaign_id` (идентификатор канала сопоставляется с измерением плана)
- `pv` → `impressions` (показы)
- `click` → `clicks` (клики)
- `activation` → `conversions` (активации/конверсии)
- единица `cost`: юань → фэнь (×100)

---

## Task 15: Создание адаптера Kuaishou Magnetic Engine

**Файлы:**
- Create: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Спецификация адаптера

Kuaishou Magnetic Engine (Kwai Ads):
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- Способ аутентификации: `access_token` в Header
- Рекламные планы: `/campaign/list` + `/campaign/create` + `/campaign/update`
- Отчёты: `/report/campaign/report` (синхронный возврат)
- Единица суммы: юань → фэнь (×100)

**Ключевые моменты сопоставления полей:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget` (юань→фэнь ×100)
- `put_status` → `status` (1→enabled, 2→paused, 3→deleted)
- в отчёте `charge`→`cost` (юань→фэнь)/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16: Создание адаптера Xiaohongshu Bole

**Файлы:**
- Create: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Спецификация адаптера

Xiaohongshu Bole (платформа Xiaohongshu Jiguang):
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- Способ аутентификации: `access_token` в Header (`Authorization: Bearer xxx`)
- Рекламные планы: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- Отчёты: `/v1/report/campaign/report`
- Единица суммы: фэнь (API Xiaohongshu возвращает фэни, конвертация не нужна)
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**Ключевые моменты сопоставления полей:**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget` (единица: фэнь)
- `status` → `status` (`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted)
- в отчёте `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## Критерии приёмки

1. ✅ Адаптер Tencent Ads реализует все 13 методов PlatformAdapter
2. ✅ Адаптер Umeng реализует возможности report + oauth (Umeng не поддерживает операции размещения)
3. ✅ Адаптер Kuaishou Magnetic Engine реализует все 13 методов
4. ✅ Адаптер Xiaohongshu Bole реализует все 13 методов
5. ✅ Все 4 адаптера зарегистрированы в bootstrap.php
6. ✅ `GET /api/v1/platforms` возвращает 7 платформ (включая прежние 3)
7. ✅ Во всех адаптерах корректная обработка ошибок curl (curl_errno + CURLOPT_CONNECTTIMEOUT)
