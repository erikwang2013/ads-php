# Phase 3: Implementierungsplan zur Erweiterung der Werbeplattform-Adapter

[中文](docs/superpowers/plans/2026-05-15-phase3-more-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase3-more-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase3-more-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase3-more-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase3-more-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase3-more-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase3-more-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase3-more-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase3-more-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase3-more-adapters.ja.md)

> **Für agentic workers:** ERFORDERLICHE SUB-SKILL: superpowers:subagent-driven-development zur Implementierung verwenden.

**Ziel:** Adapter für die vier Plattformen 腾讯广告 (Tencent Ads), 友盟 (Umeng), 快手磁力引擎 (Kuaishou), 小红书蒲公英 (Xiaohongshu) neu hinzufügen.

**Bestehende Adapter (Phase 1+2):** 巨量引擎, 百度营销, 淘宝/阿里妈妈

**Architektur:** Jeder Adapter implementiert das `PlatformAdapter`-Interface und wird in der `AdapterRegistry` registriert; damit kann er von OAuth-Autorisierungsablauf, Datensynchronisierungsaufgaben und Frontend-Admin-Backend einheitlich aufgerufen werden.

---

## Task 13: Tencent-Ad-Adapter erstellen

**Dateien:**
- Erstellen: `service/plugin/ads-platform/adapter/Tencent.php`
- Ändern: `service/plugin/ads-platform/config/bootstrap.php`

### Adapter-Spezifikation

Tencent-Ad (广点通) API:
- OAuth-URL: `https://developers.e.qq.com/oauth/authorize`
- Token-URL: `https://api.e.qq.com/oauth/token`
- API-Basis: `https://api.e.qq.com/v3.0/`
- Authentifizierung: `access_token` URL-Parameter + `nonce`/`timestamp` gegen Replay-Angriffe
- Werbepläne: `campaigns/get` + `campaigns/add` + `campaigns/update`
- Berichte: `daily_reports/get` (asynchron: Aufgabe erstellen→polling→abrufen)
- Betragseinheit: Fen (konsistent mit dem einheitlichen Modell, keine Umrechnung nötig)
- Statuszuordnung: `AD_STATUS_NORMAL`→enabled, `AD_STATUS_SUSPEND`→paused, `AD_STATUS_DELETE`→deleted

### Tencent-spezifische API-Signatur

Tencent verwendet `access_token` als URL-Parameter, keine MD5-Signatur nötig, aber `nonce` (Zufallszahl) + `timestamp` gegen Replay-Angriffe.

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

**Feldzuordnungs-Highlights:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `daily_budget` → `daily_budget` (Einheit bereits Fen, keine Umrechnung nötig)
- `configured_status` → `status` (AD_STATUS_NORMAL/SUSPEND/DELETE)
- In Berichten `cost` (Fen)/`view_count`→`impressions`/`valid_click_count`→`clicks`/`conversions_count`→`conversions`

---

## Task 14: Umeng-Adapter erstellen

**Dateien:**
- Erstellen: `service/plugin/ads-platform/adapter/Umeng.php`
- Ändern: `service/plugin/ads-platform/config/bootstrap.php`

### Adapter-Spezifikation

友盟 (Umeng U-App + U-Ads):
- API-Basis: `https://api.open.umeng.com/`
- Authentifizierung: API Key + API Secret + MD5-Signatur
- Umeng liegt der Schwerpunkt auf **Werbungswirkungs-Messung**, anders als Werbeplattformen — es erstellt/verwaltet keine Werbepläne direkt, sondern verfolgt die Werbedaten der einzelnen Kanäle
- capabilities: `['report', 'oauth']` (unterstützt kein campaign/create/update/toggle)
- Berichtsschnittstelle: `/v1/ad_analytics/report` liefert Werbedaten nach Kanal/Datum
- fetchCampaigns liefert leer (Umeng erstellt keine eigenen Pläne)
- fetchReports zieht Wirkungsdaten und bildet sie auf das einheitliche Berichtsmodell ab

### Umeng-Signaturalgorithmus

```
sign = md5(method + url + body + api_secret)
```

Authentifizierungsinformationen über HTTP-Header übertragen: `X-Umeng-API-Key`, `X-Umeng-Sign`, `X-Umeng-Timestamp`.

**Feldzuordnungs-Highlights:**
- `channel` → `platform_campaign_id` (Kanal-Kennung auf Pläne-Ebene abbilden)
- `pv` → `impressions` (Impressionen)
- `click` → `clicks` (Klicks)
- `activation` → `conversions` (Aktivierungen/Konversionen)
- `cost` Einheit: Yuan → Fen (×100)

---

## Task 15: Kuaishou-Adapter erstellen

**Dateien:**
- Erstellen: `service/plugin/ads-platform/adapter/Kuaishou.php`
- Ändern: `service/plugin/ads-platform/config/bootstrap.php`

### Adapter-Spezifikation

快手磁力引擎 (Kwai Ads):
- OAuth-URL: `https://developers.e.kuaishou.com/oauth/authorize`
- Token-URL: `https://api.e.kuaishou.com/oauth/token`
- API-Basis: `https://api.e.kuaishou.com/v2/`
- Authentifizierung: `access_token` Header
- Werbepläne: `/campaign/list` + `/campaign/create` + `/campaign/update`
- Berichte: `/report/campaign/report` (synchron)
- Betragseinheit: Yuan → Fen (×100)

**Feldzuordnungs-Highlights:**
- `campaign_id` → `platform_campaign_id`
- `campaign_name` → `name`
- `day_budget` → `daily_budget` (Yuan→Fen ×100)
- `put_status` → `status` (1→enabled, 2→paused, 3→deleted)
- In Berichten `charge`→`cost` (Yuan→Fen)/`impression`→`impressions`/`click`→`clicks`/`action_count`→`conversions`

---

## Task 16: Xiaohongshu-Adapter erstellen

**Dateien:**
- Erstellen: `service/plugin/ads-platform/adapter/Xiaohongshu.php`
- Ändern: `service/plugin/ads-platform/config/bootstrap.php`

### Adapter-Spezifikation

小红书蒲公英 (Xiaohongshu 聚光平台):
- OAuth-URL: `https://ark.xiaohongshu.com/oauth/authorize`
- Token-URL: `https://ark.xiaohongshu.com/api/open/oauth2/token`
- API-Basis: `https://ark.xiaohongshu.com/api/open/`
- Authentifizierung: `access_token` Header (`Authorization: Bearer xxx`)
- Werbepläne: `/v1/campaign/list` + `/v1/campaign/create` + `/v1/campaign/update`
- Berichte: `/v1/report/campaign/report`
- Betragseinheit: Fen (Xiaohongshu-API liefert Fen, keine Umrechnung nötig)
- capabilities: `['report', 'campaign', 'creative', 'oauth']`

**Feldzuordnungs-Highlights:**
- `id` → `platform_campaign_id`
- `name` → `name`
- `day_budget` → `daily_budget` (Einheit: Fen)
- `status` → `status` (`CAMPAIGN_STATUS_ENABLE`→enabled, `CAMPAIGN_STATUS_DISABLE`→paused, `CAMPAIGN_STATUS_DELETE`→deleted)
- In Berichten `spend`→`cost`/`impression`→`impressions`/`click`→`clicks`/`conversion`→`conversions`

---

## Abnahmekriterien

1. ✅ Tencent-Ad-Adapter implementiert alle 13 PlatformAdapter-Methoden
2. ✅ Umeng-Adapter implementiert report + oauth-Fähigkeiten (Umeng unterstützt keine Werbeoperationen)
3. ✅ Kuaishou-Adapter implementiert alle 13 Methoden
4. ✅ Xiaohongshu-Adapter implementiert alle 13 Methoden
5. ✅ Alle 4 Adapter sind in bootstrap.php registriert
6. ✅ `GET /api/v1/platforms` liefert 7 Plattformen (inkl. der vorherigen 3)
7. ✅ Alle Adapter-Curl-Aufrufe mit korrekter Fehlerbehandlung (curl_errno + CURLOPT_CONNECTTIMEOUT)
