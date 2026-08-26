# Phase 7: Implementierungsplan für die Cross-Client-Vertragsreparatur

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **Statusupdate (2026-08-16):** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ alle abgeschlossen, Tester-Regressionsverifikation bestanden (35 Tests OK, Vertrags-Kreuzabgleich ohne Geister-Endpunkte, Phase 7 abnahmefähig).

**Ziel:** Die vom Team-Audit gefundenen Cross-Client-API-Vertragsprobleme beheben: 3 Flutter-Geister-Endpunkte (404), Admin-`admin.ts`-Doppelpräfix-Bug, `/system/info` ohne Route, ServiceProxy nicht verdrahtet, veraltete Dokumentationsangaben. Den konsistenten Konsum der service API durch alle drei Clients (Admin/Flutter/HarmonyOS) wiederherstellen.

**Quelle:** Paralleles Team-Audit vom 2026-08-07 (backend-dev Routen-Inventur 61 Endpunkte, vue-dev Admin-Aufruf-Inventur 50 Aufrufstellen, mobile-dev Mobil-Inventur, researcher-Inventur realisiert/geplant im Kreuzvergleich)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1: Flutter-Geister-Endpunkte reparieren (🔴 höchste Priorität)

### Hintergrund
3 Flutter-Seiten rufen Routen auf, die der Service nicht besitzt — alle 404:

| Flutter-Aufruf | Tatsächliche Service-Route | Reparaturlösung |
|---|---|---|
| `GET /dashboard` | Keine (Dashboard-Zusammenfassung unter `/reports/summary`) | Ändern zu `GET /reports/summary` |
| `GET /alerts` | Keine (Alarme unter `/alerts/rules`, `/alerts/logs`, `/alerts/unread-count`) | Ändern zu `GET /alerts/logs` (Semantik Alarmliste) |
| `GET /reports` | Keine (Berichte unter `/reports/summary`, `/reports/custom`) | Ändern zu `GET /reports/custom` (mit Datums-/Dimensions-/Metrikparametern, passend zu ReportBuilder::buildCustom) |

### Dateien:
- Ändern: `apps/flutter/lib/features/dashboard/dashboard_page.dart` (`/dashboard` → `/reports/summary` ×2 Stellen, an die Antwortstruktur `data.overview`/`by_platform`/`daily` angepasst) ✅
- Ändern: `apps/flutter/lib/features/alert/alert_page.dart` (`/alerts` → `/alerts/logs`, an die Paginierungsstruktur `data.list` angepasst, AlertLog-Felder rule_name/metric/current_value/condition/threshold) ✅
- Ändern: `apps/flutter/lib/features/report/report_page.dart` (`/reports` → `/reports/custom`, Parameter date_start/date_end/dimensions[]/metrics[], `data.list` parsen, Feld cost) ✅
- Verifizieren: Antwortfelder stimmen mit den tatsächlichen Rückgaben von `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` überein ✅

### Abnahme
- [x] Drei Pfadänderungen durchgeführt, Abfrageparameter erhalten (Datumsparameter der Berichtsseite → date_start/date_end + dimensions/metrics) ✅
- [x] Antwort-Parsing an die tatsächliche JSON-Struktur des Backends angepasst (overview / paginated list / custom list) ✅
- [x] Nach der Änderung `flutter analyze` ohne Fehler — in dieser Umgebung ist der Flutter-SDK-Cache schreibgeschützt und nicht ausführbar; stattdessen mit SDK-eingebautem `dart analyze` über das gesamte Projekt **0 errors** (die 15 bestehenden Warnungen stammen alle aus der Zeit vor der Änderung, keine neuen Probleme eingeführt) ✅

---

## Task 2: Admin-`admin.ts`-Doppelpräfix-Bug reparieren

### Hintergrund
- `admin/public/web/src/api/admin.ts` verwendet Pfade `/api/admin/...`, aber die axios-baseURL ist bereits `/api` (`src/api/index.ts`) — tatsächlich entsteht `/api/api/admin/...`, die 5 Aufrufe von UserManage.vue / AuditLog.vue führen mit hoher Wahrscheinlichkeit zu 404.
- **Tiefgreifendes Architekturproblem (im Endbericht von vue-dev bestätigt)**: Das Admin-Backend (8789) stellt selbst 12 lokale Routen bereit (`/api/admin/login`, `me`, `logout`, `users` CRUD, `roles`, `audit-logs`, `/api/install/*`), aber:
  - `location /api/` in `docker/nginx/admin.conf` proxy_pass **alle** an `service_api` (php:8788);
  - `upstream admin_backend` (admin-php:8789) ist zwar definiert, aber **kein location referenziert ihn** → in Produktion erreicht `/api/admin/*` niemals 8789;
  - Auch der Vite-dev-Proxy leitet `/api` vollständig an 8788.
  - Fazit: Selbst mit repariertem Doppelpräfix bleiben `/api/admin/*` 404 — die lokalen Routen des Admin-Backends sind im Produktionspfad nicht verdrahtet.

### Entscheidungspunkt (bedarf Bestätigung durch backend-dev + vue-dev + devops)
- Option A (empfohlen): vue-dev ändert die `admin.ts`-Pfade zu relativ `/admin/users`, `/admin/audit-logs`, und **devops ergänzt in Nginx `location /api/admin/` → `proxy_pass http://admin_backend`** (vor `location /api/` platziert, exakte Präfix-Match-Priorität), sodass die Admin-eigenen Routen direkt von 8789 bedient werden, Geschäftsrouten weiter über 8788 laufen
- Option B: backend-dev ergänzt `/api/admin/*`-Routen im Service (überlappt mit der Admin-Zuständigkeit, nicht empfohlen)
- Option C: Auch Geschäftsabfragen auf ServiceProxy umstellen (erfordert Verdrahtung, größter Aufwand, nur bei einheitlicher Admin-Authentifizierung erwägen)

### Dateien:
- Ändern: `admin/public/web/src/api/admin.ts` (`/api`-Präfix entfernen)
- Ändern: `docker/nginx/admin.conf` (`location /api/admin/` → admin_backend upstream ergänzen)
- Ändern: `admin/public/web/vite.config.ts` (dev-Proxy `/api/admin` → 8789-Regel ergänzen, vor `/api` platziert)
- Verifizieren: Admin-Backend-Routen in `admin/config/route.php` (/api/admin/users usw.) passen zu den Frontend-Aufrufen

### Abnahme
- [x] Frontend-Request-Pfade stimmen mit den tatsächlich vorhandenen Backend-Routen überein (keine 404) — alle 9 Methoden von admin.ts gegen route.php geprüft ✅, vue-tsc bestanden
- [x] Nginx/Vite leiten `/api/admin/*` korrekt an 8789, restliches `/api/*` an 8788 — Nginx `location /api/admin/` ergänzt, Vite `/api/admin`-Proxy ergänzt (vor `/api` platziert) ✅
- [x] UserManage-/AuditLog-Seiten funktionsfähig — Pfade angeglichen (inkl. Entscheidung listRoles → `/admin/users/roles`) ✅

---

## Task 3: `/system/info` ohne Route + ServiceProxy-Entscheidung

### Hintergrund
- `SystemInfo.vue` / `stores/admin.ts` rufen `GET /api/system/info` auf, der Service hat diese Route nicht (nur /health, /ping), 404 wird von try/catch verschluckt
- `admin/app/controller/ServiceProxy.php` ist definiert, hat aber 0 aktive Aufrufer im gesamten Repo („definiert, nicht verdrahtet")

### Entscheidungspunkt
- `/system/info`: Option A — Frontend auf `/health` umstellen (Service hat es bereits); Option B — backend-dev ergänzt den Endpunkt `/api/system/info` im Service (liefert Versions-/Umgebungsinformationen, auch für HarmonyOS/Flutter nützlich, empfohlen)
- ServiceProxy: Option A — an die Admin-spezifischen APIs verdrahten, die das Admin-Backend benötigt (z. B. Audit-Log-Weiterleitung); Option B — Klasse löschen und Dokumentation auf „Admin verbindet sich direkt mit Service" (aktuelle tatsächliche Architektur) aktualisieren

### Ausgeführt (2026-08-16)
- **`/system/info` → Option A (Frontend auf `/health` umgestellt)**: SystemInfo.vue nutzt natives axios mit `GET /health`, prüft `checks.database === 'ok'`; die Route `/health` trägt auf Service-Seite kein `/api`-Präfix, Vite hat `/health`-Proxy ergänzt, Nginx-`location /health` existiert bereits; Toter Code in `stores/admin.ts` entsprechend auf `/health` geändert ✅
- **ServiceProxy → Option B (behalten + Dokumentation)**: Klasse bleibt als vorgesehene Infrastruktur erhalten (`ServiceProxy::init()` selbstinitialisierend, harmlos), Kommentar in `admin/config/app.php` auf „vorgesehene Infrastruktur, aktuell keine aktiven Aufrufer" aktualisiert ✅

### Abnahme
- [x] Entscheidung zu `/system/info` umgesetzt: Frontend-Aufruf entfernt (auf /health umgestellt), keine 404-Geister-Requests ✅
- [x] ServiceProxy-Entscheidung umgesetzt: Klasse behalten, aktuellen Zustand im config-Kommentar erläutert ✅

---

## Task 4: Dokumentationsnachführung und einheitliche Darstellung

### Hintergrund
- README „14 Controller / 45+ Endpunkte" veraltet (tatsächlich 17 Controller / 61 Endpunkte)
- Checkboxen der phase-Pläne in `docs/superpowers/plans/` nicht nachgetragen (Code implementiert, aber Dokumentation nicht angehakt)
- HarmonyOS-Status „UI in Planung" veraltet (tatsächlich 6 Seiten + ApiClient bereit)
- install.html / InstallController mit Standard `.../api/v1` inkonsistent zu config-Standard `/api` (X-API-Version-Header)
- CacheService-Kommentar nennt zweistufigen Cache, tatsächlich dreistufig (L1 Speicher / APCu / Redis)

### Dateien:
- Ändern: `README.md` / `README.en.md` (Controllerzahl, Endpunktzahl, HarmonyOS-Status, Cache-Ebenen)
- Ändern: `admin/public/install.html` / `admin/app/controller/InstallController.php` (Versionspräfix vereinheitlichen)
- Ändern: `service/support/CacheService.php` (Kommentar korrigieren)
- Optional: Checkboxen in `docs/superpowers/plans/*.md` nachtragen

### Ausgeführt (2026-08-16)
- README.md / README.en.md: 17 Controller / 61 Endpunkte / HarmonyOS 6 Seiten / 19 Vue-Seiten / SPA-Direktverbindung vollständig aktualisiert ✅
- install.html / InstallController: Standard `/api/v1` → `/api` (X-API-Version-Header-Mechanismus) ✅
- Checkboxen von 8 phase-Plänen alle nachgetragen ✅ (außer phase7, steht noch aus)

### Abnahme
- [x] README-Daten stimmen mit dem Code überein (17 Controller / 61 Endpunkte / HarmonyOS 6 Seiten) ✅
- [x] Versionspräfix des Installationsassistenten konsistent zum X-API-Version-Mechanismus ✅

---

## Nachfolge-Phasenplanung (Phase 8-10, außerhalb dieses Plans)

| Phase | Inhalt | Status |
|---|---|---|
| Phase 8 | Alarm-Mehrkanal-Umsetzung: ads-alert erhält channel/ (Email SMTP, Webhook, SMS-Gateway-Platzhalter) — Lücke aus Phase 5 schließen | Noch nicht gestartet |
| Phase 9 | HarmonyOS echte Integration: 6 Seiten an ApiClient anschließen (aktuell 0 echte Aufrufe, alles simulierte Daten) | Noch nicht gestartet |
| Phase 10 | Vertiefung und Kommerzialisierung: 29 Plattformen echte Integration, Synchronisierungsstatus-Visualisierung, Conversion-Daten-Loop, Flutter/HarmonyOS-CI-Build, Multi-Tenant-SaaS-Kontingente | Noch nicht gestartet |
