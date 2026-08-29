# Versionsvergleich

[中文](docs/versions.md) | [English](docs/versions.en.md) | [한국어](docs/versions.ko.md) | [Русский](docs/versions.ru.md) | [Deutsch](docs/versions.de.md) | [Français](docs/versions.fr.md) | [Español](docs/versions.es.md) | [Português](docs/versions.pt.md) | [हिन्दी](docs/versions.hi.md) | [العربية](docs/versions.ar.md) | [বাংলা](docs/versions.bn.md) | [Bahasa Indonesia](docs/versions.id.md) | [日本語](docs/versions.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

| Version | Lizenz | Bezugsweg |
|------|------|----------|
| **Lite (vereinfachte Version)** | Open Source (MIT) | Öffentliches GitHub-Repository |
| **Standard** | Kommerzielle Lizenz | Kontakt: erik@erik.xyz |
| **Full (vollständige Version)** | Kommerzielle Lizenz | Kontakt: erik@erik.xyz |

---

## Funktionsvergleich

### Basisfunktionen

| Funktion | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Authentifizierung (Login/Token-Refresh/Aktueller Benutzer) | ✅ | ✅ | ✅ |
| Plattformverwaltung (29 Plattformen + OAuth) | ✅ | ✅ | ✅ |
| Kontoverwaltung (CRUD + Synchronisierung) | ✅ | ✅ | ✅ |
| Werbepläne (CRUD + Start/Stopp + Batch) | ✅ | ✅ | ✅ |
| Berichte (Dashboard + Benutzerdefiniert + Export CSV/Excel/PDF) | ✅ | ✅ | ✅ |
| Health-Check + API-Dokumentation + Captcha | ✅ | ✅ | ✅ |
| Datensynchronisierung (Campaign + Report) | ✅ | ✅ | ✅ |

### Schaltungsverwaltung

| Funktion | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Anzeigegruppen (CRUD + Start/Stopp) | — | ✅ | ✅ |
| Werbekreative (Liste + Details) | — | ✅ | ✅ |
| Anzeigegruppen-/Kreativ-Datensynchronisierung | — | ✅ | ✅ |

### Überwachung und Benachrichtigung

| Funktion | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Alarmregel-Engine (7 Kennzahlen/4 Bedingungen/3 Bereiche) | — | ✅ | ✅ |
| Alarmaufzeichnungen + Bestätigung + Ungelesen-Zähler | — | ✅ | ✅ |
| Benachrichtigungszentrum (Liste/Gelesen/Alle gelesen) | — | ✅ | ✅ |

### Erweiterte Funktionen

| Funktion | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Automatische Gebots-Engine (3 Aktionen/Abkühlphase) | — | — | ✅ |
| Zielgruppen-Targeting-Vorlagen (universelles JSON-Schema) | — | — | ✅ |
| Werbematerial-Bibliothek (Upload/Galerie/Vorschau) | — | — | ✅ |
| Budget-Warnung (dreistufig 50/80/100%) | — | — | ✅ |
| Schaltungs-Kalender (Gantt-Visualisierung) | — | — | ✅ |
| Plattformübergreifende Attribution (5 Modelle/30 Tage Rückverfolgung) | — | — | ✅ |

---

## Vergleich der Sicherheitsmaßnahmen

| Schutz | Lite | Standard | Full |
|--------|:---:|:---:|:---:|
| CORS-Whitelist | ✅ | ✅ | ✅ |
| Sicherheits-Response-Header (X-Frame/CSP/HSTS/nosniff) | ✅ | ✅ | ✅ |
| Versions-Routing (X-API-Version) | ✅ | ✅ | ✅ |
| API-Rate-Limiting (gleitendes Fenster) | ✅ | ✅ | ✅ |
| SQL-Injection-Erkennung (Musterabgleich) | ✅ | ✅ | ✅ |
| Eingabefilterung (strip_tags + trim) | ✅ | ✅ | ✅ |
| Transport-Ver-/Entschlüsselung (X-Encrypted) | ✅ | ✅ | ✅ |
| JWT-Bearer-Authentifizierung | ✅ | ✅ | ✅ |
| XSS-Angriffserkennung (11 Muster) | — | ✅ | ✅ |
| Path-Traversal-Erkennung (7 Muster) | — | ✅ | ✅ |
| Header-Injection-Erkennung | — | ✅ | ✅ |
| Body-Größenbegrenzung (10 MiB) | — | ✅ | ✅ |
| Content-Type-Whitelist | — | ✅ | ✅ |
| Client-Quellenerkennung (8 Plattformen) | — | ✅ | ✅ |
| Login-Throttling (5 Fehlversuche → 15 Minuten) | — | ✅ | ✅ |
| Antwortzeit-Überwachung (X-Response-Time) | — | ✅ | ✅ |
| Origin/Referer-Validierung | — | — | ✅ |
| Replay-Schutz (Nonce+Timestamp) | — | — | ✅ |
| Begrenzung paralleler Sitzungen (max. 3) | — | — | ✅ |
| CSRF-Token (Admin-Seite) | — | — | ✅ |
| SSRF-Schutz (OAuth-Whitelist) | — | — | ✅ |
| Log-Datenmaskierung | — | — | ✅ |
| JWT-IP/UA-Bindung | — | — | ✅ |

---

## Vergleich der Middleware-Ketten

### Service-Seite

| Lite (7 Ebenen) | Standard (11 Ebenen) | Full (15 Ebenen) |
|-------------|-----------------|-------------|
| CorsMiddleware | CorsMiddleware | CorsMiddleware |
| — | — | OriginGuardMiddleware |
| SecurityHeadersMiddleware | SecurityHeadersMiddleware | SecurityHeadersMiddleware |
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | ReplayGuardMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |
| RateLimitMiddleware | RateLimitMiddleware | RateLimitMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | — | SessionLimitMiddleware |
| SqlGuardMiddleware | SqlGuardMiddleware | SqlGuardMiddleware |
| ValidationMiddleware | ValidationMiddleware | ValidationMiddleware |
| — | ResponseTimeMiddleware | ResponseTimeMiddleware |
| EncryptionMiddleware | EncryptionMiddleware | EncryptionMiddleware |

### Admin-Seite

| Lite (1 Ebene) | Standard (4 Ebenen) | Full (5 Ebenen) |
|-------------|-----------------|-------------|
| — | AttackGuardMiddleware | AttackGuardMiddleware |
| — | LoginThrottleMiddleware | LoginThrottleMiddleware |
| — | ClientPlatformMiddleware | ClientPlatformMiddleware |
| — | — | CsrfMiddleware |
| VersionMiddleware | VersionMiddleware | VersionMiddleware |

---

## Vergleich der geplanten Aufgaben

| Aufgabe | Häufigkeit | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| TokenRefreshTask | 55min | ✅ | ✅ | ✅ |
| DataSyncTask | 10min | ✅ (nur Campaign+Report) | ✅ (+AdGroup+Creative) | ✅ (+AdGroup+Creative) |
| RetrySyncTask | 3min | ✅ | ✅ | ✅ |
| AlertCheckTask | 5min | — | ✅ | ✅ |
| BidCheckTask | 10min | — | — | ✅ |
| BudgetCheckTask | 15min | — | — | ✅ |

---

## Vergleich der Datenbanktabellen

| Kategorie | Tabellenname | Lite | Standard | Full |
|------|------|:---:|:---:|:---:|
| Basis | ads_tenants | ✅ | ✅ | ✅ |
| Konten | ads_platform_accounts | ✅ | ✅ | ✅ |
| | ads_auth_tokens | ✅ | ✅ | ✅ |
| Schaltung | ads_campaigns | ✅ | ✅ | ✅ |
| | ads_report_metrics | ✅ | ✅ | ✅ |
| | ads_report_extras | ✅ | ✅ | ✅ |
| | ads_ad_groups | — | ✅ | ✅ |
| | ads_creatives | — | ✅ | ✅ |
| Alarm | ads_alert_rules | — | ✅ | ✅ |
| | ads_alert_logs | — | ✅ | ✅ |
| Benachrichtigungen | ads_notifications | — | ✅ | ✅ |
| Gebote | ads_bid_rules | — | — | ✅ |
| | ads_bid_logs | — | — | ✅ |
| Targeting | ads_targeting_templates | — | — | ✅ |
| Material | ads_assets | — | — | ✅ |
| CDN | ads_cdn_providers | — | — | ✅ |
| Attribution | ads_conversions | — | — | ✅ |
| | ads_attribution_results | — | — | ✅ |
| System | ads_sync_errors | ✅ | ✅ | ✅ |
| Verwaltung | admin_users/roles/audit_logs | ✅ | ✅ | ✅ |
| **Gesamt** | | **8** | **13** | **19** |

---

## Vergleich der Frontend-Seiten

### Vue Admin SPA

| Seite | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Login | ✅ | ✅ | ✅ |
| Dashboard | ✅ | ✅ | ✅ |
| Kontoliste + Bindung | ✅ | ✅ | ✅ |
| Werbepläne | ✅ | ✅ | ✅ |
| Berichts-Export | ✅ | ✅ | ✅ |
| Benutzerverwaltung | ✅ | ✅ | ✅ |
| Audit-Log | ✅ | ✅ | ✅ |
| Anzeigegruppen | — | ✅ | ✅ |
| Werbekreative | — | ✅ | ✅ |
| Berichtsanalyse (ECharts) | — | ✅ | ✅ |
| Alarmregeln | — | ✅ | ✅ |
| Alarmaufzeichnungen | — | ✅ | ✅ |
| Benachrichtigungszentrum | — | ✅ | ✅ |
| Automatisches Gebot | — | — | ✅ |
| Materialbibliothek | — | — | ✅ |
| CDN-Anbieter | — | — | ✅ |
| Schaltungs-Kalender | — | — | ✅ |
| Attributionsanalyse | — | — | ✅ |
| **Gesamt** | **7** | **13** | **18** |

### Flutter

| Seite | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| Login | ✅ | ✅ | ✅ |
| Dashboard | ✅ | ✅ | ✅ |
| Werbepläne (Liste+Details) | ✅ | ✅ | ✅ |
| Datenberichte | ✅ | ✅ | ✅ |
| Plattformkonten | ✅ | ✅ | ✅ |
| Alarmverwaltung | ✅ | ✅ | ✅ |
| Anzeigegruppen | — | ✅ | ✅ |
| Werbekreative | — | ✅ | ✅ |
| Berichtsanalyse | — | ✅ | ✅ |
| Benachrichtigungszentrum | — | ✅ | ✅ |
| Automatisches Gebot | — | — | ✅ |
| **Gesamt** | **6** | **10** | **11** |

---

## Vergleich der API-Endpunkte

| Modul | Lite | Standard | Full |
|------|:---:|:---:|:---:|
| System (health/ping/docs/captcha) | 6 | 6 | 6 |
| Authentifizierung (login/me/refresh) | 3 | 3 | 3 |
| Plattform (list/oauthUrl/callback) | 3 | 3 | 3 |
| Konten (index/show/destroy/sync) | 4 | 4 | 4 |
| Werbepläne (CRUD/toggle/batch) | 6 | 6 | 6 |
| Anzeigegruppen (CRUD/toggle) | — | 5 | 5 |
| Kreative (index/show) | — | 2 | 2 |
| Berichte (summary/custom/export×2) | 4 | 4 | 4 |
| Berichte (calendar/budget/attribution/models) | — | — | 4 |
| Alarm (rules CRUD + logs + acknowledge + unread) | — | 7 | 7 |
| Benachrichtigungen (index/unread/read/readAll) | — | 4 | 4 |
| Automatisches Gebot (CRUD + logs) | — | — | 5 |
| Targeting-Vorlagen (CRUD) | — | — | 5 |
| Materialbibliothek (index/upload/show/destroy/presign/register) | — | — | 6 |
| CDN-Anbieter (list/create/update/delete/default/toggle/test/purge) | — | — | 8 |
| **Gesamt** | **26** | **44** | **70** |

---

## Technologie-Stack

Alle drei Versionen teilen sich einen einheitlichen Technologie-Stack:

| Ebene | Technologie |
|----|------|
| Backend-Framework | webman v2, PHP 8.2+ |
| Datenbank | MySQL 8.0 (InnoDB, utf8mb4) |
| Cache | Redis 7 |
| ORM | Illuminate Database (Laravel Eloquent) |
| Authentifizierung | erikwang2013/jwt-webman |
| ID-Generierung | erikwang2013/snowflake-php |
| ID-Codierung | erikwang2013/hashids |
| Frontend | Vue 3 + TypeScript + Element Plus + ECharts + Pinia |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart |
| Deployment | Docker + Nginx + Docker Compose |

---

## Upgrade-Pfad

```
Lite (Open Source)
  │
  ├─→ Upgrade auf Standard (Kontakt: erik@erik.xyz)
  │     │
  │     └─→ Neu: Anzeigegruppen-/Kreativ-Verwaltung, Alarm-Engine, Benachrichtigungszentrum,
  │              AttackGuard/XSS/Path-Traversal/Login-Throttling/Antwortzeit-Überwachung
  │
  └─→ Upgrade auf Full (Kontakt: erik@erik.xyz)
        │
        └─→ Neu: komplettes Standard + automatisches Gebot, Targeting-Vorlagen, Materialbibliothek,
                   Budget-Warnung, Schaltungs-Kalender, plattformübergreifende Attribution, Replay-/Sitzungslimit/CSRF/SSRF
```
