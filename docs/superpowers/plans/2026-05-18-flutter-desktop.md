# Flutter Desktop Cross-Platform Support — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the Flutter app to iPadOS, macOS, Windows, Linux with a classic desktop admin-panel UI (two-level sidebar, custom title bar, breadcrumbs).

**Architecture:** Rewrite the shell layer (AppShell → DesktopShell, SideNav, TitleBar, BreadcrumbBar) around a shared menu config. Business feature pages (dashboard, campaigns, reports, accounts, alerts) are untouched. GoRouter ShellRoute wraps all routes in DesktopShell. A single `menu_config.dart` data file drives both navigation rendering and breadcrumb generation.

**Tech Stack:** Flutter 3.2+, Dart, Riverpod, GoRouter, Dio, fl_chart, window_manager ^0.3.0

---

### Task 1: Add window_manager dependency and generate desktop platform directories

**Files:**
- Modify: `apps/flutter/pubspec.yaml`
- Generate: `apps/flutter/macos/`, `apps/flutter/windows/`, `apps/flutter/linux/`

- [ ] **Step 1: Add window_manager to pubspec.yaml**

In `apps/flutter/pubspec.yaml`, add `window_manager: ^0.3.0` under dependencies:

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

- [ ] **Step 2: Run flutter pub get**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter pub get
```

Expected: Resolves without errors.

- [ ] **Step 3: Generate desktop platform directories**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter create --platforms=macos,windows,linux .
```

Expected: `macos/`, `windows/`, `linux/` directories created. The command uses `.` to target the existing project (no overwrite of lib/).

- [ ] **Step 4: Verify platform directories exist**

```bash
ls -d /home/wwwroot/ads-php/apps/flutter/macos /home/wwwroot/ads-php/apps/flutter/windows /home/wwwroot/ads-php/apps/flutter/linux
```

Expected: Three directory paths printed.

- [ ] **Step 5: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add pubspec.yaml pubspec.lock macos/ windows/ linux/ && git commit -m "chore: add window_manager dep and generate desktop platform dirs"
```

---

### Task 2: Create shared menu configuration

**Files:**
- Create: `apps/flutter/lib/config/menu_config.dart`

- [ ] **Step 1: Create the config directory**

```bash
mkdir -p /home/wwwroot/ads-php/apps/flutter/lib/config
```

- [ ] **Step 2: Write menu_config.dart**

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

- [ ] **Step 3: Verify the file compiles by checking syntax**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/config/menu_config.dart
```

Expected: No issues found.

- [ ] **Step 4: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/config/ && git commit -m "feat: add shared menu config with two-level structure"
```

---

### Task 3: Write TitleBar widget

**Files:**
- Create: `apps/flutter/lib/features/shell/title_bar.dart`

- [ ] **Step 1: Write title_bar.dart**

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

- [ ] **Step 2: Verify compilation**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/title_bar.dart
```

Expected: No issues found.

- [ ] **Step 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/title_bar.dart && git commit -m "feat: add custom TitleBar with window controls"
```

---

### Task 4: Write BreadcrumbBar widget

**Files:**
- Create: `apps/flutter/lib/features/shell/breadcrumb.dart`

- [ ] **Step 1: Write breadcrumb.dart**

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

- [ ] **Step 2: Verify compilation**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/breadcrumb.dart
```

Expected: No issues found.

- [ ] **Step 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/breadcrumb.dart && git commit -m "feat: add BreadcrumbBar driven by menu config"
```

---

### Task 5: Rewrite SideNav with two-level menu and collapse

**Files:**
- Rewrite: `apps/flutter/lib/features/shell/side_nav.dart`

- [ ] **Step 1: Write the new side_nav.dart**

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

- [ ] **Step 2: Verify compilation**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/side_nav.dart
```

Expected: No issues found.

- [ ] **Step 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/side_nav.dart && git commit -m "feat: rewrite SideNav with two-level menu and collapse"
```

---

### Task 6: Rewrite AppShell as DesktopShell

**Files:**
- Rewrite: `apps/flutter/lib/features/shell/app_shell.dart`

- [ ] **Step 1: Write the new app_shell.dart (DesktopShell)**

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

Note: Class name stays `AppShell` to avoid changing the import in `router.dart`. The implementation is now a desktop layout.

- [ ] **Step 2: Verify compilation**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/app_shell.dart
```

Expected: No issues found.

- [ ] **Step 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/app_shell.dart && git commit -m "feat: rewrite AppShell as DesktopShell with TitleBar + SideNav + BreadcrumbBar"
```

---

### Task 7: Update router with two-level routes

**Files:**
- Modify: `apps/flutter/lib/router.dart`

- [ ] **Step 1: Rewrite router.dart**

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

Key change: `/campaigns` → `/campaigns/list` to match the two-level menu config.

- [ ] **Step 2: Verify the whole project compiles**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

Expected: No issues found.

- [ ] **Step 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/router.dart && git commit -m "feat: update router for two-level menu structure"
```

---

### Task 8: Initialize window_manager in main.dart

**Files:**
- Modify: `apps/flutter/lib/main.dart`

- [ ] **Step 1: Rewrite main.dart**

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

- [ ] **Step 2: Verify compilation**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/main.dart
```

Expected: No issues found.

- [ ] **Step 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/main.dart && git commit -m "feat: init window_manager with min size and centered startup"
```

---

### Task 9: Tune theme for desktop appearance

**Files:**
- Modify: `apps/flutter/lib/theme.dart`

- [ ] **Step 1: Update theme.dart**

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

- [ ] **Step 2: Verify compilation**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/theme.dart
```

Expected: No issues found.

- [ ] **Step 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/theme.dart && git commit -m "feat: tune theme for desktop (scaffold bg, divider config)"
```

---

### Task 10: Remove old TopBar

**Files:**
- Delete: `apps/flutter/lib/features/shell/top_bar.dart`

- [ ] **Step 1: Delete top_bar.dart**

```bash
rm /home/wwwroot/ads-php/apps/flutter/lib/features/shell/top_bar.dart
```

- [ ] **Step 2: Verify nothing references the deleted file**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

Expected: No issues found (no broken imports).

- [ ] **Step 3: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/top_bar.dart && git commit -m "chore: remove old TopBar (replaced by TitleBar)"
```

---

### Task 11: Configure native platform windows (hide native title bars)

**Files:**
- Modify: `apps/flutter/macos/Runner/MainFlutterWindow.swift`
- Modify: `apps/flutter/windows/runner/main.cpp`
- Modify: `apps/flutter/linux/my_application.cc`

- [ ] **Step 1: Read the macOS Runner file to find the right edit point**

```bash
cat /home/wwwroot/ads-php/apps/flutter/macos/Runner/MainFlutterWindow.swift
```

Expected: Contains `import Cocoa` and a `MainFlutterWindow` class with `awakeFromNib`.

- [ ] **Step 2: Configure macOS for hidden title bar**

Add after `super.awakeFromNib()` in `MainFlutterWindow.swift`:

```swift
self.titleVisibility = .hidden
self.titlebarAppearsTransparent = true
self.styleMask.insert(.fullSizeContentView)
```

To do this, edit the `awakeFromNib` method in `apps/flutter/macos/Runner/MainFlutterWindow.swift` to:

```swift
override func awakeFromNib() {
  super.awakeFromNib()
  self.titleVisibility = .hidden
  self.titlebarAppearsTransparent = true
  self.styleMask.insert(.fullSizeContentView)
  // existing code below (if any)
}
```

- [ ] **Step 3: Configure Windows for hidden title bar**

Read the file first:
```bash
cat /home/wwwroot/ads-php/apps/flutter/windows/runner/main.cpp
```

Then locate the `CreateWindow` call and modify the `dwStyle` parameter. Change the `WS_OVERLAPPEDWINDOW` to include borderless style, or add `SW_HIDE` logic. Since `window_manager` handles this at runtime, for the initial window we need to modify `Win32Window::Create` in `windows/runner/win32_window.cpp`:

```bash
cat /home/wwwroot/ads-php/apps/flutter/windows/runner/win32_window.cpp
```

Find the `CreateWindow` call and change:
```cpp
HWND hwnd = CreateWindow(
    window_class, title.c_str(),
    WS_OVERLAPPEDWINDOW | WS_VISIBLE,
    ...
```

To:
```cpp
HWND hwnd = CreateWindow(
    window_class, title.c_str(),
    WS_POPUP | WS_VISIBLE,
    ...
```

This removes the native title bar and borders. `window_manager` will then manage window frames.

- [ ] **Step 4: Configure Linux for hidden title bar**

Read the file first:
```bash
cat /home/wwwroot/ads-php/apps/flutter/linux/my_application.cc
```

In the `my_application_activate` function, before `gtk_window_present`, add:

```cpp
gtk_window_set_decorated(GTK_WINDOW(window), FALSE);
```

- [ ] **Step 5: Verify all three files are syntactically intact (read back)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && head -30 macos/Runner/MainFlutterWindow.swift && echo "---" && grep -A5 'WS_POPUP' windows/runner/win32_window.cpp && echo "---" && grep 'gtk_window_set_decorated' linux/my_application.cc
```

Expected: Each section shows the expected modifications.

- [ ] **Step 6: Commit**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add macos/ windows/ linux/ && git commit -m "feat: hide native title bars on macOS/Windows/Linux for custom TitleBar"
```

---

### Task 12: End-to-end verification

- [ ] **Step 1: Full static analysis**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

Expected: No issues found.

- [ ] **Step 2: Verify web build compiles**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter build web --no-pub
```

Expected: Build successful.

- [ ] **Step 3: Check macOS build (if on macOS — skip if not)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter build macos --no-pub 2>&1 || echo "Skipped: not on macOS or build env not configured"
```

- [ ] **Step 4: Check file list matches spec**

```bash
cd /home/wwwroot/ads-php/apps/flutter && echo "=== Files created/modified ===" && ls -la lib/config/menu_config.dart lib/features/shell/title_bar.dart lib/features/shell/breadcrumb.dart lib/features/shell/side_nav.dart lib/features/shell/app_shell.dart lib/router.dart lib/main.dart lib/theme.dart && echo "=== Old file removed ===" && ls lib/features/shell/top_bar.dart 2>&1
```

Expected: All new files exist, top_bar.dart "No such file".

- [ ] **Step 5: Commit final state if any changes remain**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git status
```

If clean, no commit needed. If dirty, stage and commit:

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add -A && git commit -m "chore: final verification tweaks for desktop support"
```

---

## Verification Checklist

After all tasks complete, verify:

1. `dart analyze lib/` passes with zero issues
2. `flutter build web` compiles without errors
3. `flutter build macos` compiles (on macOS)
4. All files from the spec's change table exist in correct locations
5. `top_bar.dart` is deleted
6. No business feature files were modified (verify via `git diff main -- lib/features/dashboard/ lib/features/campaign/ lib/features/report/ lib/features/account/ lib/features/alert/ lib/features/auth/`)
