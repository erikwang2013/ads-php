# Phase 8: Implementierungsplan Alarm-Mehrkanal-Umsetzung

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**Ziel:** Die aus Phase 5 verbliebene Lücke schließen — die email/sms-Kanäle des `NotificationService` von echo-Stubs zu echten Implementierungen (SMTP-E-Mail + allgemeiner Webhook) aufwerten und Kanal-Konfiguration unterstützen. Der web-Kanal und Redis pub/sub sind bereits implementiert und bleiben unverändert.

**Quelle:** Phase-7-Team-Audit-Ergebnis (researcher-Planungsvergleich: einzig klar als „teilweise abgeschlossen" markierter Posten = Phase 5 Alarm-Mehrkanal, `ads-alert` fehlt das Verzeichnis `channel/`)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## Ist-Zustand (verifiziert)

| Komponente | Status |
|---|---|
| `NotificationService::send()` | `match ($channel)` verteilt web/email/sms; web schreibt real in `erik_notifications`, email/sms sind echo-Stubs |
| `AlertRule.channels` | JSON-Feld + Eloquent cast array, Frontend sendet bereits `['web','email','sms']` |
| Admin AlertRuleList.vue | Kanal-Auswahl-UI vorhanden (web gesperrt, email/sms wählbar) |
| Redis pub/sub | Push über Kanal `alert:new` implementiert |
| SMTP/E-Mail-Konfiguration | Keine (keine mail-Konfiguration in service/config) |

## Task 1: E-Mail-Kanal (SMTP)

### Dateien:
- Erstellen: `service/config/mail.php` (smtp host/port/user/pass/from/from_name/encryption, env-gesteuert)
- Erstellen: `service/plugin/ads-alert/service/channel/EmailChannel.php` (implementiert send(AlertLog, AlertRule))
- Ändern: `service/plugin/ads-alert/service/NotificationService.php` (email-Zweig ruft EmailChannel auf, echo-Stub entfernen)
- Ändern: `service/composer.json` (bei Wahl von PHPMailer Abhängigkeit ergänzen; bevorzugt abhängigkeitsfreie `mail()`/Socket-Implementierung zur Gewichtsminimierung, Bewertung durch den Implementierer)

### Design-Highlights
- Empfänger: aus AlertRule-Konfiguration oder Mandantenkonfiguration lesen (fehlt sie, `email`-Feld oder Konfigurationsstandard verwenden)
- Betreff/Inhalt: Textvorlage von sendWeb wiederverwenden („告警触发: {rule.name}" + Metrik/aktueller Wert/Bedingung/Schwellenwert)
- Fehlerbehandlung: Ausnahmen abfangen und protokollieren, andere Kanäle und Hauptablauf nicht beeinträchtigen
- Bei fehlender Konfiguration elegantes Degradieren (log-Hinweis, keine Ausnahme, die den Ablauf unterbricht)

## Task 2: Webhook-Kanal

### Dateien:
- Erstellen: `service/plugin/ads-alert/service/channel/WebhookChannel.php` (POST JSON an die konfigurierte URL)
- Ändern: `NotificationService::send()` match um `'webhook'`-Zweig erweitern

### Design-Highlights
- Konfigurationsquelle: AlertRule um Feld `webhook_url` erweitern (migration) oder channels-Konfiguration; für minimalen Aufwand bevorzugt Spalte `webhook_url` (nullable) in AlertRule ergänzen
- Payload: `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`, mit Alarmstufe/Metrik/Wert/Schwellenwert/Zeit
- Timeout und Wiederholung: Verbindungs-Timeout 5s, Gesamt-Timeout 10s, Fehler protokollieren (keine Wiederholung, einfach halten)
- Sicherheit: nur http/https erlauben, keine Innen-IP-Adressprüfung (SSRF-Risiko als bekannte Einschränkung dokumentieren, oder Nicht-Innen-Netz prüfen — Bewertung und Dokumentation durch den Implementierer)

## Task 3: SMS-Kanal (Gateway-Platzhalter)

### Dateien:
- Ändern: `NotificationService::sendSms` (Platzhalter behalten, Einstiegspunkt klar kommentieren; falls der Implementierer eine leichte Lösung findet, kann sie umgesetzt werden)

### Design-Highlights
- SMS-Gateways (Aliyun/Tencent Cloud) erfordern AK/SK und Bezahlung; in dieser Phase Platzhalter-Implementierung behalten, Anschlussschritte im Kommentar dokumentieren
- Die sms-Option im Frontend-UI bleibt wählbar, aber das Backend protokolliert nur (dem Benutzer klar mitteilen, dass kein Gateway konfiguriert ist)

## Task 4: Kanalkonfiguration und Frontend

### Dateien:
- Ändern: `admin/public/web/src/views/alert/AlertRuleList.vue` (falls Webhook-Option und URL-Eingabe ergänzt werden)
- Ändern: `service/plugin/ads-api/controller/v1/AlertController.php` (Regel-Erstellung/-Aktualisierung akzeptiert webhook_url)
- Ändern: `service/plugin/ads-alert/model/AlertRule.php` (fillable/casts um webhook_url erweitern)
- Ändern: `service/plugin/ads-alert/migration/create_alerts.sql` (ALTER oder Inkrementell-Skript erläutern)

### Abnahme
- [ ] email-Kanal: Nach SMTP-Konfiguration erhält man bei Alarmauslösung E-Mail; ohne Konfiguration elegantes Degradieren
- [ ] webhook-Kanal: Bei Alarmauslösung POST JSON an die konfigurierte URL, Payload-Felder vollständig
- [ ] sms-Kanal: Platzhalter behalten, protokolliert
- [ ] Regression von web-Kanal und Redis pub/sub nicht beeinträchtigt
- [ ] Admin-Regelformular kann die neuen Kanalfelder konfigurieren
- [ ] `php vendor/bin/phpunit --no-coverage` komplett bestanden
- [ ] Neue/aktualisierte Tests: Kanalverteilungstests für AlertEngine/NotificationService
