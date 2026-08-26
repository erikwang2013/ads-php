# Flutter Desktop Cross-Platform Support — Design Spec

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

Fecha: 2026-05-18
Estado: aprobado

## Objetivo

Extender el proyecto Flutter existente `apps/flutter/` para soportar iPadOS, macOS, Windows y Linux como plataformas de escritorio de primera clase, usando un estilo clásico de panel de administración de escritorio (inspirado en Ant Design Pro / Element UI). El soporte web se mantiene y se actualiza al mismo diseño de escritorio.

## Plataformas objetivo

| Plataforma | Estado |
|----------|--------|
| Web | Se mantiene, se actualiza al diseño de escritorio |
| iPadOS | Nueva, mismo diseño que escritorio (PC de pantalla pequeña) |
| macOS | Nueva, barra de título personalizada |
| Windows | Nueva, barra de título personalizada |
| Linux | Nueva, barra de título personalizada |

## Diseño

### Arquitectura

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

### Árbol de componentes

- `DesktopShell` — contenedor de layout de nivel superior, reemplaza a `AppShell`
- `TitleBar` — barra de título personalizada: nombre de la app a la izquierda, controles de ventana (min/max/cerrar) a la derecha, arrastrar para mover
- `SideNav` — navegación lateral plegable de dos niveles, 240px expandida → 64px plegada con animación
- `BreadcrumbBar` — se genera automáticamente a partir de la ruta mediante la configuración de menú compartida
- `AppShell`, `TopBar`, `BottomBar` — **eliminados**

### Configuración de menú de dos niveles

Un único archivo de datos `menu_config.dart` impulsa tanto el renderizado de `SideNav` como la generación de rutas de `GoRouter`:

```
/dashboard          → 仪表盘 (top-level)
/campaigns/list     → 广告管理 > 广告计划 (2nd level)
/campaigns/creative → 广告管理 > 创意管理 (2nd level)
/reports            → 数据报表 (top-level)
/accounts           → 平台账户 (top-level)
/alerts             → 告警管理 (top-level)
```

### Enrutamiento

`ShellRoute` de `GoRouter` envuelve las rutas con `DesktopShell`. Las rutas anidadas bajo `/campaigns` se corresponden con el grupo de menú de dos niveles.

### Comportamiento responsive

Sin ramificación por plataforma. Un único diseño se adapta al ancho de la ventana:

| Ancho | Comportamiento |
|-------|----------|
| ≥ 1024px | Barra lateral expandida, escritorio completo |
| 768–1023px | Barra lateral plegada por defecto |
| < 768px | Barra lateral plegada, padding de contenido reducido |
| Ventana mínima | 680×480 |

### Pila tecnológica (sin cambios)

- Estado: Riverpod
- Enrutamiento: GoRouter
- HTTP: Dio
- Gráficos: fl_chart
- Nueva dependencia: `window_manager` ^0.3.0 para controles de ventana

## Cambios de archivos

| Acción | Archivo | Notas |
|--------|------|-------|
| Reescritura | `lib/features/shell/app_shell.dart` | Nuevo `DesktopShell` |
| Reescritura | `lib/features/shell/side_nav.dart` | Dos niveles + plegable |
| Nuevo | `lib/features/shell/title_bar.dart` | Barra de título personalizada |
| Nuevo | `lib/features/shell/breadcrumb.dart` | Widget de migas de pan |
| Eliminar | `lib/features/shell/top_bar.dart` | Barra superior antigua |
| Nuevo | `lib/config/menu_config.dart` | Datos de menú compartidos |
| Modificar | `lib/router.dart` | DesktopShell + rutas anidadas |
| Modificar | `lib/main.dart` | Inicializar window_manager |
| Modificar | `lib/theme.dart` | Tema orientado a escritorio |
| Modificar | `pubspec.yaml` | Añadir dependencia window_manager |
| Generar | `macos/`, `windows/`, `linux/` | Runners de plataforma |
| Modificar | `macos/Runner/MainFlutterWindow.swift` | Ocultar barra de título nativa |
| Modificar | `windows/runner/main.cpp` | Ocultar barra de título nativa |
| Modificar | `linux/my_application.cc` | Ocultar barra de título nativa |

Las páginas de funcionalidad de negocio (6 archivos bajo `lib/features/`) — **sin cambios**.

## Límites del alcance

- Dentro del alcance: diseño del shell, navegación, barra de título, configuración de plataforma
- Fuera del alcance: nuevas funcionalidades de negocio, cambios de backend, CI/CD, pantalla de presentación, icono de la app
