# Phase 6: Erik Stack-Architektur-Refactoring

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> Umfassendes Refactoring: Datenbankpräfix, ID-System, Verschlüsselungssystem, Copyright, Codekonventionen

## Änderungsliste

| # | Änderung | Paket | Auswirkungsbereich |
|---|------|----|---------|
| 1 | Datenbank-Tabellenpräfix `erik_` | — | Alle SQL-/Migrationsdateien |
| 2 | Primärschlüssel Snowflake-ID (ohne Auto-Increment) | erikwang2013/snowflake-php | Alle Modelle + SQL |
| 3 | API-ID hashids Ver-/Entschlüsselung | erikwang2013/hashids | Alle Controller-Antworten |
| 4 | Umstellung auf JWT-Authentifizierung | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | Ver-/Entschlüsselung sensibler API-Daten | erikwang2013/encryption | API-Request-/Response-Schicht |
| 6 | Ver-/Entschlüsselung sensibler DB-Daten | erikwang2013/encryptable | Eloquent-Model-Ebene |
| 7 | ES-Datensynchronisierung/-abfrage | erikwang2013/webman-scout | Berichtssuche |
| 8 | Länderflaggen | erikwang2013/season | Frontend-Plattform-Badges |
| 9 | Copyright-Hinweis | — | Kopf aller Dateien |
| 10 | Globales `\`-Präfix entfernen | — | Alle PHP-Dateien |
| 11 | Kommentare in Konfigurationsdateien | — | config/*.php |
| 12 | Flutter Web PC-Layout | — | Flutter-Projekt |
| 13 | Visualisierungsverbesserung Admin-Panel | — | Dashboard-Diagramme |
| 14 | PDF-Export der Panel-Daten | — | Neues Exportformat |
| 15 | Excel-Export (Client+Admin) | — | Export erweitern |
| 16 | HarmonyOS-App | — | Neues HarmonyOS-Projekt |

## Implementierungsreihenfolge

**Batch A: Infrastruktur (Abhängigkeiten + ID + Verschlüsselung)**
- composer.json aktualisieren, 6 erikwang2013-Pakete hinzufügen
- Alle SQL-Migrationsdateien neu schreiben (erik_-Präfix + bigint ohne Auto-Increment)
- Snowflake-ID-Trait erstellen
- Alle Modelle aktualisieren (SnowflakeTrait verwenden)
- hashids-Middleware konfigurieren
- JWT auf jwt-webman umstellen

**Batch B: Code-Bereinigung**
- Alle `\`-Globalpräfixe entfernen
- Copyright-Header zu allen Dateien hinzufügen
- Kommentare in Konfigurationsdateien

**Batch C: Frontend-Erweiterungen**
- Visualisierungsverbesserung Admin-Panel (mehr Diagramme, Echtzeitdaten)
- PDF-Export der Panel-Daten
- Excel-Export erweitern

**Batch D: Flutter + HarmonyOS**
- Flutter Web PC-Layout-Projekt
- HarmonyOS-Projektskelett
