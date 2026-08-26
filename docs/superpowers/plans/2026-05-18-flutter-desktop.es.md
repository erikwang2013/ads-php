# Soporte Multiplataforma de Escritorio Flutter — Plan de Implementación

[中文](docs/superpowers/plans/2026-05-18-flutter-desktop.md) | [English](docs/superpowers/plans/2026-05-18-flutter-desktop.en.md) | [한국어](docs/superpowers/plans/2026-05-18-flutter-desktop.ko.md) | [Русский](docs/superpowers/plans/2026-05-18-flutter-desktop.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-18-flutter-desktop.de.md) | [Français](docs/superpowers/plans/2026-05-18-flutter-desktop.fr.md) | [Español](docs/superpowers/plans/2026-05-18-flutter-desktop.es.md) | [Português](docs/superpowers/plans/2026-05-18-flutter-desktop.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-18-flutter-desktop.hi.md) | [العربية](docs/superpowers/plans/2026-05-18-flutter-desktop.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-18-flutter-desktop.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-18-flutter-desktop.id.md) | [日本語](docs/superpowers/plans/2026-05-18-flutter-desktop.ja.md)

> **Para trabajadores agénticos:** SUB-HABILIDAD OBLIGATORIA: Usa superpowers:subagent-driven-development (recomendado) o superpowers:executing-plans para implementar este plan tarea por tarea. Los pasos usan la sintaxis de casillas de verificación (`- [ ]`) para el seguimiento.

**Objetivo:** Extender la app Flutter a iPadOS, macOS, Windows y Linux con una UI clásica de panel de administración de escritorio (barra lateral de dos niveles, barra de título personalizada, migas de pan).

**Arquitectura:** Reescribir la capa de shell (AppShell → DesktopShell, SideNav, TitleBar, BreadcrumbBar) alrededor de una configuración de menú compartida. Las páginas de funciones de negocio (dashboard, campaigns, reports, accounts, alerts) no se tocan. GoRouter ShellRoute envuelve todas las rutas en DesktopShell. Un único archivo de datos `menu_config.dart` impulsa tanto el renderizado de la navegación como la generación de migas de pan.

**Tech Stack:** Flutter 3.2+, Dart, Riverpod, GoRouter, Dio, fl_chart, window_manager ^0.3.0

---

### Tarea 1: Añadir la dependencia window_manager y generar los directorios de plataforma de escritorio

**Files:**
- Modificar: `apps/flutter/pubspec.yaml`
- Generar: `apps/flutter/macos/`, `apps/flutter/windows/`, `apps/flutter/linux/`

- [x] **Paso 1: Añadir window_manager a pubspec.yaml**

En `apps/flutter/pubspec.yaml`, añadir `window_manager: ^0.3.0` bajo dependencies:

```yaml
dependencies:
  flutter:
    sdk: flutter
  flutter_riverpod: ^2.4.0
  go_router: ^13.0.0
  dio: ^5.4.0
  fl_chart: ^0.66.0
  intl: ^0.19.0
  shared_preferences: ^2.2.0
  flutter_svg: ^2.0.0
  window_manager: ^0.3.0
```

- [x] **Paso 2: Ejecutar flutter pub get**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter pub get
```

Esperado: Se resuelve sin errores.

- [x] **Paso 3: Generar los directorios de plataforma de escritorio**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter create --platforms=macos,windows,linux .
```

Esperado: Se crean los directorios `macos/`, `windows/`, `linux/`. El comando usa `.` para apuntar al proyecto existente (sin sobrescribir lib/).

- [x] **Paso 4: Verificar que existen los directorios de plataforma**

```bash
ls -d /home/wwwroot/ads-php/apps/flutter/macos /home/wwwroot/ads-php/apps/flutter/windows /home/wwwroot/ads-php/apps/flutter/linux
```

Esperado: Se imprimen las tres rutas de directorio.

- [x] **Paso 5: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add pubspec.yaml pubspec.lock macos/ windows/ linux/ && git commit -m "chore: add window_manager dep and generate desktop platform dirs"
```

---

### Tarea 2: Crear la configuración de menú compartida

**Files:**
- Crear: `apps/flutter/lib/config/menu_config.dart`

- [x] **Paso 1: Crear el directorio config**

```bash
mkdir -p /home/wwwroot/ads-php/apps/flutter/lib/config
```

- [x] **Paso 2: Escribir menu_config.dart**

```dart
import 'package:flutter/material.dart';

class MenuItem {
  final String label;
  final String? path;
  final IconData icon;
  final List<MenuItem>? children;

  const MenuItem({
    required this.label,
    this.path,
    required this.icon,
    this.children,
  });

  bool get hasChildren => children != null && children!.isNotEmpty;
}

const List<MenuItem> menuConfig = [
  MenuItem(label: '仪表盘', path: '/dashboard', icon: Icons.dashboard),
  MenuItem(label: '广告管理', icon: Icons.campaign, children: [
    MenuItem(label: '广告计划', path: '/campaigns/list', icon: Icons.list_alt),
    MenuItem(label: '创意管理', path: '/campaigns/creative', icon: Icons.palette),
  ]),
  MenuItem(label: '数据报表', path: '/reports', icon: Icons.bar_chart),
  MenuItem(label: '平台账户', path: '/accounts', icon: Icons.person),
  MenuItem(label: '告警管理', path: '/alerts', icon: Icons.notifications),
];

/// Build breadcrumb trail for a given route path.
/// Returns the chain of MenuItems from root to the matched item.
List<MenuItem> buildBreadcrumb(String path) {
  for (final item in menuConfig) {
    if (item.path == path) return [item];
    if (item.hasChildren) {
      for (final child in item.children!) {
        if (child.path == path) return [item, child];
      }
    }
  }
  return [];
}

/// Find the label for a given route path. Returns empty string if not found.
String routeLabel(String path) {
  for (final item in menuConfig) {
    if (item.path == path) return item.label;
    if (item.hasChildren) {
      for (final child in item.children!) {
        if (child.path == path) return child.label;
      }
    }
  }
  return '';
}
```

- [x] **Paso 3: Verificar que el archivo compila comprobando la sintaxis**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/config/menu_config.dart
```

Esperado: No se encuentran problemas.

- [x] **Paso 4: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/config/ && git commit -m "feat: add shared menu config with two-level structure"
```

---

### Tarea 3: Escribir el widget TitleBar

**Files:**
- Crear: `apps/flutter/lib/features/shell/title_bar.dart`

- [x] **Paso 1: Escribir title_bar.dart**

```dart
import 'package:flutter/material.dart';
import 'package:window_manager/window_manager.dart';

class TitleBar extends StatelessWidget {
  const TitleBar({super.key});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.translucent,
      onPanStart: (_) => windowManager.startDragging(),
      child: SizedBox(
        height: 40,
        child: ColoredBox(
          color: Theme.of(context).colorScheme.surface,
          child: Row(
            children: [
              const SizedBox(width: 16),
              Icon(Icons.ads_click, size: 18,
                  color: Theme.of(context).colorScheme.primary),
              const SizedBox(width: 8),
              const Text('广告管理系统', style: TextStyle(fontSize: 13)),
              const Spacer(),
              _WindowButton(
                icon: Icons.minimize,
                onTap: () => windowManager.minimize(),
              ),
              _WindowButton(
                icon: Icons.crop_square,
                onTap: () => windowManager.maximize(),
              ),
              _WindowButton(
                icon: Icons.close,
                onTap: () => windowManager.close(),
                isClose: true,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _WindowButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;
  final bool isClose;

  const _WindowButton({
    required this.icon,
    required this.onTap,
    this.isClose = false,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: SizedBox(
        width: 46,
        height: 40,
        child: Icon(
          icon,
          size: 18,
          color: isClose
              ? Theme.of(context).colorScheme.error
              : Theme.of(context).colorScheme.onSurface,
        ),
      ),
    );
  }
}
```

- [x] **Paso 2: Verificar la compilación**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/title_bar.dart
```

Esperado: No se encuentran problemas.

- [x] **Paso 3: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/title_bar.dart && git commit -m "feat: add custom TitleBar with window controls"
```

---

### Tarea 4: Escribir el widget BreadcrumbBar

**Files:**
- Crear: `apps/flutter/lib/features/shell/breadcrumb.dart`

- [x] **Paso 1: Escribir breadcrumb.dart**

```dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../config/menu_config.dart';

class BreadcrumbBar extends StatelessWidget {
  const BreadcrumbBar({super.key});

  @override
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).uri.path;
    final trail = buildBreadcrumb(location);

    return SizedBox(
      height: 36,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        child: Row(
          children: [
            for (int i = 0; i < trail.length; i++) ...[
              if (i > 0)
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 4),
                  child: Icon(Icons.chevron_right, size: 16, color: Colors.grey),
                ),
              if (i == trail.length - 1)
                Text(trail[i].label,
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500))
              else
                GestureDetector(
                  onTap: () {
                    if (trail[i].path != null) context.go(trail[i].path!);
                  },
                  child: Text(
                    trail[i].label,
                    style: TextStyle(
                      fontSize: 13,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                  ),
                ),
            ],
          ],
        ),
      ),
    );
  }
}
```

- [x] **Paso 2: Verificar la compilación**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/breadcrumb.dart
```

Esperado: No se encuentran problemas.

- [x] **Paso 3: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/breadcrumb.dart && git commit -m "feat: add BreadcrumbBar driven by menu config"
```

---

### Tarea 5: Reescribir SideNav con menú de dos niveles y colapso

**Files:**
- Reescribir: `apps/flutter/lib/features/shell/side_nav.dart`

- [x] **Paso 1: Escribir el nuevo side_nav.dart**

```dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../config/menu_config.dart';

class SideNav extends StatefulWidget {
  const SideNav({super.key});

  @override
  State<SideNav> createState() => _SideNavState();
}

class _SideNavState extends State<SideNav> {
  bool _collapsed = false;

  void _toggle() => setState(() => _collapsed = !_collapsed);

  @override
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).uri.path;

    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      width: _collapsed ? 64 : 240,
      child: Column(
        children: [
          SizedBox(
            height: 48,
            child: _collapsed
                ? const Icon(Icons.ads_click, size: 22, color: Colors.blue)
                : const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 16),
                    child: Row(
                      children: [
                        Icon(Icons.ads_click, size: 20, color: Colors.blue),
                        SizedBox(width: 8),
                        Text('广告管理系统',
                            style: TextStyle(
                                fontSize: 15, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
          ),
          const Divider(height: 1),
          Expanded(
            child: ListView(
              padding: EdgeInsets.zero,
              children: menuConfig
                  .map((item) => _SideNavGroup(
                        item: item,
                        location: location,
                        collapsed: _collapsed,
                      ))
                  .toList(),
            ),
          ),
          const Divider(height: 1),
          IconButton(
            icon: Icon(_collapsed ? Icons.menu_open : Icons.menu, size: 20),
            onPressed: _toggle,
            tooltip: _collapsed ? '展开菜单' : '收起菜单',
            padding: const EdgeInsets.symmetric(vertical: 12),
          ),
          if (!_collapsed)
            const Padding(
              padding: EdgeInsets.fromLTRB(0, 0, 0, 12),
              child: Text('Copyright (c) 2026 erik',
                  style: TextStyle(fontSize: 10, color: Colors.grey)),
            ),
        ],
      ),
    );
  }
}

class _SideNavGroup extends StatefulWidget {
  final MenuItem item;
  final String location;
  final bool collapsed;

  const _SideNavGroup({
    required this.item,
    required this.location,
    required this.collapsed,
  });

  @override
  State<_SideNavGroup> createState() => _SideNavGroupState();
}

class _SideNavGroupState extends State<_SideNavGroup> {
  bool _expanded = false;

  bool get _active => widget.item.path == widget.location ||
      (widget.item.hasChildren &&
          widget.item.children!.any((c) => c.path == widget.location));

  @override
  void initState() {
    super.initState();
    _expanded = widget.item.hasChildren &&
        widget.item.children!.any((c) => widget.location.startsWith(c.path!));
  }

  @override
  void didUpdateWidget(covariant _SideNavGroup old) {
    super.didUpdateWidget(old);
    if (old.location != widget.location) {
      _expanded = widget.item.hasChildren &&
          widget.item.children!
              .any((c) => widget.location.startsWith(c.path!));
    }
  }

  @override
  Widget build(BuildContext context) {
    final item = widget.item;

    if (item.hasChildren) {
      return Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          _NavTile(
            icon: item.icon,
            label: item.label,
            active: _active,
            collapsed: widget.collapsed,
            trailing: widget.collapsed
                ? null
                : Icon(
                    _expanded ? Icons.expand_less : Icons.expand_more,
                    size: 18,
                  ),
            onTap: () => setState(() => _expanded = !_expanded),
          ),
          if (_expanded && !widget.collapsed)
            ...item.children!.map((child) => _NavTile(
                  icon: child.icon,
                  label: child.label,
                  active: child.path == widget.location,
                  collapsed: false,
                  indent: true,
                  onTap: () => context.go(child.path!),
                )),
        ],
      );
    }

    return _NavTile(
      icon: item.icon,
      label: item.label,
      active: _active,
      collapsed: widget.collapsed,
      onTap: () => context.go(item.path!),
    );
  }
}

class _NavTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool active;
  final bool collapsed;
  final bool indent;
  final Widget? trailing;
  final VoidCallback onTap;

  const _NavTile({
    required this.icon,
    required this.label,
    required this.active,
    required this.collapsed,
    required this.onTap,
    this.indent = false,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    const textStyle = TextStyle(fontSize: 13);
    final color = active
        ? Theme.of(context).colorScheme.primary
        : Theme.of(context).colorScheme.onSurface;

    return ListTile(
      contentPadding: EdgeInsets.only(
        left: collapsed ? 20 : (indent ? 48 : 16),
        right: 8,
      ),
      dense: true,
      visualDensity: VisualDensity.compact,
      leading: Icon(icon, size: 20, color: color),
      title: collapsed
          ? null
          : Text(label,
              style: TextStyle(
                fontSize: 13,
                color: color,
                fontWeight: active ? FontWeight.w600 : FontWeight.normal,
              )),
      trailing: trailing,
      selected: active,
      selectedTileColor:
          Theme.of(context).colorScheme.primaryContainer.withOpacity(0.3),
      onTap: onTap,
    );
  }
}
```

- [x] **Paso 2: Verificar la compilación**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/side_nav.dart
```

Esperado: No se encuentran problemas.

- [x] **Paso 3: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/side_nav.dart && git commit -m "feat: rewrite SideNav with two-level menu and collapse"
```

---

### Tarea 6: Reescribir AppShell como DesktopShell

**Files:**
- Reescribir: `apps/flutter/lib/features/shell/app_shell.dart`

- [x] **Paso 1: Escribir el nuevo app_shell.dart (DesktopShell)**

```dart
import 'package:flutter/material.dart';
import 'side_nav.dart';
import 'title_bar.dart';
import 'breadcrumb.dart';

class AppShell extends StatelessWidget {
  final Widget child;
  const AppShell({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          const TitleBar(),
          const Divider(height: 1),
          Expanded(
            child: Row(
              children: [
                const SideNav(),
                const VerticalDivider(width: 1),
                Expanded(
                  child: Column(
                    children: [
                      const BreadcrumbBar(),
                      const Divider(height: 1),
                      Expanded(child: child),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
```

Nota: El nombre de la clase sigue siendo `AppShell` para evitar cambiar el import en `router.dart`. La implementación ahora es un diseño de escritorio.

- [x] **Paso 2: Verificar la compilación**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/app_shell.dart
```

Esperado: No se encuentran problemas.

- [x] **Paso 3: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/app_shell.dart && git commit -m "feat: rewrite AppShell as DesktopShell with TitleBar + SideNav + BreadcrumbBar"
```

---

### Tarea 7: Actualizar el router con rutas de dos niveles

**Files:**
- Modificar: `apps/flutter/lib/router.dart`

- [x] **Paso 1: Reescribir router.dart**

```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'features/auth/login_page.dart';
import 'features/dashboard/dashboard_page.dart';
import 'features/campaign/campaign_list_page.dart';
import 'features/campaign/campaign_detail_page.dart';
import 'features/report/report_page.dart';
import 'features/account/account_page.dart';
import 'features/alert/alert_page.dart';
import 'features/shell/app_shell.dart';

final routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/dashboard',
    routes: [
      GoRoute(
        path: '/login',
        builder: (_, __) => const LoginPage(),
      ),
      ShellRoute(
        builder: (_, __, child) => AppShell(child: child),
        routes: [
          GoRoute(
            path: '/dashboard',
            builder: (_, __) => const DashboardPage(),
          ),
          GoRoute(
            path: '/campaigns/list',
            builder: (_, __) => const CampaignListPage(),
          ),
          GoRoute(
            path: '/campaigns/:id',
            builder: (_, state) =>
                CampaignDetailPage(id: state.pathParameters['id']!),
          ),
          GoRoute(
            path: '/accounts',
            builder: (_, __) => const AccountPage(),
          ),
          GoRoute(
            path: '/reports',
            builder: (_, __) => const ReportPage(),
          ),
          GoRoute(
            path: '/alerts',
            builder: (_, __) => const AlertPage(),
          ),
        ],
      ),
    ],
  );
});
```

Cambio clave: `/campaigns` → `/campaigns/list` para que coincida con la configuración de menú de dos niveles.

- [x] **Paso 2: Verificar que todo el proyecto compila**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

Esperado: No se encuentran problemas.

- [x] **Paso 3: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/router.dart && git commit -m "feat: update router for two-level menu structure"
```

---

### Tarea 8: Inicializar window_manager en main.dart

**Files:**
- Modificar: `apps/flutter/lib/main.dart`

- [x] **Paso 1: Reescribir main.dart**

```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:window_manager/window_manager.dart';
import 'app.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await windowManager.ensureInitialized();
  await windowManager.setMinimumSize(const Size(680, 480));
  await windowManager.setSize(const Size(1280, 800));
  await windowManager.center();

  runApp(const ProviderScope(child: AdsApp()));
}
```

- [x] **Paso 2: Verificar la compilación**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/main.dart
```

Esperado: No se encuentran problemas.

- [x] **Paso 3: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/main.dart && git commit -m "feat: init window_manager with min size and centered startup"
```

---

### Tarea 9: Ajustar el tema para la apariencia de escritorio

**Files:**
- Modificar: `apps/flutter/lib/theme.dart`

- [x] **Paso 1: Actualizar theme.dart**

```dart
import 'package:flutter/material.dart';

class AppTheme {
  static final lightTheme = ThemeData(
    useMaterial3: true,
    colorSchemeSeed: Colors.blue,
    brightness: Brightness.light,
    fontFamily: 'Roboto',
    scaffoldBackgroundColor: const Color(0xFFF5F5F5),
    dividerTheme: const DividerThemeData(
      space: 0,
      thickness: 1,
      color: Color(0xFFE0E0E0),
    ),
    navigationBarTheme: NavigationBarThemeData(
      height: 40,
      labelBehavior: NavigationDestinationLabelBehavior.alwaysHide,
    ),
  );
}
```

- [x] **Paso 2: Verificar la compilación**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/theme.dart
```

Esperado: No se encuentran problemas.

- [x] **Paso 3: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/theme.dart && git commit -m "feat: tune theme for desktop (scaffold bg, divider config)"
```

---

### Tarea 10: Eliminar el antiguo TopBar

**Files:**
- Eliminar: `apps/flutter/lib/features/shell/top_bar.dart`

- [x] **Paso 1: Eliminar top_bar.dart**

```bash
rm /home/wwwroot/ads-php/apps/flutter/lib/features/shell/top_bar.dart
```

- [x] **Paso 2: Verificar que nada referencia al archivo eliminado**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

Esperado: No se encuentran problemas (sin imports rotos).

- [x] **Paso 3: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/top_bar.dart && git commit -m "chore: remove old TopBar (replaced by TitleBar)"
```

---

### Tarea 11: Configurar las ventanas nativas de las plataformas (ocultar las barras de título nativas)

**Files:**
- Modificar: `apps/flutter/macos/Runner/MainFlutterWindow.swift`
- Modificar: `apps/flutter/windows/runner/main.cpp`
- Modificar: `apps/flutter/linux/my_application.cc`

- [x] **Paso 1: Leer el archivo Runner de macOS para encontrar el punto de edición correcto**

```bash
cat /home/wwwroot/ads-php/apps/flutter/macos/Runner/MainFlutterWindow.swift
```

Esperado: Contiene `import Cocoa` y una clase `MainFlutterWindow` con `awakeFromNib`.

- [x] **Paso 2: Configurar macOS para ocultar la barra de título**

Añadir después de `super.awakeFromNib()` en `MainFlutterWindow.swift`:

```swift
self.titleVisibility = .hidden
self.titlebarAppearsTransparent = true
self.styleMask.insert(.fullSizeContentView)
```

Para ello, editar el método `awakeFromNib` en `apps/flutter/macos/Runner/MainFlutterWindow.swift` para que quede así:

```swift
override func awakeFromNib() {
  super.awakeFromNib()
  self.titleVisibility = .hidden
  self.titlebarAppearsTransparent = true
  self.styleMask.insert(.fullSizeContentView)
  // existing code below (if any)
}
```

- [x] **Paso 3: Configurar Windows para ocultar la barra de título**

Leer primero el archivo:
```bash
cat /home/wwwroot/ads-php/apps/flutter/windows/runner/main.cpp
```

Luego localizar la llamada `CreateWindow` y modificar el parámetro `dwStyle`. Cambiar `WS_OVERLAPPEDWINDOW` para incluir el estilo sin bordes, o añadir la lógica `SW_HIDE`. Dado que `window_manager` gestiona esto en tiempo de ejecución, para la ventana inicial hay que modificar `Win32Window::Create` en `windows/runner/win32_window.cpp`:

```bash
cat /home/wwwroot/ads-php/apps/flutter/windows/runner/win32_window.cpp
```

Encontrar la llamada `CreateWindow` y cambiar:
```cpp
HWND hwnd = CreateWindow(
    window_class, title.c_str(),
    WS_OVERLAPPEDWINDOW | WS_VISIBLE,
    ...
```

A:
```cpp
HWND hwnd = CreateWindow(
    window_class, title.c_str(),
    WS_POPUP | WS_VISIBLE,
    ...
```

Esto elimina la barra de título y los bordes nativos. `window_manager` se encargará entonces de gestionar los marcos de ventana.

- [x] **Paso 4: Configurar Linux para ocultar la barra de título**

Leer primero el archivo:
```bash
cat /home/wwwroot/ads-php/apps/flutter/linux/my_application.cc
```

En la función `my_application_activate`, antes de `gtk_window_present`, añadir:

```cpp
gtk_window_set_decorated(GTK_WINDOW(window), FALSE);
```

- [x] **Paso 5: Verificar que los tres archivos están sintácticamente intactos (relectura)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && head -30 macos/Runner/MainFlutterWindow.swift && echo "---" && grep -A5 'WS_POPUP' windows/runner/win32_window.cpp && echo "---" && grep 'gtk_window_set_decorated' linux/my_application.cc
```

Esperado: Cada sección muestra las modificaciones esperadas.

- [x] **Paso 6: Confirmar (commit)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add macos/ windows/ linux/ && git commit -m "feat: hide native title bars on macOS/Windows/Linux for custom TitleBar"
```

---

### Tarea 12: Verificación de extremo a extremo

- [x] **Paso 1: Análisis estático completo**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

Esperado: No se encuentran problemas.

- [x] **Paso 2: Verificar que la compilación web compila**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter build web --no-pub
```

Esperado: Compilación exitosa.

- [x] **Paso 3: Comprobar la compilación de macOS (si se está en macOS — omitir si no)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter build macos --no-pub 2>&1 || echo "Skipped: not on macOS or build env not configured"
```

- [x] **Paso 4: Comprobar que la lista de archivos coincide con la especificación**

```bash
cd /home/wwwroot/ads-php/apps/flutter && echo "=== Files created/modified ===" && ls -la lib/config/menu_config.dart lib/features/shell/title_bar.dart lib/features/shell/breadcrumb.dart lib/features/shell/side_nav.dart lib/features/shell/app_shell.dart lib/router.dart lib/main.dart lib/theme.dart && echo "=== Old file removed ===" && ls lib/features/shell/top_bar.dart 2>&1
```

Esperado: Todos los archivos nuevos existen, top_bar.dart "No such file".

- [x] **Paso 5: Confirmar el estado final si quedan cambios**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git status
```

Si está limpio, no se necesita commit. Si está sucio, preparar y confirmar:

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add -A && git commit -m "chore: final verification tweaks for desktop support"
```

---

## Lista de verificación de verificación

Después de completar todas las tareas, verificar:

1. `dart analyze lib/` pasa con cero problemas
2. `flutter build web` compila sin errores
3. `flutter build macos` compila (en macOS)
4. Todos los archivos de la tabla de cambios de la especificación existen en las ubicaciones correctas
5. `top_bar.dart` está eliminado
6. No se modificó ningún archivo de funciones de negocio (verificar mediante `git diff main -- lib/features/dashboard/ lib/features/campaign/ lib/features/report/ lib/features/account/ lib/features/alert/ lib/features/auth/`)
