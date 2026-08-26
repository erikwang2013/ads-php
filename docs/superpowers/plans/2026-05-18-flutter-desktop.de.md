# Flutter Desktop Cross-Platform Support — Implementierungsplan

[中文](docs/superpowers/plans/2026-05-18-flutter-desktop.md) | [English](docs/superpowers/plans/2026-05-18-flutter-desktop.en.md) | [한국어](docs/superpowers/plans/2026-05-18-flutter-desktop.ko.md) | [Русский](docs/superpowers/plans/2026-05-18-flutter-desktop.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-18-flutter-desktop.de.md) | [Français](docs/superpowers/plans/2026-05-18-flutter-desktop.fr.md) | [Español](docs/superpowers/plans/2026-05-18-flutter-desktop.es.md) | [Português](docs/superpowers/plans/2026-05-18-flutter-desktop.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-18-flutter-desktop.hi.md) | [العربية](docs/superpowers/plans/2026-05-18-flutter-desktop.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-18-flutter-desktop.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-18-flutter-desktop.id.md) | [日本語](docs/superpowers/plans/2026-05-18-flutter-desktop.ja.md)

> **Für agentische Worker:** ERFORDERLICHES UNTER-SKILL: Verwende superpowers:subagent-driven-development (empfohlen) oder superpowers:executing-plans, um diesen Plan Aufgabe für Aufgabe umzusetzen. Schritte verwenden die Checkbox-Syntax (`- [ ]`) zur Fortschrittsverfolgung.

**Ziel:** Die Flutter-App auf iPadOS, macOS, Windows und Linux erweitern, mit einer klassischen Desktop-Admin-Panel-Oberfläche (zwei Ebenen umfassende Seitenleiste, benutzerdefinierte Titelleiste, Breadcrumbs).

**Architektur:** Die Shell-Schicht neu schreiben (AppShell → DesktopShell, SideNav, TitleBar, BreadcrumbBar), basierend auf einer gemeinsamen Menükonfiguration. Die Geschäfts-Feature-Seiten (dashboard, campaigns, reports, accounts, alerts) bleiben unverändert. Die GoRouter-ShellRoute umschließt alle Routen in der DesktopShell. Eine einzelne `menu_config.dart`-Datendatei steuert sowohl das Navigations-Rendering als auch die Breadcrumb-Erzeugung.

**Tech Stack:** Flutter 3.2+, Dart, Riverpod, GoRouter, Dio, fl_chart, window_manager ^0.3.0

---

### Task 1: window_manager-Abhängigkeit hinzufügen und Desktop-Plattformverzeichnisse erzeugen

**Dateien:**
- Ändern: `apps/flutter/pubspec.yaml`
- Erzeugen: `apps/flutter/macos/`, `apps/flutter/windows/`, `apps/flutter/linux/`

- [x] **Schritt 1: window_manager zu pubspec.yaml hinzufügen**

In `apps/flutter/pubspec.yaml` unter dependencies `window_manager: ^0.3.0` ergänzen:

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

- [x] **Schritt 2: flutter pub get ausführen**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter pub get
```

Erwartung: Löst ohne Fehler auf.

- [x] **Schritt 3: Desktop-Plattformverzeichnisse erzeugen**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter create --platforms=macos,windows,linux .
```

Erwartung: Die Verzeichnisse `macos/`, `windows/`, `linux/` werden erstellt. Der Befehl verwendet `.`, um auf das bestehende Projekt zu zielen (kein Überschreiben von lib/).

- [x] **Schritt 4: Existenz der Plattformverzeichnisse prüfen**

```bash
ls -d /home/wwwroot/ads-php/apps/flutter/macos /home/wwwroot/ads-php/apps/flutter/windows /home/wwwroot/ads-php/apps/flutter/linux
```

Erwartung: Drei Verzeichnispfade werden ausgegeben.

- [x] **Schritt 5: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add pubspec.yaml pubspec.lock macos/ windows/ linux/ && git commit -m "chore: add window_manager dep and generate desktop platform dirs"
```

---

### Task 2: Gemeinsame Menükonfiguration erstellen

**Dateien:**
- Erstellen: `apps/flutter/lib/config/menu_config.dart`

- [x] **Schritt 1: Das config-Verzeichnis erstellen**

```bash
mkdir -p /home/wwwroot/ads-php/apps/flutter/lib/config
```

- [x] **Schritt 2: menu_config.dart schreiben**

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

- [x] **Schritt 3: Syntaxprüfung der Datei**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/config/menu_config.dart
```

Erwartung: Keine Probleme gefunden.

- [x] **Schritt 4: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/config/ && git commit -m "feat: add shared menu config with two-level structure"
```

---

### Task 3: TitleBar-Widget schreiben

**Dateien:**
- Erstellen: `apps/flutter/lib/features/shell/title_bar.dart`

- [x] **Schritt 1: title_bar.dart schreiben**

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

- [x] **Schritt 2: Kompilierung prüfen**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/title_bar.dart
```

Erwartung: Keine Probleme gefunden.

- [x] **Schritt 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/title_bar.dart && git commit -m "feat: add custom TitleBar with window controls"
```

---

### Task 4: BreadcrumbBar-Widget schreiben

**Dateien:**
- Erstellen: `apps/flutter/lib/features/shell/breadcrumb.dart`

- [x] **Schritt 1: breadcrumb.dart schreiben**

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

- [x] **Schritt 2: Kompilierung prüfen**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/breadcrumb.dart
```

Erwartung: Keine Probleme gefunden.

- [x] **Schritt 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/breadcrumb.dart && git commit -m "feat: add BreadcrumbBar driven by menu config"
```

---

### Task 5: SideNav mit Zwei-Ebenen-Menü und Einklappen neu schreiben

**Dateien:**
- Neu schreiben: `apps/flutter/lib/features/shell/side_nav.dart`

- [x] **Schritt 1: Die neue side_nav.dart schreiben**

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

- [x] **Schritt 2: Kompilierung prüfen**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/side_nav.dart
```

Erwartung: Keine Probleme gefunden.

- [x] **Schritt 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/side_nav.dart && git commit -m "feat: rewrite SideNav with two-level menu and collapse"
```

---

### Task 6: AppShell als DesktopShell neu schreiben

**Dateien:**
- Neu schreiben: `apps/flutter/lib/features/shell/app_shell.dart`

- [x] **Schritt 1: Die neue app_shell.dart (DesktopShell) schreiben**

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

Hinweis: Der Klassenname bleibt `AppShell`, um den Import in `router.dart` nicht zu ändern. Die Implementierung ist nun ein Desktop-Layout.

- [x] **Schritt 2: Kompilierung prüfen**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/app_shell.dart
```

Erwartung: Keine Probleme gefunden.

- [x] **Schritt 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/app_shell.dart && git commit -m "feat: rewrite AppShell as DesktopShell with TitleBar + SideNav + BreadcrumbBar"
```

---

### Task 7: Router mit Zwei-Ebenen-Routen aktualisieren

**Dateien:**
- Ändern: `apps/flutter/lib/router.dart`

- [x] **Schritt 1: router.dart neu schreiben**

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

Wichtigste Änderung: `/campaigns` → `/campaigns/list`, passend zur Zwei-Ebenen-Menükonfiguration.

- [x] **Schritt 2: Kompilierung des gesamten Projekts prüfen**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

Erwartung: Keine Probleme gefunden.

- [x] **Schritt 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/router.dart && git commit -m "feat: update router for two-level menu structure"
```

---

### Task 8: window_manager in main.dart initialisieren

**Dateien:**
- Ändern: `apps/flutter/lib/main.dart`

- [x] **Schritt 1: main.dart neu schreiben**

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

- [x] **Schritt 2: Kompilierung prüfen**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/main.dart
```

Erwartung: Keine Probleme gefunden.

- [x] **Schritt 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/main.dart && git commit -m "feat: init window_manager with min size and centered startup"
```

---

### Task 9: Theme für das Desktop-Erscheinungsbild anpassen

**Dateien:**
- Ändern: `apps/flutter/lib/theme.dart`

- [x] **Schritt 1: theme.dart aktualisieren**

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

- [x] **Schritt 2: Kompilierung prüfen**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/theme.dart
```

Erwartung: Keine Probleme gefunden.

- [x] **Schritt 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/theme.dart && git commit -m "feat: tune theme for desktop (scaffold bg, divider config)"
```

---

### Task 10: Alte TopBar entfernen

**Dateien:**
- Löschen: `apps/flutter/lib/features/shell/top_bar.dart`

- [x] **Schritt 1: top_bar.dart löschen**

```bash
rm /home/wwwroot/ads-php/apps/flutter/lib/features/shell/top_bar.dart
```

- [x] **Schritt 2: Prüfen, dass nichts auf die gelöschte Datei verweist**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

Erwartung: Keine Probleme gefunden (keine defekten Importe).

- [x] **Schritt 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/top_bar.dart && git commit -m "chore: remove old TopBar (replaced by TitleBar)"
```

---

### Task 11: Native Plattformfenster konfigurieren (native Titelleisten ausblenden)

**Dateien:**
- Ändern: `apps/flutter/macos/Runner/MainFlutterWindow.swift`
- Ändern: `apps/flutter/windows/runner/main.cpp`
- Ändern: `apps/flutter/linux/my_application.cc`

- [x] **Schritt 1: Die macOS-Runner-Datei lesen, um die richtige Bearbeitungsstelle zu finden**

```bash
cat /home/wwwroot/ads-php/apps/flutter/macos/Runner/MainFlutterWindow.swift
```

Erwartung: Enthält `import Cocoa` und eine `MainFlutterWindow`-Klasse mit `awakeFromNib`.

- [x] **Schritt 2: macOS für ausgeblendete Titelleiste konfigurieren**

Nach `super.awakeFromNib()` in `MainFlutterWindow.swift` einfügen:

```swift
self.titleVisibility = .hidden
self.titlebarAppearsTransparent = true
self.styleMask.insert(.fullSizeContentView)
```

Dazu die `awakeFromNib`-Methode in `apps/flutter/macos/Runner/MainFlutterWindow.swift` wie folgt bearbeiten:

```swift
override func awakeFromNib() {
  super.awakeFromNib()
  self.titleVisibility = .hidden
  self.titlebarAppearsTransparent = true
  self.styleMask.insert(.fullSizeContentView)
  // existing code below (if any)
}
```

- [x] **Schritt 3: Windows für ausgeblendete Titelleiste konfigurieren**

Die Datei zuerst lesen:
```bash
cat /home/wwwroot/ads-php/apps/flutter/windows/runner/main.cpp
```

Dann den `CreateWindow`-Aufruf lokalisieren und den Parameter `dwStyle` ändern. `WS_OVERLAPPEDWINDOW` auf den randlosen Stil umstellen oder `SW_HIDE`-Logik ergänzen. Da `window_manager` dies zur Laufzeit übernimmt, muss für das anfängliche Fenster `Win32Window::Create` in `windows/runner/win32_window.cpp` geändert werden:

```bash
cat /home/wwwroot/ads-php/apps/flutter/windows/runner/win32_window.cpp
```

Den `CreateWindow`-Aufruf finden und ändern:
```cpp
HWND hwnd = CreateWindow(
    window_class, title.c_str(),
    WS_OVERLAPPEDWINDOW | WS_VISIBLE,
    ...
```

Zu:
```cpp
HWND hwnd = CreateWindow(
    window_class, title.c_str(),
    WS_POPUP | WS_VISIBLE,
    ...
```

Dies entfernt die native Titelleiste und die Ränder. `window_manager` verwaltet danach die Fensterrahmen.

- [x] **Schritt 4: Linux für ausgeblendete Titelleiste konfigurieren**

Die Datei zuerst lesen:
```bash
cat /home/wwwroot/ads-php/apps/flutter/linux/my_application.cc
```

In der Funktion `my_application_activate` vor `gtk_window_present` einfügen:

```cpp
gtk_window_set_decorated(GTK_WINDOW(window), FALSE);
```

- [x] **Schritt 5: Prüfen, dass alle drei Dateien syntaktisch intakt sind (zurücklesen)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && head -30 macos/Runner/MainFlutterWindow.swift && echo "---" && grep -A5 'WS_POPUP' windows/runner/win32_window.cpp && echo "---" && grep 'gtk_window_set_decorated' linux/my_application.cc
```

Erwartung: Jeder Abschnitt zeigt die erwarteten Änderungen.

- [x] **Schritt 6: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add macos/ windows/ linux/ && git commit -m "feat: hide native title bars on macOS/Windows/Linux for custom TitleBar"
```

---

### Task 12: End-to-End-Verifizierung

- [x] **Schritt 1: Vollständige statische Analyse**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

Erwartung: Keine Probleme gefunden.

- [x] **Schritt 2: Prüfen, dass der Web-Build kompiliert**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter build web --no-pub
```

Erwartung: Build erfolgreich.

- [x] **Schritt 3: macOS-Build prüfen (nur auf macOS — sonst überspringen)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter build macos --no-pub 2>&1 || echo "Skipped: not on macOS or build env not configured"
```

- [x] **Schritt 4: Prüfen, dass die Dateiliste der Spezifikation entspricht**

```bash
cd /home/wwwroot/ads-php/apps/flutter && echo "=== Files created/modified ===" && ls -la lib/config/menu_config.dart lib/features/shell/title_bar.dart lib/features/shell/breadcrumb.dart lib/features/shell/side_nav.dart lib/features/shell/app_shell.dart lib/router.dart lib/main.dart lib/theme.dart && echo "=== Old file removed ===" && ls lib/features/shell/top_bar.dart 2>&1
```

Erwartung: Alle neuen Dateien existieren, top_bar.dart „No such file".

- [x] **Schritt 5: Endzustand committen, falls Änderungen übrig bleiben**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git status
```

Wenn sauber, ist kein Commit nötig. Wenn nicht sauber, stagen und committen:

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add -A && git commit -m "chore: final verification tweaks for desktop support"
```

---

## Verifizierungs-Checkliste

Nach Abschluss aller Tasks prüfen:

1. `dart analyze lib/` läuft fehlerfrei durch
2. `flutter build web` kompiliert ohne Fehler
3. `flutter build macos` kompiliert (auf macOS)
4. Alle Dateien aus der Änderungstabelle der Spezifikation existieren an den richtigen Stellen
5. `top_bar.dart` ist gelöscht
6. Es wurden keine Geschäfts-Feature-Dateien geändert (prüfen via `git diff main -- lib/features/dashboard/ lib/features/campaign/ lib/features/report/ lib/features/account/ lib/features/alert/ lib/features/auth/`)
