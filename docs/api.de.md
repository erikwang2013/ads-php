# API-Schnittstellendokument

[中文](docs/api.md) | [English](docs/api.en.md) | [한국어](docs/api.ko.md) | [Русский](docs/api.ru.md) | [Deutsch](docs/api.de.md) | [Français](docs/api.fr.md) | [Español](docs/api.es.md) | [Português](docs/api.pt.md) | [हिन्दी](docs/api.hi.md) | [العربية](docs/api.ar.md) | [বাংলা](docs/api.bn.md) | [Bahasa Indonesia](docs/api.id.md) | [日本語](docs/api.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> **hg/apidoc-Online-Dokumentation**: nach dem Dienststart `http://127.0.0.1:8788/apidoc` aufrufen (Service- + Admin-Doppelanwendung umschaltbar)  
> Konfigurationsdatei: `service/config/plugin/hg/apidoc/app.php`

---

## Allgemeine Konventionen

### Base-URL

```
http://your-domain.com/api
```

### Erforderliche Headers

| Header | Wert | Beschreibung |
|--------|----|------|
| `X-API-Version` | `v1` | API-Versionsnummer (erforderlich, erscheint nicht im URL-Pfad) |
| `X-Client-Platform` | `web` / `ios` / `android` / `macos` / `windows` / `linux` / `harmonyos` | Betriebsquell-Endgerät (erforderlich) |
| `Authorization` | `Bearer <token>` | JWT-Authentifizierungstoken (erforderlich außer Login/Plattformliste/Health-Check) |

### Replay-Schutz-Header (nicht-Browser-Seite)

| Header | Beschreibung |
|--------|------|
| `X-Nonce` | Zufallsstring (pro Anfrage eindeutig) |
| `X-Timestamp` | Unix-Zeitstempel in Sekunden (±5 Minuten Fenster) |

### Optionale Headers

| Header | Beschreibung |
|--------|------|
| `X-Tenant-Id` | Tenant-ID (Multi-Tenant-Modus) |
| `X-Encrypted` | `1` = Request-Body muss entschlüsselt, Response-Body verschlüsselt werden |
| `Accept-Language` | `zh-CN` / `en` |

### Content-Type

| Wert | Beschreibung |
|----|------|
| `application/json` | JSON-Request-Body (empfohlen) |
| `application/x-www-form-urlencoded` | Formularanfragen |
| `multipart/form-data` | Datei-Upload |

### Antwortformat

**Erfolg**:
```json
{
  "code": 0,
  "message": "操作成功",
  "data": { ... }
}
```

**Paginierung**:
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

**Fehler**:
```json
{ "code": 401, "message": "Unauthorized" }
```

**Health-Check**:
```json
{ "status": "healthy", "timestamp": "2026-05-22T00:00:00+08:00", "checks": { "database": "ok", "redis": "ok" } }
```

### HTTP-Statuscodes

| Statuscode | Bedeutung |
|--------|------|
| 200 | Erfolg |
| 204 | OPTIONS-Preflight erfolgreich |
| 400 | Fehlerhafte Anfrageparameter, nicht unterstützte API-Version |
| 401 | Nicht authentifiziert, Token abgelaufen, Token-IP/UA-Nichtübereinstimmung |
| 403 | Zugriff verboten (XSS/Path-Traversal/CSRF/SQL-Injection/Origin-Nichtübereinstimmung) |
| 404 | Ressource nicht vorhanden |
| 429 | Zu viele Anfragen (Rate-Limit/Login-Drosselung/Begrenzung paralleler Sessions) |
| 500 | Serverfehler |
| 503 | Service-Degradierung (DB oder Redis nicht verfügbar) |

### Paginierungsparameter

| Parameter | Standardwert | Maximum | Beschreibung |
|------|--------|--------|------|
| `page` | 1 | — | Seitennummer |
| `per_page` | 20 | 100 | Einträge pro Seite (bei Überschreitung automatisch abgeschnitten) |
| `sort` | `id` | — | Sortierfeld (muss in der Whitelist sein) |

### Cache-Strategie

| Endpunkt | TTL | Ebene |
|------|-----|-----|
| `/api/platforms` | 1 Stunde | L1 Speicher → L2 APCu → L3 Redis |
| `/api/accounts` + `/api/accounts/:id` | 5 Minuten | wie oben |
| `/api/reports/summary` | 5 Minuten | wie oben |
| `/api/alerts/rules` | 2 Minuten | wie oben |
| `/api/alerts/unread-count` | 30 Sekunden | wie oben |

---

## Modul 1: System

### GET /health — Health-Check

```
GET /health
```

**Antwort**:
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

- `status`: `healthy` (200) oder `degraded` (503)
- Keine Authentifizierung erforderlich, läuft nicht über das Versions-Routing

---

### GET /ping — Erreichbarkeitsprüfung

```
GET /ping
```

**Antwort**: `{ "pong": true }`

---

### GET /docs — API-Dokumentation

```
GET /docs
```

Gibt die API-Dokumentationsseite im HTML-Format zurück (ohne Authentifizierung).

---

### GET /api/captcha/generate — Captcha generieren

Ohne Authentifizierung.

**Antwort**:
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

- Token 5 Minuten gültig
- Offset-Toleranz 5px

---

### POST /api/captcha/verify — Captcha verifizieren

Ohne Authentifizierung.

**Anfrage**:
```json
{
  "captcha_token": "...",
  "captcha_offset": 120
}
```

**Antwort**: `{ "code": 0, "message": "验证通过" }`

---

## Modul 2: Authentifizierung

### POST /api/auth/login — Login

Ohne Authentifizierung.

**Anfrage**:
```json
{
  "username": "admin",
  "password": "your-password",
  "captcha_token": "...",
  "captcha_offset": 120,
  "tenant_id": 1
}
```

**Antwort**:
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

- JWT-Token 24 Stunden gültig
- Token enthält eingebetteten IP- + User-Agent-Hash
- 5 Fehlversuche → Redis-Sperre 15 Minuten

---

### GET /api/auth/me — Aktueller Benutzer

**Request-Header**: `Authorization: Bearer <token>`

**Antwort**:
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

### POST /api/auth/refresh — Token aktualisieren

**Request-Header**: `Authorization: Bearer <old_token>`

**Antwort**:
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

- Alte Token werden automatisch zur Blacklist hinzugefügt
- Max. 3 aktive Token pro Benutzer

---

## Modul 3: Plattform & Konten

### GET /api/platforms — Plattformliste

Ohne Authentifizierung. 1 Stunde gecacht.

**Antwort**:
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

### GET /api/platforms/:code/oauth-url — OAuth-Autorisierungs-URL

**Parameter**: `?redirect_uri=https://your-domain.com/callback`

**Antwort**: `{ "code": 0, "data": { "auth_url": "https://...", "state": "random-state" } }`

- `redirect_uri` muss die SSRF-Whitelist-Prüfung bestehen (Umgebungsvariable `OAUTH_ALLOWED_REDIRECTS`)

---

### POST /api/platforms/:code/callback — OAuth-Callback

**Anfrage**: `{ "state": "...", "code": "..." }`

**Antwort**: `{ "code": 0, "data": { "account_id": "hashids-encoded" } }`

---

### GET /api/accounts — Kontoliste

5 Minuten gecacht.

**Parameter**:

| Parameter | Beschreibung |
|------|------|
| `platform` | Plattformcode-Filter |
| `page` | Seitennummer |
| `per_page` | Einträge pro Seite |

**Antwort**: Paginierungsformat, jeder Eintrag in `list` enthält `id`(hashids), `platform`, `account_name`, `status`, `sync_enabled`, `last_sync_at`

---

### GET /api/accounts/:id — Kontodetails

5 Minuten gecacht.

---

### DELETE /api/accounts/:id — Konto entbinden

---

### POST /api/accounts/:id/sync — Manuelle Synchronisierung

---

## Modul 4: Werbepläne

### GET /api/campaigns — Planliste

**Parameter**:

| Parameter | Beschreibung | Mögliche Werte |
|------|------|--------|
| `platform` | Plattformfilter | juliang, meta, google... |
| `status` | Statusfilter | enabled, paused |
| `keyword` | Namenssuche | beliebiger Text |
| `sort` | Sortierfeld | id, name, platform, daily_budget, status, created_at |
| `page` | Seitennummer | — |
| `per_page` | Einträge pro Seite | ≤100 |

**Antwort**: Paginierungsformat + `summary: { total_cost, total_impressions, total_clicks, avg_ctr, avg_cvr }`

---

### POST /api/campaigns — Plan erstellen

**Anfrage**:
```json
{
  "platform": "juliang",
  "platform_account_id": "hashids-encoded-account-id",
  "name": "测试计划",
  "daily_budget": 20000
}
```

**Antwort**: `{ "code": 0, "data": { "id": "hashids-encoded", "platform_campaign_id": "platform-side-id" } }`

- `daily_budget` Einheit: Fen (20000 = ¥200.00)

---

### GET /api/campaigns/:id — Plandetails

**Antwort**: `{ "code": 0, "data": { "campaign": {...}, "today": { "cost":..., "impressions":... } } }`

---

### PUT /api/campaigns/:id — Plan aktualisieren

**Anfrage**: `{ "name": "新名称", "daily_budget": 30000 }`

---

### POST /api/campaigns/:id/toggle — Plan starten/stoppen

**Anfrage**: `{ "enabled": false }`

---

### POST /api/campaigns/batch/toggle — Batch starten/stoppen

**Anfrage**: `{ "ids": ["hash1", "hash2", "hash3"], "enabled": false }`

**Antwort**: `{ "code": 0, "data": { "success": 3, "failed": 0, "total": 3 } }`

---

## Modul 5: Anzeigegruppen

### GET /api/ad-groups — Anzeigegruppenliste

**Parameter**: `platform`, `campaign_id`, `status`, `sort`(id/name/status/bid_amount), `page`, `per_page`

### POST /api/ad-groups — Anzeigegruppe erstellen

**Anfrage**:
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

- `targeting_template_id`: optional, lädt das Targeting-JSON aus der Vorlage und führt es zusammen

### GET /api/ad-groups/:id — Anzeigegruppen-Details

### PUT /api/ad-groups/:id — Anzeigegruppe aktualisieren

### POST /api/ad-groups/:id/toggle — Anzeigegruppe starten/stoppen

---

## Modul 6: Kreative

### GET /api/creatives — Kreativliste

**Parameter**: `platform`, `ad_group_id`, `campaign_id`, `media_type`(image/video/text), `sort`, `page`, `per_page`

### GET /api/creatives/:id — Kreativ-Details

---

## Modul 7: Berichte

### GET /api/reports/summary — Dashboard-Zusammenfassung

5 Minuten gecacht.

**Parameter**: `date_start`, `date_end`

**Antwort**:
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

### GET /api/reports/custom — Benutzerdefinierter Bericht

**Parameter**:

| Parameter | Beschreibung |
|------|------|
| `dimensions[]` | Dimensionen: date, platform, campaign |
| `metrics[]` | Kennzahlen: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi |
| `date_start` | Startdatum |
| `date_end` | Enddatum |
| `platform` | Plattformfilter |

---

### GET /api/reports/export — Bericht exportieren

**Parameter**: `format=csv`, `date_start`, `date_end`, `metrics[]`

Gibt einen Dateidownload zurück (CSV UTF-8 BOM oder Excel .xls).

---

### GET /api/reports/export-dashboard — Dashboard als PDF exportieren

---

### GET /api/reports/calendar — Schaltungs-Kalender

**Parameter**: `date_start`, `date_end`, `platform`

**Antwort**: `[{ id, name, platform, status, start_date, end_date, budget }]`

---

### GET /api/reports/budget-alerts — Budget-Warnung

**Antwort**: `[{ campaign_id, campaign_name, platform, spent, budget, pct, level }]`

- `level`: yellow (≥50%), orange (≥80%), red (≥100%)

---

### GET /api/reports/attribution — Attributionsanalyse

**Parameter**: `model`(first_touch/last_touch/linear/time_decay/position_based), `date_start`, `date_end`

**Antwort**:
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

### GET /api/reports/attribution/models — Attributionsmodell-Liste

**Antwort**: `[{ code: "last_touch", name: "末次触点", description: "..." }]`

Insgesamt 5 Modelle.

---

## Modul 8: Alarm

### GET /api/alerts/rules — Alarmregel-Liste

2 Minuten gecacht.

**Parameter**: `platform`, `enabled`(0/1), `metric`, `page`, `per_page`

### POST /api/alerts/rules — Alarmregel erstellen

**Anfrage**:
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

### PUT /api/alerts/rules/:id — Alarmregel aktualisieren

### DELETE /api/alerts/rules/:id — Alarmregel löschen

### GET /api/alerts/logs — Alarmaufzeichnungen

**Parameter**: `status`, `rule_id`, `metric`, `page`, `per_page`

### POST /api/alerts/logs/:id/acknowledge — Alarm bestätigen

### GET /api/alerts/unread-count — Anzahl ungelesener Alarme

30 Sekunden gecacht. Frontend pollt alle 30s.

---

## Modul 9: Benachrichtigungen

### GET /api/notifications — Benachrichtigungsliste

**Parameter**: `type`(alert/system), `is_read`(0/1), `page`, `per_page`

### GET /api/notifications/unread-count — Anzahl ungelesener Benachrichtigungen

### POST /api/notifications/:id/read — Als gelesen markieren

### POST /api/notifications/read-all — Alle als gelesen markieren

---

## Modul 10: Automatisches Gebot

### GET /api/bid-rules — Regelliste

### POST /api/bid-rules — Regel erstellen

**Anfrage**:
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

**Feldbeschreibungen**:

| Feld | Typ | Beschreibung |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Überwachte Kennzahl |
| condition | gt/gte/lt/lte | Auslösebedingung |
| threshold | decimal | Schwellenwert |
| action_type | adjust_budget/toggle_pause/toggle_enable | Aktionstyp |
| adjust_step | int (Fen) | Budget-Anpassungsschritt (positiv = erhöhen, negativ = senken) |
| budget_min | int | Budgetuntergrenze (Fen) |
| budget_max | int | Budgetobergrenze (Fen) |
| cooldown_minutes | int | Abkühlzeit (Standard 60) |

### PUT /api/bid-rules/:id — Regel aktualisieren

### DELETE /api/bid-rules/:id — Regel löschen

### GET /api/bid-rules/logs — Gebotshistorie

**Parameter**: `rule_id`, `campaign_id`

---

## Modul 11: Targeting-Vorlagen

### GET /api/targeting-templates — Vorlagenliste

**Parameter**: `platform`

### GET /api/targeting-templates/:id — Vorlagendetails

### POST /api/targeting-templates — Vorlage erstellen

**Anfrage**:
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

### PUT /api/targeting-templates/:id — Vorlage aktualisieren

### DELETE /api/targeting-templates/:id — Vorlage löschen

---

## Modul 12: Materialbibliothek

### GET /api/assets — Materialliste

**Parameter**: `type`(image/video), `page`, `per_page`

### POST /api/assets/upload — Material hochladen

**Anfrage**: `multipart/form-data`, Feld `file`

- Bilder: max. 5 MB (jpeg/png/gif/webp)
- Videos: max. 50 MB (mp4)

**Antwort**: `{ "code": 0, "data": { "id": "hashids", "url": "/uploads/assets/20260522/abc123.jpg", "type": "image" } }`

### GET /api/assets/:id — Materialdetails

### DELETE /api/assets/:id — Material löschen

---

## Admin-Endpunkte (Port 8789)

### POST /api/admin/login — Admin-Login

**Anfrage**: `{ "username": "admin", "password": "..." }`

**Antwort**: `{ "code": 0, "data": { "access_token": "...", "user": {...}, "csrf_token": "..." } }`

- Token wird in localStorage gespeichert
- `csrf_token` muss bei späteren POST/PUT/DELETE-Anfragen im `X-CSRF-Token`-Header mitgesendet werden

### GET /api/admin/me — Aktueller Administrator

### POST /api/admin/logout — Abmelden

### GET /api/admin/users — Benutzerliste

**Parameter**: `keyword`, `role_id`, `page`, `per_page`

`id` und `role_id` in der Antwort sind hashids-codiert.

### POST /api/admin/users — Benutzer erstellen

### PUT /api/admin/users/:id — Benutzer aktualisieren

### DELETE /api/admin/users/:id — Benutzer deaktivieren

### GET /api/admin/users/roles — Rollenliste

### GET /api/admin/audit-logs — Audit-Log

**Parameter**: `user_id`, `action`, `date_from`, `date_to`, `page`, `per_page`

---

## Fehlercode-Referenz

| code | HTTP | Beschreibung |
|------|------|------|
| 0 | 200 | Erfolg |
| 1 | 200/400 | Allgemeiner Geschäftsfehler |
| 401 | 401 | Nicht authentifiziert / Token abgelaufen / IP/UA-Nichtübereinstimmung |
| 403 | 403 | Zugriff verboten (Sicherheitsabfang) |
| 404 | 404 | Ressource nicht vorhanden |
| 422 | 422 | Parametervalidierung fehlgeschlagen |
| 429 | 429 | Zu viele Anfragen / Login-Drosselung / Parallelitätsbegrenzung |
| 1001 | 200 | Authentifizierung fehlgeschlagen (Benutzername oder Passwort falsch) |

---

## Sicherheitsabfang-Antworten

Wenn eine Anfrage von der Sicherheits-Middleware abgefangen wird, wird 403 zurückgegeben:

```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
{ "code": 403, "message": "Forbidden: Path traversal detected" }
{ "code": 403, "message": "Forbidden: Header injection detected in: User-Agent" }
{ "code": 403, "message": "Forbidden: CSRF token mismatch" }
{ "code": 403, "message": "Forbidden: HTTP method TRACE is not allowed" }
```

## Rate-Limit-Antwort

```json
{ "code": 429, "message": "Too many requests. Retry after 15s" }
```

Der `Retry-After`-Header enthält die verbleibende Wartezeit in Sekunden.
