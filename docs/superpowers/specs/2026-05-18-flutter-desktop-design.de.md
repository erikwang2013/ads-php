# Flutter-Desktop-Cross-Platform-Unterstützung — Designspezifikation

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

Datum: 2026-05-18
Status: genehmigt

## Ziel

Das bestehende Flutter-Projekt `apps/flutter/` erweitern, um iPadOS, macOS, Windows und Linux als erstklassige Desktop-Plattformen zu unterstützen, mit klassischem Desktop-Admin-Panel-UI-Stil (inspiriert von Ant Design Pro / Element UI). Die Web-Unterstützung bleibt erhalten und wird auf dasselbe Desktop-Layout aufgewertet.

## Zielplattformen

| Plattform | Status |
|----------|--------|
| Web | Behalten, auf Desktop-Layout aufwerten |
| iPadOS | Neu, gleiches Layout wie Desktop (kleinbildschirm-PC) |
| macOS | Neu, benutzerdefinierte Titelleiste |
| Windows | Neu, benutzerdefinierte Titelleiste |
| Linux | Neu, benutzerdefinierte Titelleiste |

## Design

### Architektur

```
┌─────────────────────────────────────────────────┐
│  TitleBar (custom)            ─  ⬜  × │  48px  │
├──────────┬──────────────────────────────────────┤
│          │  BreadcrumbBar                       │  40px
│ SideNav  ├──────────────────────────────────────┤
│          │                                      │
│ 240px    │  Content Area (child)                │  fill
│          │                                      │
│ collapsed│                                      │
│  64px    │                                      │
├──────────┴──────────────────────────────────────┤
│  StatusBar (optional)                           │  24px
└─────────────────────────────────────────────────┘
```

### Komponentenbaum

- `DesktopShell` — Layout-Container der obersten Ebene, ersetzt `AppShell`
- `TitleBar` — benutzerdefinierte Titelleiste: App-Name links, Fenstersteuerung (min/max/close) rechts, per Drag verschiebbar
- `SideNav` — einklappbare zweistufige Seitennavigation, 240px erweitert → 64px eingeklappt mit Animation
- `BreadcrumbBar` — automatisch aus dem Routenpfad über die gemeinsame Menükonfiguration generiert
- `AppShell`, `TopBar`, `BottomBar` — **entfernt**

### Zweistufige Menükonfiguration

Eine einzige Datendatei `menu_config.dart` steuert sowohl das Rendering von `SideNav` als auch die Routengenerierung von `GoRouter`:

```
/dashboard          → 仪表盘 (top-level)
/campaigns/list     → 广告管理 > 广告计划 (2nd level)
/campaigns/creative → 广告管理 > 创意管理 (2nd level)
/reports            → 数据报表 (top-level)
/accounts           → 平台账户 (top-level)
/alerts             → 告警管理 (top-level)
```

### Routing

Die `ShellRoute` von `GoRouter` umschließt die Routen mit `DesktopShell`. Verschachtelte Routen unter `/campaigns` werden der zweistufigen Menügruppe zugeordnet.

### Responsives Verhalten

Keine Plattform-Verzweigung. Ein einziges Layout passt sich der Fensterbreite an:

| Breite | Verhalten |
|-------|----------|
| ≥ 1024px | Sidebar erweitert, volles Desktop |
| 768–1023px | Sidebar standardmäßig eingeklappt |
| < 768px | Sidebar eingeklappt, reduziertes Content-Padding |
| Mindestfenster | 680×480 |

### Technologie-Stack (keine Änderungen)

- State: Riverpod
- Routing: GoRouter
- HTTP: Dio
- Diagramme: fl_chart
- Neue Abhängigkeit: `window_manager` ^0.3.0 für Fenstersteuerung

## Dateiänderungen

| Aktion | Datei | Anmerkungen |
|--------|------|-------|
| Neu schreiben | `lib/features/shell/app_shell.dart` | Neue `DesktopShell` |
| Neu schreiben | `lib/features/shell/side_nav.dart` | Zweistufig + einklappbar |
| Neu | `lib/features/shell/title_bar.dart` | Benutzerdefinierte Titelleiste |
| Neu | `lib/features/shell/breadcrumb.dart` | Breadcrumb-Widget |
| Löschen | `lib/features/shell/top_bar.dart` | Alte obere Leiste |
| Neu | `lib/config/menu_config.dart` | Gemeinsame Menüdaten |
| Ändern | `lib/router.dart` | DesktopShell + verschachtelte Routen |
| Ändern | `lib/main.dart` | window_manager initialisieren |
| Ändern | `lib/theme.dart` | Desktop-orientiertes Theme |
| Ändern | `pubspec.yaml` | window_manager-Abhängigkeit hinzufügen |
| Generieren | `macos/`, `windows/`, `linux/` | Plattform-Runner |
| Ändern | `macos/Runner/MainFlutterWindow.swift` | Native Titelleiste ausblenden |
| Ändern | `windows/runner/main.cpp` | Native Titelleiste ausblenden |
| Ändern | `linux/my_application.cc` | Native Titelleiste ausblenden |

Business-Funktionsseiten (6 Dateien unter `lib/features/`) — **keine Änderungen**.

## Umfangsgrenzen

- Im Umfang: Shell-Layout, Navigation, Titelleiste, Plattformkonfiguration
- Außerhalb des Umfangs: neue Business-Funktionen, Backend-Änderungen, CI/CD, Splash-Screen, App-Symbol
