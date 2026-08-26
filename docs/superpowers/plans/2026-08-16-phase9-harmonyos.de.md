# Phase 9: Implementierungsplan HarmonyOS echte Integration

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**Ziel:** Die 6 Seiten des HarmonyOS-Clients von simulierten Daten auf echte API-Aufrufe (service :8788) umstellen, das baseUrl-Hardcoding des ApiClient beheben, den Login real machen und den HarmonyOS-Client zum nutzbaren dritten Client machen.

**Quelle:** Phase-7-Team-Audit (mobile-dev-Inventur: alle 6 HarmonyOS-Seiten mit simulierten Daten, 0 echte Aufrufe, ApiClient-baseUrl hartkodiert `http://127.0.0.1:8788/api`)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## Ist-Zustand (verifiziert)

| Komponente | Status |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login vollständig; baseUrl hartkodiert `http://127.0.0.1:8788/api` (Flutter nutzt gleichlautenden relativen `/api`); login() hat keine Aufrufer |
| `pages/LoginPage.ets` | Simulierter Login (setTimeout 1s Wechsel), Kommentar „replace with actual API call" |
| `pages/DashboardPage.ets` | `@State` hartkodierte Metriken (totalCost=1250000 usw.) |
| `pages/CampaignListPage.ets` | L187 Kommentar-Platzhalter `/campaigns` |
| `pages/AccountPage.ets` | L138 Kommentar-Platzhalter `/accounts` |
| `pages/AlertPage.ets` | L146 Kommentar-Platzhalter `/alerts` |
| `pages/ReportPage.ets` | L242 Kommentar-Platzhalter `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric vorhanden |
| i18n | StringResources.ets (15+ keys) |

## Task 1: ApiClient erweitern

### Dateien:
- Ändern: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### Design-Highlights
- **baseUrl konfigurierbar machen**: setBaseUrl beibehalten, Standardwert weiterhin `http://127.0.0.1:8788/api` (echtes Gerät/Simulator muss auf LAN-Adresse zeigen, per Kommentar erläutern); Flutter-artigen gleichlautenden relativen Pfad vermeiden (ArkTS benötigt absolute URLs)
- **Doppelten replayHeaders-Bug beheben**: `{ ...this.replayHeaders(), ...this.replayHeaders() }` doppelt expandiert (in der get-Methode) → einfach
- **login()-Rückgabewert anpassen**: service `POST /api/auth/login` liefert `{access_token, token_type, expires_in, user}` (gegen die tatsächlichen Felder von `service/plugin/ads-api/controller/v1/AuthController.php` prüfen — es ist access_token statt token, `data.token`-Prüfung nach Verifizierung korrigieren)
- **Fehlerbehandlung**: bei resp.responseCode ungleich 2xx Fehler werfen/klare Fehlermeldung zurückgeben; JSON.parse-Fehlerschutz
- Konvention beibehalten: get/post/put/delete liefern `data.data` (ApiResponse-Entpackung)

## Task 2: LoginPage echter Login

### Dateien:
- Ändern: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### Design-Highlights
- `handleLogin()` ruft `ApiClient.login(username, password)`; Erfolg → setToken + Wechsel zu Dashboard; Fehler → toast Fehlermeldung
- Ladezustand isLoading existiert bereits, wiederverwenden
- Fehlermeldung bevorzugt die vom Service gelieferte message (ApiResponse-Envelope), sonst allgemeiner Text

## Task 3: Fünf Geschäftsseiten realisieren

### Dateien:
- Ändern: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`, `CampaignListPage.ets`, `AccountPage.ets`, `AlertPage.ets`, `ReportPage.ets`

### Endpunkt-Vergleichstabelle (im Phase-7-Audit bestätigt, konsistent mit dem reparierten Flutter)
| Seite | Aufruf | Parsing |
|---|---|---|
| DashboardPage | `GET /reports/summary` (heutiges Intervall) | `data.overview` → totalCost/total_impressions/avg_ctr usw. (Beträge in Fen, formatFen vorhanden) |
| CampaignListPage | `GET /campaigns` | `data.list` (paginiert) → Campaign-Model |
| AccountPage | `GET /accounts` | `data.list` → PlatformAccount-Model |
| AlertPage | `GET /alerts/logs` | `data.list` → AlertLog-Felder (metric/rule_name/current_value/condition/threshold/status) |
| ReportPage | `GET /reports/custom` (date_start/date_end/dimensions[]/metrics[]) | `data.list` → ReportMetric |

### Design-Highlights
- Seitenladen (aboutToAppear) löst Request aus; @State-Daten initial auf leer/0 setzen, keine Simulationswerte hinterlassen
- Bei Ladefehler Fehler + Retry anzeigen (dem Fehler-/Retry-Muster der Flutter-Seiten folgen)
- Betragseinheit: Service liefert Beträge als Zahlen in Fen, formatFen verarbeitet bereits
- **Keine neuen Dateien**, bestehende UI-Struktur und i18n der Seiten beibehalten

## Task 4: Verifizierung

### Abnahme
- [ ] ApiClient ohne doppeltes replayHeaders, login-Rückgabefelder konsistent mit AuthController
- [ ] Keine hartkodierten simulierten Geschäftsdaten in den 6 Seiten mehr (grep-Verifizierung)
- [ ] Aufrufpfade der 5 Geschäftsseiten entsprechen 1:1 den Service-Routen (gegen `service/plugin/ads-api/config/route.php`)
- [ ] ArkTS-Syntaxprüfung (falls hvigor/DevEco-Toolchain in dieser Umgebung verfügbar, ausführen; sonst erläutern und manuell gegenprüfen)
- [ ] Regression: Service-PHPUnit nicht beeinträchtigt
