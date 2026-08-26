# Phase 3: Expand Ad Platform Adapters Implementation Plan

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement.

**Goal:** Add adapters for four platforms: Tencent Ads, Umeng, Kuaishou (Kwai), and Xiaohongshu (RED).

**Existing adapters (Phase 1+2):** Juliang (Ocean Engine), Baidu Marketing, Taobao/Alimama

**Architecture:** Each adapter implements the `PlatformAdapter` interface and registers with `AdapterRegistry`, then can be uniformly invoked by the OAuth authorization flow, data sync tasks, and the frontend admin panel.

---

## Task 13: Create the Tencent Ads Adapter

**Files:**
- Create: `service/plugin/ads-platform/adapter/Tencent.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Adapter Spec

Tencent Ads (Guangdiantong) API:
- OAuth URL: `https://developers.e.qq.com/oauth/authorize`
- Token URL: `https://api.e.qq.com/oauth/token`
- API Base: `https://api.e.qq.com/v3.0/`
- Auth: `access_token` URL param + `nonce`/`timestamp` anti-replay
- Campaigns: `campaigns/get` + `campaigns/add` + `campaigns/update`
- Reports: `daily_reports/get` (async: create task → poll → fetch)
- Money unit: fen (consistent with the unified model, no conversion needed)
- Status mapping: `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### Tencent-Specific API Signing

Tencent uses `access_token` as a URL parameter, no MD5 signing required, but needs `nonce` (random) + `timestamp` anti-replay.

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

**Field mapping key points:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget` (already in fen, no conversion)
- `configured_status` → `status` (AD_STATUS_NORMAL/SUSPEND/DELETE)
- In reports: `cost` (fen)/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14: Create the Umeng Adapter

**Files:**
- Create: `service/plugin/ads-platform/adapter/Umeng.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Adapter Spec

Umeng (U-App + U-Ads):
- API Base: `https://api.open.umeng.com/`
- Auth: API Key + API Secret + MD5 signature
- Umeng focuses on **promotion effect tracking**, unlike ad delivery platforms — it does not create/manage campaigns directly, but tracks promotion data from various channels
- capabilities: `['report', 'oauth']` (no campaign/create/update/toggle support)
- Report API: `/v1/ad_analytics/report` returns promotion data by channel/date dimensions
- fetchCampaigns returns empty (Umeng does not create campaigns itself)
- fetchReports maps promotion effect data to the unified report model

### Umeng Signing Algorithm

```
sign = md5(method + url + body + api_secret)
```

Auth info is passed via HTTP headers: `X-Umeng-API-Key`, `X-Umeng-Sign`, `X-Umeng-Timestamp`.

**Field mapping key points:**
- `channel` → `platform_campaign_id` (channel ID mapped to the campaign dimension)
- `pv` → `impressions` (views)
- `click` → `clicks` (clicks)
- `activation` → `conversions` (activations/conversions)
- `cost` unit: yuan → fen (×100)

---

## Task 15: Create the Kuaishou (Kwai) Adapter

**Files:**
- Create: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Adapter Spec

Kuaishou Magnetic Engine (Kwai Ads):
- OAuth URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token URL: `https://api.e.kuaishou.com/oauth/token`
- API Base: `https://api.e.kuaishou.com/v2/`
- Auth: `access_token` Header
- Campaigns: `/campaign/list` + `/campaign/create` + `/campaign/update`
- Reports: `/report/campaign/report` (sync response)
- Money unit: yuan → fen (×100)

**Field mapping key points:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget` (yuan→fen ×100)
- `put_status` → `status` (1→enabled, 2→paused, 3→deleted)
- In reports: `charge`→`cost` (yuan→fen)/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16: Create the Xiaohongshu (RED) Adapter

**Files:**
- Create: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Modify: `service/plugin/ads-platform/config/bootstrap.php`

### Adapter Spec

Xiaohongshu Pugongying (聚光 platform):
- OAuth URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API Base: `https://ark.xiaohongshu.com/api/open/`
- Auth: `access_token` Header (`Authorization: Bearer xxx`)
- Campaigns: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- Reports: `/v1/report/campaign/report`
- Money unit: fen (Xiaohongshu API returns fen, no conversion)
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**Field mapping key points:**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget` (unit: fen)
- `status` → `status` (`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted)
- In reports: `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## Acceptance Criteria

1. ✅ Tencent Ads adapter implements all 13 PlatformAdapter methods
2. ✅ Umeng adapter implements report + oauth capabilities (Umeng does not support delivery operations)
3. ✅ Kuaishou (Kwai) adapter implements all 13 methods
4. ✅ Xiaohongshu (RED) adapter implements all 13 methods
5. ✅ All 4 adapters registered in bootstrap.php
6. ✅ `GET /api/v1/platforms` returns 7 platforms (including the previous 3)
7. ✅ All adapters handle curl errors correctly (curl_errno + CURLOPT_CONNECTTIMEOUT)
