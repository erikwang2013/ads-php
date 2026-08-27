# Phase 10: Implementierungsplan Vertiefung und Kommerzialisierung

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**Ziel:** Aufbauend auf den Verträgen und Mehrkanal-Fähigkeiten aus Phase 7-9 vier Vertiefungsfähigkeiten umsetzen: Synchronisierungsstatus-Visualisierung, Conversion-Daten-Loop, Mobile-CI-Build, Multi-Tenant-SaaS-Kontingente.

**Quelle:** Vom Phase-7-Team-Audit abgeleitete Richtung (researcher: ES/Read-Write-Splitting/Queue-Umsetzung, Flutter/HarmonyOS-CI, 29 Plattformen echte Integration, SaaS-Abrechnungskontingente, Conversion-Daten-Loop, Synchronisierungsstatus-Visualisierung, AI-Gebote)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## Ist-Zustand (verifiziert)

| Kandidaten-Unterpunkt | Ist-Zustand |
|---|---|
| Synchronisierungsstatus-Visualisierung | Tabelle `ads_sync_errors` + `RetrySyncTask` (3 Wiederholungen, Backoff 5^n Minuten) existiert; **keine Frontend-Seite/API zur Anzeige von Sync-Fehlerquote und Latenz** |
| Conversion-Daten-Loop | Tabellen `ads_conversions` + `ads_attribution_results` existieren, Attributions-Engine implementiert; **kein Erfassungs-Einstieg für Conversion-Daten** (Callback-/Event-Tracking-API) |
| Mobile-CI | `ci.yml` nur PHP-Syntax→PHPUnit→vue-tsc→Docker; **kein Flutter/HarmonyOS-Build-Paket** |
| Multi-Tenant-SaaS | Tabelle `ads_tenants` + TenantIdentify-Middleware existieren; **keine Abrechnung/Kontingente/Verbrauchsstatistik** |
| ES-Umsetzung | scout.php konfiguriert + webman-scout-Abhängigkeit eingeführt; **docker-compose ohne ES-Dienst** |
| 29 Plattformen echte Integration | Code für 29 Adapter vollständig; **keine Sandbox-/Credential-Integrationsaufzeichnung** (externe Credentials nötig, als manueller Posten markiert) |

## Task 1: Synchronisierungsstatus-Visualisierung

### Dateien:
- Ändern: `service/plugin/ads-api/controller/v1/DashboardController.php` oder neu `service/plugin/ads-api/controller/v1/SyncController.php` + route
- Erstellen: `admin/public/web/src/api/sync.ts`
- Erstellen: `admin/public/web/src/views/sync/SyncStatus.vue` (oder in Systemseite integrieren)

### Design-Highlights
- Endpunkte: `GET /api/sync/status` (Konto-Ebene: last_sync_at, Erfolgsquote, heutige Fehlerzahl, anstehende Wiederholungen) + `GET /api/sync/errors` (paginierte Fehlerliste, mit last_error/retry_count/next_retry_at)
- Frontend: Synchronisierungsstatus-Seite (Tabelle + Zusammenfassungskarten), nur Full/Standard-Versionslinie
- Datenquellen: ads_platform_accounts (last_sync_at) + ads_sync_errors

## Task 2: Conversion-Daten-Erfassungs-API

### Dateien:
- Ändern: `service/plugin/ads-api/controller/v1/` (ConversionController + route ergänzen)
- Erstellen: `service/plugin/ads-report/service/ConversionService.php`

### Design-Highlights
- Endpunkte: `POST /api/conversions` (Businessseite meldet Conversion zurück: platform/campaign_id/order_id/conversion_time/value/currency/channel) + `GET /api/conversions` (Abfrage)
- Validierung: campaign_id existiert, Betrag nicht negativ, Zeitformat; Schreiben in ads_conversions
- Attributions-Verknüpfung: Nach Rückmeldung kann Neuberechnung der Attribution ausgelöst werden (oder Hinweis, dass die bestehende AttributionEngine zeitgesteuert/manuell neu berechnet)
- Frontend: Attributionsbericht-Seite um Erläuterung/Demo „Conversion-Rückmeldung" ergänzen (optional)

## Task 3: Mobile-CI-Build

### Dateien:
- Ändern: `.github/workflows/ci.yml` (Job ergänzen: Flutter-Build (web + linux oder apk) + HarmonyOS-Statische-Prüfung)

### Design-Highlights
- Flutter: `flutter pub get && flutter analyze && flutter build web` (oder apk, je nach Repo-Zustand wählbares Build-Ziel; falls Flutter-Umgebung eingeschränkt, dart analyze verwenden)
- HarmonyOS: keine Standard-Linux-CI-Toolchain, statische Prüfung erläutern oder überspringen (markieren)
- Parallel zum bestehenden php-tests-Job, blockiert den Hauptablauf nicht

## Task 4: Multi-Tenant-SaaS-Kontingente (MVP)

### Dateien:
- Ändern: `service/plugin/ads-tenant/` (QuotaService ergänzen)
- Ändern: `service/plugin/ads-api/config/route.php` + controller

### Design-Highlights
- Daten: ads_tenants um quota-Feld erweitern oder neue Tabelle ads_tenant_quotas (plan/account_limit/campaign_limit/sync_quota)
- Prüfpunkte: Zahl der gebundenen Konten, Zahl der erstellten Pläne, tägliche Synchronisierungsanzahl (an den Einstiegen von AccountController/CampaignController/DataSyncTask prüfen)
- Endpunkt: `GET /api/tenant/quota` (Verbrauch + Kontingent)
- Frontend: Systemseite zeigt Kontingentverbrauch (optional, MVP kann reine API sein)
- Versionslinie: quota-Standardwerte nach lite/standard/full unterschiedlich (config-Konstanten)

## Abnahme (nach Task)
- [ ] Task 1: sync-API-Endpunkte verfügbar, Frontend-Seite zeigt an, Testabdeckung
- [ ] Task 2: conversions-Rückmelde-API schreib- und lesbar, Validierung wirksam, Testabdeckung
- [ ] Task 3: neuer CI-Job besteht (oder übersprungene Punkte klar markiert)
- [ ] Task 4: quota-API liefert korrekt, Limitüberschreitung-Sperre wirksam, Testabdeckung
- [ ] Alles: `php vendor/bin/phpunit --no-coverage` komplett bestanden, vue-tsc bestanden

## Nicht in diesem Umfang (erfordert externe Ressourcen)
- 29 Plattformen echte Integration (erfordert Credentials/Sandbox der einzelnen Plattformen)
- ES-Dienst-Umsetzung (erfordert ES-Dienst und Indexinitialisierung in docker-compose)
- AI-Gebotsvorschläge (Modell-/Datenvorbereitung)
