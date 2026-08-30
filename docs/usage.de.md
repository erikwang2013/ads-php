# Bedienungsanleitung

[中文](docs/usage.md) | [English](docs/usage.en.md) | [한국어](docs/usage.ko.md) | [Русский](docs/usage.ru.md) | [Deutsch](docs/usage.de.md) | [Français](docs/usage.fr.md) | [Español](docs/usage.es.md) | [Português](docs/usage.pt.md) | [हिन्दी](docs/usage.hi.md) | [العربية](docs/usage.ar.md) | [বাংলা](docs/usage.bn.md) | [Bahasa Indonesia](docs/usage.id.md) | [日本語](docs/usage.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Installation und Bereitstellung siehe Abschnitt „Schnellstart" der README; dieses Dokument beschreibt den vollständigen Ablauf nach der Installation.

---

## 1. Erste Anmeldung

Nach der Installation die Admin-Oberfläche öffnen:

- Ein-Klick-Installation / Docker: `http://localhost`
- Lokale Entwicklung: `http://localhost:8789`

Mit dem im Installationsassistenten festgelegten Administrator-Benutzernamen und Passwort anmelden. Nach der Anmeldung erscheint das Dashboard mit 8 KPI-Metrikkarten (Gesamtausgaben, Impressionen, Klicks, Conversions, CTR, CVR, durchschnittlicher CPC, durchschnittlicher CPA), einer Tagesverlaufs-Liniendiagramm der Ausgaben, einem Plattform-Vergleichsbalkendiagramm und den TOP-10-Kampagnen.

Passwort- und Kontodaten ändern: Systemverwaltung → Benutzerverwaltung.

---

## 2. Plattform-Autorisierung

Das System unterstützt **16 Inlands- + 13 internationale Plattformen**, alle über „Kontoverwaltung → Konto binden" autorisiert.

### OAuth2-Plattformen (die Mehrheit)

1. Auf der Seite „Konto binden" die Zielplattform wählen und „Autorisieren" klicken
2. Der Browser wechselt zur Anmeldeseite der Plattform; anmelden und Zugriff erlauben
3. Nach dem Callback speichert das System das Access Token automatisch

Autorisierte Plattformen erscheinen in der Kontoliste. Abgelaufene Tokens werden von `TokenRefreshTask` automatisch erneuert (jede Stunde zur Minute 55) — kein manueller Eingriff nötig.

### API-Key-Plattformen

Plattformen wie 360, Sogou und Umeng nutzen API-Key-Authentifizierung: API-Key (und ggf. Signaturparameter) auf der Seite „Konto binden" manuell eintragen, speichern, und die Synchronisierung beginnt.

> 16 Inlands-Plattformen: Juliang, Baidu Marketing, Taobao/Alimama, Tencent Ads, Kuaishou, Xiaohongshu, Weibo, Bilibili, Youku Ads, Meituan Ads, Zhihu Ads, 360 Promotion, Sogou Promotion, Umeng, JD, Pinduoduo Ads
>
> 13 internationale Plattformen: Google Ads, YouTube Ads, Meta Ads, TikTok Ads, LinkedIn Ads, Snapchat Ads, Pinterest Ads, Twitter/X Ads, Amazon Ads, The Trade Desk, Spotify Ads, Twitch Ads, Netflix Ads

---

## 3. Kontobindung und Upload der Kreativ-Bibliothek

### Kontoverwaltung

Nach der Autorisierung erscheinen die Konten in der Liste „Kontoverwaltung". Jedes Konto kann einzeln steuern, ob es an der Synchronisierung teilnimmt (`sync_enabled`). Die Werbehierarchie ist dreistufig: Kampagne → Anzeigengruppe → Kreativ.

### Kreativ-Bibliothek

Die „Kreativ-Bibliothek" unterstützt das Hochladen von Bild-/Video-Assets mit Galerie-Darstellung, zur Verwendung in Anzeigen. Hochgeladene Assets können optional CDN-Speicher nutzen (siehe unten).

### CDN-Speicheranbieter konfigurieren

Das System hat eine eingebaute Speicherabstraktion mit mehreren Treibern; mehrere Anbieter können gleichzeitig konfiguriert werden:

| Treiber | Beschreibung |
|---------|--------------|
| Lokaler Speicher | Standard-Treiber, speichert auf der Serverfestplatte |
| Alibaba Cloud OSS | AlibabaOssStorage |
| Tencent Cloud COS | TencentCosStorage |
| S3-kompatibel | S3CompatibleStorage (kompatibel mit AWS S3, Qiniu Cloud, MinIO usw.) |

Auf der Seite „CDN-Anbieter" einen Anbieter hinzufügen und die entsprechenden Schlüssel/Region-Parameter eintragen, dann ist er aktiv.

### Vorab signierte Uploads und Cache-Bereinigung

- **Vorab signierter Upload**: Der Server stellt für jeden Upload eine zeitlich begrenzte, vorab signierte URL (OSS/S3 PUT) aus; Browser oder mobile Clients laden Dateien direkt in den Objektspeicher hoch, am Anwendungsserver vorbei — weniger Bandbreite und Last
- **Cache-Bereinigung**: Nach Aktualisierung oder Löschung eines Assets kann die CDN-Cache-Bereinigung (Purge) ausgelöst werden, damit Clients immer den aktuellen Inhalt erhalten

---

## 4. Datensynchronisierung

Die Synchronisierung wird von 6 geplanten Aufgaben gesteuert (prozessintern über das webman-crontab-Plugin, kein externes crontab nötig):

| Aufgabe | Häufigkeit | Zuständigkeit |
|---------|------------|---------------|
| RetrySyncTask | Alle 3 Minuten | Letzte fehlgeschlagene Synchronisierung wiederholen |
| AlertCheckTask | Alle 5 Minuten | Alarmregeln auswerten |
| DataSyncTask | Alle 10 Minuten | Kampagnen/Anzeigengruppen/Kreative und Berichte synchronisieren (letzte 2 Tage, 9 Metriken) |
| BidCheckTask | Alle 10 Minuten | Automatische Gebotsregeln prüfen |
| BudgetCheckTask | Alle 15 Minuten | Budget-Warnprüfungen |
| TokenRefreshTask | Jede Stunde zur Minute 55 | Abgelaufene Plattform-Tokens erneuern |

Die Aufgabenkonfiguration liegt in `service/plugin/ads-task/config/cron.php`; Frequenzen sind dort änderbar. Der Synchronisierungsstatus ist auf der Seite „Datensynchronisierung" sichtbar; Ein-/Ausschalter pro Konto in „Kontoverwaltung".

---

## 5. Berichtsanalyse

### Dashboard

8 KPI-Metrikkarten + Tagesverlaufs-Liniendiagramm + Plattform-Vergleichsbalkendiagramm + TOP-10-Kampagnen, mit Datumsbereichsfilter und Ein-Klick-Export als PDF/Excel.

### Benutzerdefinierte Berichte

- **Dimensionen**: date, platform, campaign
- **Metriken**: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Unterstützt kombinierte Dimensionsabfragen und Sortierung

### Attributionsanalyse

Eine eingebaute plattformübergreifende Attributions-Engine unterstützt **5 Attributionsmodelle**: first_touch, last_touch, linear, time_decay, position_based, mit 30-Tage-Rückblicksfenster. Auf der Seite „Attributionsanalyse" Modell und Datumsbereich wählen, um den Beitrag jedes Kanals zu sehen.

### Kampagnenkalender

Der „Kampagnenkalender" zeigt den Auslieferungsplan jeder Kampagne in Kalenderansicht für einen schnellen Überblick über den täglichen Auslieferungsrhythmus.

### Export

Berichte unterstützen drei Exportformate:

- **CSV** (UTF-8-BOM, öffnet direkt in Excel ohne Zeichenfehler)
- **Excel** (HTML .xls)
- **PDF** (HTML-Drucklayout)

---

## 6. Alarme und Benachrichtigungen

### Alarmregeln

Regeln auf der Seite „Alarmregeln" erstellen: Überwachungsobjekt (Budget/Ausgaben/Impressionen/Klicks usw.), Schwellwert und Vergleichsart, Wirkungsbereich und Benachrichtigungskanäle wählen. Aktivierte Regeln werden von `AlertCheckTask` alle 5 Minuten ausgewertet und lösen bei Treffer aus.

### Benachrichtigungskanäle

| Kanal | Beschreibung |
|-------|--------------|
| Web | In-App-Benachrichtigungen, im „Benachrichtigungszentrum" einsehbar |
| E-Mail | Versand per E-Mail (SMTP, mit `mail()`-Fallback); Empfängeradressen in der Alarmregel konfigurieren |
| SMS | Versand per SMS |
| Webhook | POST-JSON an eine konfigurierte Callback-URL; integrierbar mit WeCom/DingTalk/Feishu usw. |

Der Alarmverlauf ist auf der Seite „Alarmprotokolle" einsehbar.

---

## 7. Mobile Apps

### Flutter-App (12 Seiten: Anmeldung/Dashboard/Konten/Kampagnen/Anzeigengruppen/Kreative/Berichte/Gebote/Alarme/Benachrichtigungen usw.)

```bash
cd apps/flutter
flutter run -d chrome     # Web-PC
flutter run -d android    # Android-Smartphone
```

### HarmonyOS-App

Das Verzeichnis `apps/harmonyos` mit DevEco Studio öffnen und ausführen.

---

## 8. Mandantenfähigkeit (Multi-Tenancy)

Das System hat ein eingebautes Multi-Tenant-Plugin (ads-tenant):

- **Tenant-Identifikation**: Die `TenantIdentify`-Middleware identifiziert den aktuellen Tenant pro Anfrage
- **Datenisolation**: Zwei Modi — gemeinsame Datenbank, isoliert über `tenant_id`, oder eine eigene Datenbank pro Tenant (`db_type`)
- **Kontingentverwaltung**: `QuotaService` validiert Tenant-Kontingente (Anzahl Konten, Assets usw.); Anfragen über dem Kontingent werden abgelehnt

---

## Verwandte Dokumente

- [Funktionsdokumentation](features.de.md) — 21 Module/Geschäftsabläufe
- [API-Referenz](api.de.md) — alle Schnittstellendefinitionen
- [Architektur](architecture.de.md) — Bereitstellung/Sicherheit/Datenmodell
