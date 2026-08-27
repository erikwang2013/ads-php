# Funktions-Design-Dokument

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Alle API-Schnittstellendefinitionen (Anfrage/Antwort/Parameter) siehe [api.md](api.de.md).

---

## Modulübersicht

| # | Modul | Controller/Service | Anzahl API-Routen | Vue-Seiten |
|---|------|--------|-----------|----------|
| 1 | Authentifizierung und Autorisierung | AuthController | 3 | LoginPage |
| 2 | Plattformverwaltung | PlatformController | 3 | — |
| 3 | Kontoverwaltung | AccountController | 5 | AccountList, AccountBind |
| 4 | Werbepläne | CampaignController | 6 | CampaignList |
| 5 | Anzeigegruppen | AdGroupController | 5 | AdGroupList |
| 6 | Werbekreative | CreativeController | 2 | CreativeList |
| 7 | Datenberichte | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | Alarmüberwachung | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | Benachrichtigungszentrum | NotificationController | 4 | NotificationList |
| 10 | Automatisches Gebot | BidRuleController | 5 | BidRuleList |
| 11 | Targeting-Vorlagen | TargetingTemplateController | 5 | — |
| 12 | Systemverwaltung | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | Datensynchronisierung | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | Materialbibliothek | AssetController | 4 | AssetGallery |
| 15 | Budget-Warnung | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | Schaltungs-Kalender | CalendarService | 1 | CampaignCalendar |
| 17 | Plattformübergreifende Attribution | AttributionEngine | 2 | AttributionReport |
| 18 | Health-Check | HealthController | 2 | — |
| 19 | Captcha | CaptchaController | 2 | — |
| 20 | API-Dokumentation | DocController | 1 | — |

**Gesamt**: 20 Module, 65+ Routen, 18 Vue-Seiten

---

## Modul 1: Authentifizierung und Autorisierung

- Captcha-Prüfung (optional)
- `admin_users`-Tabelle abfragen
- bcrypt-`password_verify()`-Verifizierung
- JWT-Token-Generierung (24h TTL)
- Alte Token automatisch zur Blacklist hinzufügen
- `uid` aus dem Token extrahieren und Benutzerinformationen abfragen

Schnittstellen: Login / Token-Refresh / aktueller Benutzer → [api.md Modul 2](api.de.md#模块-2-认证)

---

## Modul 2-3: Plattform- und Kontoverwaltung

- Plattformliste 1 Stunde cachen (Redis), Integration des Season-Flaggen-Emojis
- OAuth-Ablauf: zufälligen state generieren → Autorisierungs-URL erstellen → Callback verarbeiten → Token speichern
- Kontoliste/-details 5 Minuten cachen

Schnittstellen: Plattformliste / OAuth / Konten-CRUD + Synchronisierung → [api.md Modul 3](api.de.md#模块-3-平台--账户)

---

## Modul 4-6: Werbeschaltungsebene

### Datenstruktur

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- Planerstellung über Plattform-Adapter + lokales Schreiben
- Filterung nach Plattform/Status/Keyword unterstützt, Liste enthält Tageszusammenfassung
- Anzeigegruppen-Erstellung unterstützt Laden der Targeting-Vorlage über `targeting_template_id`

Schnittstellen: Pläne / Anzeigegruppen / Kreative → [api.md Modul 4-6](api.de.md#模块-4-广告计划)

---

## Modul 7: Datenberichte

- Dashboard-Zusammenfassung 5 Minuten cachen: 8 KPI-Kennzahlenkarten + Tagesverlauf-Liniendiagramm + Plattform-Balkendiagramm
- Benutzerdefinierte Berichtsdimensionen: date, platform, campaign
- Kennzahlen: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Exportformate: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (HTML-Druck)

Schnittstellen: Zusammenfassung / Benutzerdefiniert / Export → [api.md Modul 7](api.de.md#模块-7-报表)

---

## Modul 8: Alarmüberwachung

### AlertEngine-Auswertungsablauf

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### Benachrichtigungskanäle

| Kanal | Status | Implementierung |
|------|------|------|
| web | ✅ | schreibt in erik_notifications |
| email | Platzhalter | echo-Stub |
| sms | Platzhalter | echo-Stub |
| Redis pub/sub | ✅ | JSON-Push über Kanal `alert:new` |

Schnittstellen: Regel-CRUD / Alarmaufzeichnungen / Bestätigen / Ungelesen-Zähler → [api.md Modul 8](api.de.md#模块-8-告警)

---

## Modul 9: Benachrichtigungszentrum

- Frontend-Pinia-Store pollt alle 30s
- Glockensymbol in der Seitenleiste + Ungelesen-Zahlenbadge

Schnittstellen: Liste / Ungelesen-Zähler / Als gelesen markieren / Alle als gelesen → [api.md Modul 9](api.de.md#模块-9-通知)

---

## Modul 10: Automatische Gebots-Engine

### BidEngine-Auswertungsablauf

```
遍历 enabled=1 的规则
  → 查询 erik_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 冷却检查 (cooldown_minutes 内是否有过操作)
  → 执行动作:
    - adjust_budget: 新预算 = current + adjust_step, 限制 [budget_min, budget_max]
    - toggle_pause: 暂停计划
    - toggle_enable: 启用计划
  → 通过 AdapterRegistry → PlatformAdapter 调用平台 API
  → 更新本地 DB + 写入 BidLog
```

### Regelfelder

| Feld | Typ | Beschreibung |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Überwachte Kennzahl |
| condition | gt/gte/lt/lte | Auslösebedingung |
| threshold | DECIMAL(12,2) | Schwellenwert |
| scope | tenant/platform/campaign | Wirkungsbereich |
| action_type | adjust_budget/toggle_pause/toggle_enable | Aktion |
| adjust_step | INT (Fen) | Budget-Anpassungsschritt (positiv = erhöhen, negativ = senken) |
| budget_min, budget_max | BIGINT | Budgetgrenzen |
| cooldown_minutes | INT | Abkühlphase |

Schnittstellen: Regel-CRUD / Gebotshistorie → [api.md Modul 10](api.de.md#模块-10-自动出价)

---

## Modul 11: Zielgruppen-Targeting-Vorlagen

### Integration in Anzeigegruppen

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### Allgemeines JSON-Schema

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

Schnittstellen: Vorlagen-CRUD → [api.md Modul 11](api.de.md#模块-11-定向模板)

---

## Modul 12: Systemverwaltung (Admin)

- Benutzerlisten-ID hashids-codiert
- Benutzererstellung mit bcrypt-Hash-Passwort
- Deaktivierte Benutzer sind soft-deaktiviert (status=0)

Audit-Log-Felder: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

Schnittstellen: Benutzerverwaltung / Audit-Log / Rollen → [api.md Admin-Endpunkte](api.de.md#admin-端点端口-8789)

---

## Modul 13: Datensynchronisierung

### DataSyncTask-Ablauf (alle 10 Minuten)

```
遍历 sync_enabled=1 的账户
  → 获取平台适配器
  → 同步 Campaigns (fetchCampaigns → updateOrInsert)
  → 同步 AdGroups (fetchAdGroups → 遍历每 campaign)
  → 同步 Creatives (fetchCreatives → 遍历每 ad_group)
  → 同步 Reports (fetchReports → 过去 2 天 daily, 9 个指标)
  → 清除 Dashboard 缓存
  → 更新 last_sync_at
```

---

## Antwortformat

### Erfolg
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### Paginierung
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### Fehler
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## Modul 14: Werbematerial-Bibliothek

- Unterstützte Typen: image/jpeg, image/png, image/gif, image/webp, video/mp4
- Dateispeicherung: `public/uploads/assets/`
- Frontend: Raster-Galerie + Drag-and-Drop-Upload + Bildvorschau + Videowiedergabe + URL kopieren

Schnittstellen: Upload / Liste / Details / Löschen → [api.md Modul 12](api.de.md#模块-12-素材库)

---

## Modul 15: Budget-Warnung

- Dreistufen-Alarm: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask wird alle 15 Minuten ausgeführt
- Deduplizierung: pro Plan und Stufe nur einmal täglich benachrichtigen
- Schreiben in die Tabelle `erik_notifications`

Schnittstellen: Budget-Warnung → [api.md Modul 7](api.de.md#模块-7-报表)

---

## Modul 16: Schaltungs-Kalender

- Aggregation der Kampagnen-Planung nach Datum
- Frontend-Gantt-Diagramm: x-Achse Datum, y-Achse Pläne, nach Plattformfarbe unterschieden
- Monats-/Wochenansicht-Umschaltung unterstützt

Schnittstellen: Schaltungs-Kalender → [api.md Modul 7](api.de.md#模块-7-报表)

---

## Modul 17: Plattformübergreifende Attribution

### Attributionsmodelle

| Modell | Algorithmus |
|------|------|
| first_touch | Erster Touchpoint 100% |
| last_touch | Letzter Touchpoint 100% |
| linear | Alle Touchpoints gleichmäßig (1/N) |
| time_decay | e^(-λ×Δt), 7 Tage Halbwertszeit |
| position_based | Erste 40% + Letzte 40% + Mitte 20% |

- Rückverfolgungsfenster: 30 Tage
- Touchpoint-Quelle: `erik_report_metrics` (Klicks > 0)
- Ergebnis wird in `erik_attribution_results` geschrieben
- Frontend: AttributionReport.vue Modellumschaltung + Statistik-Karten + ECharts-Balkendiagramm + Detailtabelle

### Datenbanktabellen

| Tabelle | Felder |
|----|------|
| `erik_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `erik_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

Schnittstellen: Attributionsanalyse / Modellliste → [api.md Modul 7](api.de.md#模块-7-报表)

### Health-Check
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```

---

## Modul 18: Plattform-Aufruf-Resilienz (Circuit Breaker / Degradierung)

### Zustandsmaschine des Circuit Breakers

`CircuitBreaker` (service/plugin/ads-platform/src/CircuitBreaker.php) — Zustand pro Plattform:

| Zustand | Auslöser | Verhalten |
|---------|----------|-----------|
| CLOSED | Normal | Aufrufe passieren |
| OPEN | 5 aufeinanderfolgende Fehler | Fast-Fail, Plattform überspringen |
| HALF_OPEN | Nach 30s Abkühlung | Ein Probe-Request erlaubt |
| CLOSED | Probe erfolgreich | Wiederhergestellt, Zähler zurückgesetzt |
| OPEN | Probe erneut fehlgeschlagen | Erneut auslösen |

### GuardedAdapter-Proxy

- `AdapterRegistry::get()` gibt einen GuardedAdapter-Proxy zurück; 14 Aufrufstellen ohne Änderung
- Bei OPEN wird `CircuitBreakerOpenException` geworfen (Fast-Fail); Task-Ebene fängt und absorbiert = plattformweises Degradieren
- Generator-Methode: vollständige Iteration → success, Abbruch → failure

### Timeout-Prüfung

- Alle 29 Adapter enthalten CURLOPT_TIMEOUT (30/60s) + CURLOPT_CONNECTTIMEOUT (10s)

### Testabdeckung

- CircuitBreakerTest 8 Fälle + GuardedAdapterTest 13 Fälle

### Bekannte Einschränkung

- In-Memory-Zustand auf einem Knoten; Multi-Node-Betrieb benötigt Redis-Shared-State
