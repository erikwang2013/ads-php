# Support multiplateforme Flutter Desktop — Spécification de conception

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

Date : 2026-05-18
Statut : approuvé

## Objectif

Étendre le projet Flutter existant `apps/flutter/` pour prendre en charge iPadOS, macOS, Windows et Linux en tant que plateformes de bureau de premier ordre, en utilisant un style d'interface de panneau d'administration classique (inspiré d'Ant Design Pro / Element UI). Le support Web est conservé et mis à niveau vers la même disposition de bureau.

## Plateformes cibles

| Plateforme | Statut |
|----------|--------|
| Web | Conservé, mise à niveau vers la disposition de bureau |
| iPadOS | Nouveau, même disposition que le bureau (petit écran PC) |
| macOS | Nouveau, barre de titre personnalisée |
| Windows | Nouveau, barre de titre personnalisée |
| Linux | Nouveau, barre de titre personnalisée |

## Conception

### Architecture

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

### Arborescence des composants

- `DesktopShell` — conteneur de disposition de niveau supérieur, remplace `AppShell`
- `TitleBar` — barre de titre personnalisée : nom de l'application à gauche, contrôles de fenêtre (réduire/agrandir/fermer) à droite, glisser pour déplacer
- `SideNav` — navigation latérale à deux niveaux repliable, 240px déplié → 64px replié avec animation
- `BreadcrumbBar` — généré automatiquement à partir du chemin de route via la configuration de menu partagée
- `AppShell`, `TopBar`, `BottomBar` — **supprimés**

### Configuration du menu à deux niveaux

Un seul fichier de données `menu_config.dart` pilote à la fois le rendu de `SideNav` et la génération des routes `GoRouter` :

```
/dashboard          → 仪表盘 (top-level)
/campaigns/list     → 广告管理 > 广告计划 (2nd level)
/campaigns/creative → 广告管理 > 创意管理 (2nd level)
/reports            → 数据报表 (top-level)
/accounts           → 平台账户 (top-level)
/alerts             → 告警管理 (top-level)
```

### Routage

`GoRouter` `ShellRoute` enveloppe les routes avec `DesktopShell`. Les routes imbriquées sous `/campaigns` correspondent au groupe de menu à deux niveaux.

### Comportement responsive

Aucune branchement par plateforme. Une seule disposition s'adapte à la largeur de la fenêtre :

| Largeur | Comportement |
|-------|----------|
| ≥ 1024px | Barre latérale dépliée, bureau complet |
| 768–1023px | Barre latérale repliée par défaut |
| < 768px | Barre latérale repliée, padding de contenu réduit |
| Fenêtre minimale | 680×480 |

### Pile technique (aucun changement)

- État : Riverpod
- Routage : GoRouter
- HTTP : Dio
- Graphiques : fl_chart
- Nouvelle dépendance : `window_manager` ^0.3.0 pour les contrôles de fenêtre

## Modifications de fichiers

| Action | Fichier | Notes |
|--------|------|-------|
| Réécriture | `lib/features/shell/app_shell.dart` | Nouveau `DesktopShell` |
| Réécriture | `lib/features/shell/side_nav.dart` | Deux niveaux + repliable |
| Nouveau | `lib/features/shell/title_bar.dart` | Barre de titre personnalisée |
| Nouveau | `lib/features/shell/breadcrumb.dart` | Widget fil d'Ariane |
| Suppression | `lib/features/shell/top_bar.dart` | Ancienne barre supérieure |
| Nouveau | `lib/config/menu_config.dart` | Données de menu partagées |
| Modification | `lib/router.dart` | DesktopShell + routes imbriquées |
| Modification | `lib/main.dart` | Initialisation de window_manager |
| Modification | `lib/theme.dart` | Thème orienté bureau |
| Modification | `pubspec.yaml` | Ajout de la dépendance window_manager |
| Génération | `macos/`, `windows/`, `linux/` | Runners de plateforme |
| Modification | `macos/Runner/MainFlutterWindow.swift` | Masquer la barre de titre native |
| Modification | `windows/runner/main.cpp` | Masquer la barre de titre native |
| Modification | `linux/my_application.cc` | Masquer la barre de titre native |

Les pages de fonctionnalités métier (6 fichiers sous `lib/features/`) — **aucune modification**.

## Limites du périmètre

- Dans le périmètre : disposition de l'interface, navigation, barre de titre, configuration de plateforme
- Hors périmètre : nouvelles fonctionnalités métier, modifications backend, CI/CD, écran de démarrage, icône d'application
