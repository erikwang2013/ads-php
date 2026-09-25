# Ads Platform — Multi-Plattform-Werbe-Verwaltungssystem

[中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## Projekt-Maskottchen „阿章"

<p align="center"><img src="docs/diagrams/svg/pet.svg" width="160" alt="Projekt-Maskottchen 阿章"></p>

**阿章** (zhāng, „Krake") ist das Maskottchen dieses Projekts: ein Krake. Seine acht Arme kümmern sich jeweils um ihre eigene Aufgabe und reichen gleichzeitig hinaus zu 29 Plattformen — genau das tut dieses System.

| Figur | Bedeutung | Entsprechende Umsetzung |
|------|------|---------|
| Acht Arme | Acht Plugins, jedes mit eigener Aufgabe, alle parallel | ads-account / ads-alert / ads-api / ads-platform / ads-report / ads-storage / ads-task / ads-tenant |
| Saugnäpfe der Arme | Daten bleiben hängen und werden unterwegs gebündelt | 29 Plattformen → eine einheitliche `/api/v1` |
| Beide Augen nach vorn | Mehrere Endgeräte gleichzeitig im Blick | Vue Admin / Flutter / HarmonyOS nutzen gemeinsam eine API |
| Blinzeln + Atmen | Zyklisches Polling und stille Wache | 6 geplante Aufgaben (3/5/10/10/15/55 Minuten) sammeln in Schleife; 22 Schutzmaßnahmen laufen still |
| 29 Knoten im äußeren Ring | 29 Plattformen dauerhaft online | 16 Inland + 13 international, jede einzelne im Blick |

阿章 ist bereits im Code integriert: **Login-Seite** und **favicon** des Verwaltungs-Backends, die **`/docs`-API-Dokumentationsseite** des Service sowie alle Dokumentationsseiten.

> SVG-Quellcode [docs/diagrams/svg/pet.svg](docs/diagrams/svg/pet.svg) (ohne Text, alle 13 Sprachversionen der Dokumentation nutzen dieselbe Datei; mit SMIL-Animation zum Blinzeln und Atmen)

---

## Übersicht

**Ads Platform** ist ein Multi-Plattform-Werbesystem, das **29 Werbeplattformen** (16 inländische + 13 internationale) anbindet und Werbeschaltung sowie plattformübergreifende Datenberichte einheitlich verwaltet.

- **Kampagnenverwaltung** — OAuth-Kontoauthorisierung, einheitliche Verwaltung von Kampagnen/Anzeigengruppen/Anzeigen über Plattformen hinweg
- **Berichte** — plattformübergreifende Metrikaggregation, CSV/Excel/PDF-Export, 5-Modell-Attribution
- **Intelligente Auslieferung** — automatisches Gebot, Budgetwarnungen, Werbekalender (Gantt), Asset-Bibliothek
- **Globale Beschleunigung** — Asset-Auslieferung über CDN (Multi-Treiber: lokal / Alibaba Cloud OSS / Tencent Cloud COS / S3-kompatibel, mehrere Anbieter in der Verwaltung konfigurierbar)
- **Überwachung & Warnungen** — Warnregel-Engine, Mehrkanal-Push, geplante automatische Synchronisierung
- **Multi-Geräte-Zugriff** — Web-Admin (Vue 3), Flutter PC/Mobile, HarmonyOS
- **Stabilität & Zuverlässigkeit** — Circuit Breaker/Degradierung/Timeout für Plattformaufrufe, 3-stufiger Cache, Hochparallelitäts-Optimierungen, 22 Sicherheitsmaßnahmen
- **Internationalisierung** — Dokumentation in 12 Sprachen, zweisprachige Oberfläche (ZH/EN)
- **Projekt-Maskottchen** — der Krake „阿章", acht Arme, 24 Stunden im Einsatz für 29 Plattformen ([pet.svg](docs/diagrams/svg/pet.svg))

> Architekturentwurf → [docs/architecture.md](docs/architecture.de.md)  
> Funktionsmodule → [docs/features.md](docs/features.de.md)  
> API-Dokumentation → [docs/api.md](docs/api.de.md) | hg/apidoc: `http://127.0.0.1:8788/apidoc`  
> Versionsvergleich → [docs/versions.md](docs/versions.de.md)（Lite Open Source / Standard & Full Kontakt erik@erik.xyz）

### Unterstützte Plattformen

#### Inland (16)
| Plattform | Adapter | Authentifizierung |
|------|--------|------|
| 巨量引擎 | Juliang | OAuth2 Access-Token |
| 百度营销 | Baidu | OAuth2 + Umschlagsignatur |
| 淘宝/阿里妈妈 | Taobao | OAuth2 + MD5 |
| 腾讯广告 | Tencent | OAuth2 + nonce |
| 快手磁力引擎 | Kuaishou | OAuth2 URL-Parameter |
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
| 拼多多广告 | Pinduoduo | OAuth2 + benutzerdefiniertes Sign |

#### International (13)
| Plattform | Adapter | Authentifizierung |
|------|--------|------|
| Google Ads | Google | OAuth2 + GAQL |
| YouTube Ads | Youtube | OAuth2 + GAQL |
| Meta Ads | Meta | OAuth2 URL-Parameter |
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

## Technologie-Stack

| Ebene | Technologie | Beschreibung |
|----|------|------|
| Server | webman v2 + PHP 8.4+ | 8 Plugins, 76 API-Endpunkte |
| Datenbank | MySQL 8.0 | 29 Tabellen, ads_-Präfix, Snowflake-BIGINT-Primärschlüssel |
| Cache | Redis 7 | Drei-Stufen-Cache (L1 Speicher/L2 APCu/L3 Redis), Ratenbegrenzungszähler, Pub/Sub, Nachrichtenwarteschlange |
| Suche | Elasticsearch | webman-scout automatische Indexsynchronisierung (konfiguriert) |
| Verwaltungs-Backend | webman-admin v2 + Vue 3 + TypeScript + Element Plus | PHP-Backend (Port 8789), SPA-Direktverbindung zur Geschäfts-API (Port 8788), 21 Seiten, ECharts-Visualisierung |
| Flutter | Dart 3 + Riverpod + GoRouter + fl_chart | PC/Mobile responsiv, Desktop-Shell-Layout, 12 Seiten |
| HarmonyOS | ArkTS + ArkUI | 6 Seiten implementiert, HTTP-Client bereit |
| Bereitstellung | Docker + Nginx + GHCR | Docker-Compose-Ein-Klick-Start, GitHub Actions automatischer Build und Push |

## Architekturdiagramm

![Systemarchitektur-Diagramm](docs/diagrams/svg/architecture.de.svg)

### Anfrageablauf-Diagramm

![Anfrageablauf-Diagramm](docs/diagrams/svg/request-flow.de.svg)

### Funktionsmodul-Diagramm

![Funktionsmodul-Diagramm](docs/diagrams/svg/functional-modules.de.svg)

### Datenlebenszyklus-Diagramm

![Datenlebenszyklus-Diagramm](docs/diagrams/svg/data-lifecycle.de.svg)

> Die Vollversion enthält alle Detailbeschriftungen, Admin-End-Pipelines, Cron-Gantt-Diagramm und Cache-Zustandsmaschine → [docs/diagrams/](docs/diagrams/) |

> Detaillierte Architekturbeschreibung, Sicherheitsarchitektur und Hochverfügbarkeitsdesign siehe [Architekturentwurfsdokument](docs/architecture.de.md) | Historische Designspezifikationen siehe [design.md](docs/superpowers/specs/design.de.md)

## Architekturbeschreibung

- **`service/`** — webman-v2-Geschäfts-API-Dienst für die Benutzerseite, lauscht auf Port **8788**. Verarbeitet Werbeplattform-Anbindung, OAuth-Autorisierung, Datensynchronisierung, Berichts-Engine, Alarmüberwachung und weitere Geschäftslogik.
- **`admin/`** — unabhängiges webman-admin-v2-Verwaltungs-Backend, lauscht auf Port **8789**. Enthält PHP-Backend (Authentifizierung und Autorisierung, Benutzerverwaltung, Systemkonfiguration) und Vue-3-SPA-Frontend.
- **Kommunikation zwischen Verwaltungs-Backend und Geschäftsdienst** — Die Vue-SPA verbindet sich über axios (baseURL `/api`) direkt mit der service-API; Admin-eigene Routen (`/api/admin/*`) werden vom Admin-PHP-Backend (8789) bedient, Nginx leitet nach Pfad auf.
- **Entwicklungsmodus** — Vite-Dev-Server (Port 5173) proxyt `/api` an service:8788; das Admin-PHP-Backend stellt auf 8789 Session-Authentifizierung und SPA-Statik bereit.
- **Produktionsmodus** — Nginx routet `/` an admin:8789 (Verwaltungs-Backend-SPA) und `/api/` an service:8788 (Geschäfts-API).

## Erik-Stack-Integration

| Paket | Verwendung |
|----|------|
| `erikwang2013/snowflake-php` | Verteilte Snowflake-ID-Generierung |
| `erikwang2013/hashids` | Ver-/Entschlüsselung von API-ID-Parametern |
| `erikwang2013/jwt-webman` | JWT-Authentifizierungstoken |
| `erikwang2013/encryption` | Ver-/Entschlüsselung sensibler Daten auf API-Ebene |
| `erikwang2013/encryptable` | Automatische Ver-/Entschlüsselung auf DB-Feldebene |
| `erikwang2013/webman-scout` | Elasticsearch-Datensynchronisierung |
| `erikwang2013/season` | Nationalflaggen-Kennzeichnung |
| `erikwang2013/poster-php` | Slider-Captcha (Login-Schutz) |
| `hg/apidoc` | Automatische API-Dokumentgenerierung (Annotationen + Web-UI) |

## Internationalisierung

Alle Oberflächen unterstützen den zweisprachigen Wechsel zwischen **Chinesisch (zh-CN)** / **English (en)**:

| Endgerät | Technologie | Wechselmethode |
|----|------|---------|
| Admin | vue-i18n v9 | TopBar-Sprachauswahlmenü, localStorage-Persistenz |
| Service-API | `erik\support\I18n` | Accept-Language-Request-Header / `?lang=`-Parameter |
| Flutter | AppLocalizations + Delegate | Automatische Erkennung der Systemsprache |
| HarmonyOS | StringResources | Wechsel über `setLang()` |

## Sicherheit

### Service-Seite (14 Ebenen global + AuthMiddleware)

CORS → OriginGuard → SecurityHeaders → AttackGuard → ClientPlatform → ReplayGuard → Version → RateLimit → LoginThrottle → SessionLimit → SQLGuard → Validation → ResponseTime → Encryption → AuthMiddleware (Routen-Ebene)

### Admin-Seite (10 Ebenen global + AuthCheck)

CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → AuthCheck (Routen-Ebene)

### Übersicht der Schutzfähigkeiten (22 Punkte)

| Kategorie | Schutzpunkt | Beschreibung |
|------|--------|------|
| Eingabeerkennung | XSS (11 Muster) | script/iframe/event handler/javascript:/data: |
| | Pfad-Traversal (7 Muster) | ../ / null byte / /etc/passwd / .env / .git |
| | Header-Injection | CRLF-Erkennung |
| | Body-Größenbegrenzung | 10 MiB |
| | Content-Type-Whitelist | JSON/Form/Multipart/Plain |
| | SQL-Injection | UNION/DROP/ALTER-Mustererkennung |
| Authentifizierung | JWT-Token-Bindung | IP + User-Agent-Hash-Verifizierung |
| | Token-Refresh + Blacklist | Alte Token laufen automatisch ab |
| | Login-Drosselung | 5 Fehlversuche → 15 Minuten Sperre (Redis) |
| | Begrenzung paralleler Sessions | Max. 3 aktive Token pro Benutzer |
| | Captcha | Slider-Captcha (5 Minuten gültig, 5px Toleranz) |
| Request-Prüfung | CORS-Whitelist | Domänen-Whitelist in Produktion |
| | Origin/Referer-Prüfung | Cross-Origin-Quellenverifizierung |
| | CSRF-Token | Session-Token-Verifizierung auf Admin-Seite |
| | Replay-Schutz | Nonce + Timestamp ±5min (nicht-Browser-Seite) |
| | API-Ratenbegrenzung | Gleitendes Fenster 60-mal/60s |
| | SSRF-Schutz | OAuth-redirect_uri-Whitelist |
| Response-Header | CSP | Content-Security-Policy (SPA) |
| | X-Frame-Options / HSTS | Clickjacking-Schutz + HTTPS-Pflicht |
| | X-Content-Type-Options | nosniff |
| Datenschutz | Übertragungsverschlüsselung | EncryptionMiddleware (X-Encrypted) |
| | Speicherverschlüsselung | Encryptable (DB-Feldebene) |
| | Log-Maskierung | password/token/secret → \*\*\* |

### Sicherheitsarchitektur-Diagramm

![Sicherheitsarchitektur-Diagramm](docs/diagrams/svg/security.de.svg)

**Defense in Depth**: äußere Schicht (Nginx) → Eingangs-Gateway (5 Middleware-Ebenen) → Identitätsauthentifizierung (7 Punkte) → Eingabevalidierung (4 Punkte) → Frequenzsteuerung → Datenverschlüsselung → Audit-Nachverfolgung

**Authentifizierung**: Service und Admin verwenden einheitlich die Tabelle `admin_users` + bcrypt-Hash, JWT 24h + Refresh-Rotation

**Audit**: Alle Operationen protokollieren IP / User-Agent / Client-Platform / Operationsdetails

**Zweite Bestätigung**: Lösch-/Entbindungs-/Batch-Operationen verwenden das Muster „Bestätigungswort eingeben" (`GlobalConfirm` + `useConfirmStore`)

---

## Erweiterte Funktionen

| Funktion | Beschreibung | Technologie |
|------|------|------|
| Materialbibliothek | Bild-/Video-Upload-Verwaltung, Galerie-Vorschau, URL kopieren | AssetController + Vue-Galerie |
| Budget-Warnung | Echtzeit-Tracking des Tagesbudget-Verbrauchs, Dreistufen-Alarm (50/80/100%) | BudgetAlertService + 15min Cron |
| Schaltungs-Kalender | Plattformübergreifendes Gantt-Diagramm, Monats-/Wochenansicht, Einfärbung nach Plattform | CalendarService + Vue-Gantt |
| Plattformübergreifende Attribution | 5-Modell-Attribution (first/last/linear/time_decay/position_based), 30 Tage Rückverfolgung | AttributionEngine + ECharts |
| Resilienz von Plattformaufrufen | Circuit-Breaker-Zustandsmaschine pro Plattform (5 Fehler → OPEN → 30s Half-Open-Test), Degradierung fast-fail, Timeout-Prüfung der 29 Adapter | CircuitBreaker + GuardedAdapter |
| CDN-Asset-Beschleunigung | Objektspeicher-Multitreiber (local/oss/cos/s3), CDN-Anbieterverwaltung im Admin, vorsigniertes Direkt-Upload, automatisches Cache-Purge beim Löschen | ads-storage-Plugin + CdnProviderController |

---

## Hohe Parallelität

| Optimierung | Lösung | Datei |
|------|------|------|
| DB-Lese-/Schreibtrennung | Hauptdatenbank `shared` + Read-Only-Replikat `read_replica`, SELECT automatisch an Replikat | `config/database.php` |
| DB-Verbindungspool | `PDO::ATTR_PERSISTENT` persistente Verbindungen + Zeitzonen-Initialisierungsvorwärmung | `config/database.php` |
| Redis-Verbindungspool | `persistent` persistente Verbindungen + Lese-/Schreibtrennung `readonly`-Konfiguration | `config/redis.php` |
| Drei-Stufen-Cache | L1 Prozessspeicher → L2 APCu Shared Memory → L3 Redis | `support/CacheService.php` |
| Message-Queue asynchron | Redis-Liste 4 Kanäle (sync/report/export/notification) | `support/AsyncJobService.php` |
| Nginx-String-Limiting | 30r/s + burst 20 + 20 parallele Verbindungen + keepalive 32 | `docker/nginx/admin.conf` |
| Horizontale Skalierung | upstream Mehrfachinstanz + Failover + Sticky Session | `docker/nginx/admin.conf` |
| CDN-Beschleunigung | Statische Ressourcen `expires 30d` + `immutable` + `gzip_static` | `docker/nginx/admin.conf` |

---

## Schnellstart

### Ein-Klick-Web-Installation (empfohlen)

Nach dem Start des Dienstes den Browser auf `/install` öffnen, um den Installationsassistenten zu starten:

```bash
# Verwaltungs-Backend starten (Port 8789)
cd admin && composer install && php start.php start

# Browser öffnen und http://localhost:8789/install aufrufen
# Im Installationsassistenten Datenbankinformationen und Administrator-Konto ausfüllen, auf „Installation starten" klicken
```

Der Installationsassistent führt dich Schritt für Schritt durch die Webseite:
1. **Datenbankverbindung** — MySQL-Host, Port, Datenbankname, Benutzername und Passwort ausfüllen, Verbindungstest unterstützt
2. **Redis-Konfiguration** — Redis-Verbindungsinformationen ausfüllen (optional)
3. **Administrator-Konto** — Login-Benutzername, Passwort und Anzeigename des Backends festlegen
4. **Ein-Klick-Installation** — automatisch Datenbank anlegen, `install.sql` ausführen (erstellt 29 Tabellen und schreibt Seed-Daten), Administrator-Passwort aktualisieren

Nach der Installation `/` aufrufen, um ins Verwaltungs-Backend zu gelangen und sich mit dem festgelegten Benutzernamen und Passwort anzumelden.

### Docker (für Produktion empfohlen)

```bash
# Alle Dienste starten (MySQL + Redis + PHP + Nginx)
docker-compose up -d

# Datenbank initialisieren (Tabellen erstellen + Seed-Daten)
make db-init

# Zugriff
# Verwaltungs-Backend: http://localhost
# Installationsassistent: http://localhost/install
# API: http://localhost/api/v1（Version ist im URL-Pfad festgelegt und wird nicht per Header übertragen）
```

### Lokale Entwicklung

```bash
# Server (Port 8788)
cd service && composer install && php start.php start

# Verwaltungs-Backend (Port 5173)
cd admin/public/web && npm install && npm run dev

# Flutter-App
cd apps/flutter && flutter run -d chrome  # Web-PC
# HarmonyOS-App
# DevEco Studio verwenden, um das Verzeichnis apps/harmonyos zu öffnen
cd apps/flutter && flutter run -d android # Mobile

# TypeScript-Prüfung
cd admin/public/web && npx vue-tsc --noEmit   # null Fehler
```

Bedienungsanleitung → [docs/usage.de.md](docs/usage.de.md)
---

## Projektstruktur

```
ads-php/
├── service/                           # Benutzerseitiger Geschäftsdienst (webman v2 :8788)
│   ├── plugin/
│   │   ├── ads-api/                   # REST API (76 Endpunkte, versionsbasierte Routen /api/v1)
│   │   │   ├── controller/v1/         # 21 Controller (inkl. admin/-Unterverzeichnis)
│   │   │   ├── middleware/            # 15 Middlewares
│   │   │   ├── config/route.php       # Routendefinition
│   │   ├── ads-platform/              # Kern der Plattform-Adapter
│   │   │   ├── adapter/               # 29 Plattform-Adapter
│   │   │   ├── src/                   # AdapterRegistry, CampaignData
│   │   │   ├── model/                 # BidRule, BidLog, TargetingTemplate
│   │   │   ├── service/               # BidEngine, ReportBuilder
│   │   │   └── migration/             # SQL-Migrationen + Performance-Indizes
│   │   ├── ads-account/               # OAuth-Kontoverwaltung
│   │   ├── ads-task/                  # Scheduling geplanter Aufgaben (6 Cron)
│   │   ├── ads-alert/                 # Alarmüberwachungs-Engine + Budgetwarnung
│   │   ├── ads-report/                # Berichts-Engine (CSV/Excel/PDF) + Attributions-Engine + Werbekalender
│   │   ├── ads-tenant/                # Multi-Tenant-Verwaltung
│   │   └── ads-storage/               # Speicherabstraktion (local/OSS/COS/S3) + CDN-Anbieter
│   ├── public/                        # Statische Ressourcen (webman-interne Static-Verarbeitung)
│   │   └── img/pet.svg                # Projekt-Maskottchen 阿章, auf der /docs-Seite angezeigt
│   ├── scripts/backfill-assets.php    # Bestehende Assets in den Objektspeicher übertragen
│   ├── support/                       # Erik-Stack-Hilfsklassen
│   │   ├── ControllerTrait.php        # Gemeinsamer Controller-Trait
│   │   ├── JwtService.php             # JWT-Wrapper-Klasse
│   │   ├── CacheService.php           # Redis-Cache-Dienst
│   │   ├── ExceptionHandler.php       # API-Ausnahmebehandler
│   │   └── ApiResponse.php            # Einheitliches Antwortformat
│   ├── config/                        # Globale Konfiguration (DB/Redis/Log/Middleware)
│   ├── tests/                         # PHPUnit-Tests (288 Tests)
│   │   ├── Unit/                      # Unit-Tests (Middleware, Task, Engines)
│   │   └── Integration/               # Integrationstests (Auth, Health, API)
│   └── start.php                      # Diensteinstieg
├── admin/                             # Unabhängiges Verwaltungs-Backend (webman-admin v2 :8789)
│   ├── public/web/
│   │   ├── public/pet.svg             # Projekt-Maskottchen 阿章, Favicon + Login-Seite
│   │   ├── src/
│   │   │   ├── views/                 # 21 Vue-Seiten
│   │   │   │   ├── dashboard/         # Dashboard (ECharts)
│   │   │   │   ├── campaign/          # Kampagnen
│   │   │   │   ├── adgroup/           # Anzeigengruppen
│   │   │   │   ├── creative/          # Werbemittel
│   │   │   │   ├── account/           # Kontoverwaltung + Verknüpfung
│   │   │   │   ├── asset/             # Materialbibliothek
│   │   │   │   ├── report/            # Berichtsanalyse + Export + Attribution + Werbekalender
│   │   │   │   ├── alert/             # Alarmregeln + Protokolle
│   │   │   │   ├── notification/      # Benachrichtigungszentrum
│   │   │   │   ├── sync/              # Synchronisierungsstatus
│   │   │   │   ├── bid/               # Automatische Gebotsregeln
│   │   │   │   ├── cdn/               # CDN-Anbieter
│   │   │   │   ├── login/             # Login-Seite (阿章)
│   │   │   │   └── system/            # Benutzerverwaltung + Audit-Log + Systeminformationen
│   │   │   ├── api/                   # 15 API-Clients
│   │   │   ├── stores/                # 5 Pinia-Stores
│   │   │   └── components/            # Gemeinsame Komponenten (ListPageLayout u. a., 8 Stück)
│   │   └── dist/                      # Build-Artefakte (nicht im Repo, CI-Build)
│   ├── app/                           # PHP-Backend (controller/middleware)
│   └── config/                        # Admin-Konfiguration
├── apps/
│   ├── flutter/                       # Flutter Desktop App
│   │   └── lib/
│   │       ├── features/              # 12 Funktionsseiten + Shell-Layout
│   │       ├── config/menu_config.dart # Zwei-Ebenen-Menükonfiguration
│   │       ├── router.dart            # GoRouter (ShellRoute + Routen-Guards)
│   │       └── stores/                # Riverpod Auth Provider
│   └── harmonyos/                     # HarmonyOS (API-Client bereit)
├── docker/                            # Docker- & Nginx-Konfiguration
├── .github/workflows/                 # CI (Syntax→Tests→TS→Docker) + CD (Build & Push)
├── docs/                              # Designdokumente, Umsetzungspläne, Skills
│   ├── architecture.md                # Architekturdesign (mit Architektur-/Anfrageablauf-/Sicherheitsdiagramm)
│   ├── features.md                    # Funktionsdesign (21 Module, mit Funktionsmodul-Diagramm)
│   ├── usage.md                       # Bedienungsanleitung (mit Datenlebenszyklus-Diagramm)
│   ├── diagrams/svg/                  # 5 Diagrammkategorien × 13 Sprachen + pet.svg Projekt-Maskottchen
│   └── skills/                        # 143 wiederverwendbare Projekt-Skills
├── docker-compose.yml
├── Dockerfile / Dockerfile.admin / Dockerfile.admin-php
└── Makefile
```

## API-Endpunkte

> Alle API-Endpunktdefinitionen siehe [docs/api.md](docs/api.de.md) (mit Anfrage-/Antwortbeispielen, Fehlercodes, Ratenbegrenzungsstrategie).
> hg/apidoc-Online-Dokumentation: nach dem Dienststart `http://127.0.0.1:8788/apidoc` aufrufen

## Datenbank

**Namenskonvention**: Tabellenpräfix `ads_`, Primärschlüssel `BIGINT UNSIGNED PRIMARY KEY` (ohne Auto-Increment, Snowflake-ID), Engine InnoDB, Zeichensatz utf8mb4

| Kategorie | Tabellenname | Verwendung |
|------|------|------|
| Basis | `ads_tenants` | Multi-Tenant |
| Konten | `ads_platform_accounts`, `ads_auth_tokens` | OAuth-Plattformkonten |
| Schaltung | `ads_campaigns`, `ads_ad_groups`, `ads_creatives` | Werbeschaltungs-Hierarchie |
| Berichte | `ads_report_metrics`, `ads_report_extras` | Einheitliche Berichtskennzahlen |
| Material | `ads_assets` | Kreativ-Materialbibliothek |
| CDN | `ads_cdn_providers` | CDN-Anbieterkonfiguration (Zugangsdaten verschlüsselt) |
| Targeting | `ads_targeting_templates` | Zielgruppen-Targeting-Vorlagen |
| Attribution | `ads_conversions`, `ads_attribution_results` | Conversion-Tracking + Attributionsergebnisse |
| Gebote | `ads_bid_rules`, `ads_bid_logs` | Automatische Gebotsregeln + Historie |
| Alarm | `ads_alert_rules`, `ads_alert_logs` | Alarmüberwachung |
| Benachrichtigungen | `ads_notifications` | In-Site-Benachrichtigungen |
| System | `ads_sync_errors`, `admin_users`, `admin_roles`, `admin_audit_logs` | Synchronisierungsfehler, RBAC, Audit |

---

## Geplante Aufgaben

| Aufgabe | Häufigkeit | Funktion |
|------|------|------|
| TokenRefreshTask | alle 55 Minuten | Abgelaufene OAuth-Token scannen, automatisch aktualisieren |
| DataSyncTask | alle 10 Minuten | Pläne + Anzeigegruppen + Creatives + Berichte der Plattformen abrufen, in einheitliche Tabellen schreiben, Cache leeren |
| AlertCheckTask | alle 5 Minuten | Aktivierte Alarmregeln durchlaufen, Schwellenwerte bewerten, Push auslösen |
| BidCheckTask | alle 10 Minuten | Automatische Gebotsregeln durchlaufen, Kennzahlen abfragen, Budgetanpassung/Start-Stopp ausführen |
| BudgetCheckTask | alle 15 Minuten | Laufende Pläne durchlaufen, Tagesbudget-Verbrauch verfolgen, Dreistufen-Warnung (50/80/100%) |
| RetrySyncTask | alle 3 Minuten | Fehlgeschlagene Synchronisierungsaufgaben erneut versuchen (max. 3 Mal, exponentielles Backoff) |

---

## Tests

```bash
cd service && ./vendor/bin/phpunit
# 288 Tests / 862 Assertions
```

**Abdeckung**: 14 Middleware · 8 Plugin-Geschäftsebenen (Konto/Alarm/Plattform/Bericht/Aufgabe/Tenant/Speicher) · Engines (Bid/Alert/Attribution/Report) · API-Integrationstests (76 Routen) · UI-E2E (18 Seiten)

```bash
# TypeScript-Prüfung
cd admin/public/web && npx vue-tsc --noEmit   # null Fehler

# Dart-Analyse
cd apps/flutter && dart analyze   # null Fehler
```

## CI/CD

**CI** (`.github/workflows/ci.yml`): automatische Pipeline — **PHP-Syntax → PHPUnit → TypeScript → Docker-Build**

**CD** (`.github/workflows/deploy.yml`): manuell ausgelöst — **Docker-Buildx → Push zu GHCR (service/admin/admin-php) → Bereitstellungsbenachrichtigung**

`.github/dependabot.yml` aktualisiert wöchentlich automatisch Composer-, npm- und Docker-Abhängigkeiten.

---

## Skills

`docs/skills/` — 11 wiederverwendbare Projekt-Skills:

| Skill | Beschreibung |
|------|------|
| `adapter-generator` | Neue Werbeplattform-Adapter generieren (14-Methoden-Vorlage) |
| `migration-generator` | SQL-Migrationsdateien generieren (ads_-Präfix + BIGINT-PK) |
| `erik-stack` | Integrationsleitfaden für die 8 Erik-Stack-Pakete |
| `admin-page-generator` | Vue3-Verwaltungs-Backend-Seiten generieren |
| `api-endpoint` | RESTful-API-Endpunkte hinzufügen |
| `tdd-workflow` | TDD-Verifizierungsablauf (Test → Implementierung → Syntax → TypeScript → Commit) |
| `security-middleware` | Sicherheits-Middleware-Ebene hinzufügen (Schnittstellenspezifikation + Registrierung + Referenz der bestehenden Kette) |
| `version-split` | Lite/Standard/Full-Drei-Versionen-Aufteilung (Operationsschritte + Konfigurationsaktualisierung) |
| `cache-strategy` | Drei-Stufen-Cache-Strategie (L1 Speicher/L2 APCu/L3 Redis + TTL-Empfehlungen) |
| `attribution-setup` | Plattformübergreifende Attributions-Engine (5 Modelle + API-Aufrufe + Datenvorbereitung) |
| `high-concurrency` | 8 Optimierungen für hohe Parallelität (Lese-/Schreibtrennung/Verbindungspool/Message-Queue/horizontale Skalierung/CDN) |

## Open Source ist nicht einfach, Unterstützung willkommen

| WeChat | Alipay |
|:---:|:---:|
| ![微信](./docs/weixinpay.png "微信") | ![支付宝](./docs/alipay.png "支付宝") |

### Global Transfer Donation

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

### Krypto-Spenden (Crypto Donation)

Wenn dieses Projekt Ihnen hilft, scannen Sie gerne den QR-Code, um zu spenden. Vielen Dank!

| Netzwerk (Network) | QR-Code (QR Code) | Wallet-Adresse (Wallet Address) |
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

## Lizenz

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

All rights reserved.
