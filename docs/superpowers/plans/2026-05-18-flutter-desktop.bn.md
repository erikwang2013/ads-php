# Flutter Desktop Cross-Platform Support — Implementation Plan

[中文](docs/superpowers/plans/2026-05-18-flutter-desktop.md) | [English](docs/superpowers/plans/2026-05-18-flutter-desktop.en.md) | [한국어](docs/superpowers/plans/2026-05-18-flutter-desktop.ko.md) | [Русский](docs/superpowers/plans/2026-05-18-flutter-desktop.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-18-flutter-desktop.de.md) | [Français](docs/superpowers/plans/2026-05-18-flutter-desktop.fr.md) | [Español](docs/superpowers/plans/2026-05-18-flutter-desktop.es.md) | [Português](docs/superpowers/plans/2026-05-18-flutter-desktop.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-18-flutter-desktop.hi.md) | [العربية](docs/superpowers/plans/2026-05-18-flutter-desktop.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-18-flutter-desktop.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-18-flutter-desktop.id.md) | [日本語](docs/superpowers/plans/2026-05-18-flutter-desktop.ja.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Flutter অ্যাপকে iPadOS、macOS、Windows、Linux-এ ক্লাসিক ডেস্কটপ অ্যাডমিন-প্যানেল UI (টু-লেভেল সাইডবার、কাস্টম টাইটেল বার、ব্রেডক্রাম্ব) সহ সম্প্রসারিত করা।

**Architecture:** শেয়ার্ড মেনু কনফিগের চারপাশে শেল লেয়ার (AppShell → DesktopShell, SideNav, TitleBar, BreadcrumbBar) রিরাইট করা। বিজনেস ফিচার পেজ (dashboard, campaigns, reports, accounts, alerts) অপরিবর্তিত। GoRouter ShellRoute সব রুট DesktopShell দিয়ে মোড়ানো করে। একটি একক `menu_config.dart` ডেটা ফাইল নেভিগেশন রেন্ডারিং এবং ব্রেডক্রাম্ব জেনারেশন দুটোই চালায়।

**Tech Stack:** Flutter 3.2+, Dart, Riverpod, GoRouter, Dio, fl_chart, window_manager ^0.3.0

---

### Task 1: window_manager ডিপেন্ডেন্সি যোগ করুন এবং ডেস্কটপ প্ল্যাটফর্ম ডিরেক্টরি তৈরি করুন

**Files:**
- Modify: `apps/flutter/pubspec.yaml`
- Generate: `apps/flutter/macos/`, `apps/flutter/windows/`, `apps/flutter/linux/`

- [x] **Step 1: pubspec.yaml-এ window_manager যোগ করুন**

`apps/flutter/pubspec.yaml`-এ dependencies-এর অধীনে `window_manager: ^0.3.0` যোগ করুন:

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

- [x] **Step 2: flutter pub get চালান**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter pub get
```

প্রত্যাশিত: কোনো এরর ছাড়াই রিসলভ হয়।

- [x] **Step 3: ডেস্কটপ প্ল্যাটফর্ম ডিরেক্টরি তৈরি করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter create --platforms=macos,windows,linux .
```

প্রত্যাশিত: `macos/`、`windows/`、`linux/` ডিরেক্টরি তৈরি হয়েছে। কমান্ডটি বিদ্যমান প্রজেক্টকে টার্গেট করতে `.` ব্যবহার করে (lib/ ওভাররাইট হয় না)।

- [x] **Step 4: প্ল্যাটফর্ম ডিরেক্টরি আছে কিনা ভেরিফাই করুন**

```bash
ls -d /home/wwwroot/ads-php/apps/flutter/macos /home/wwwroot/ads-php/apps/flutter/windows /home/wwwroot/ads-php/apps/flutter/linux
```

প্রত্যাশিত: তিনটি ডিরেক্টরি পাথ প্রিন্ট হয়েছে।

- [x] **Step 5: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add pubspec.yaml pubspec.lock macos/ windows/ linux/ && git commit -m "chore: add window_manager dep and generate desktop platform dirs"
```

---

### Task 2: শেয়ার্ড মেনু কনফিগ তৈরি করুন

**Files:**
- Create: `apps/flutter/lib/config/menu_config.dart`

- [x] **Step 1: config ডিরেক্টরি তৈরি করুন**

```bash
mkdir -p /home/wwwroot/ads-php/apps/flutter/lib/config
```

- [x] **Step 2: menu_config.dart লিখুন**

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

- [x] **Step 3: সিনট্যাক্স চেক করে ফাইলটি কম্পাইল হয় কিনা ভেরিফাই করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/config/menu_config.dart
```

প্রত্যাশিত: কোনো সমস্যা নেই।

- [x] **Step 4: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/config/ && git commit -m "feat: add shared menu config with two-level structure"
```

---

### Task 3: TitleBar উইজেট লিখুন

**Files:**
- Create: `apps/flutter/lib/features/shell/title_bar.dart`

- [x] **Step 1: title_bar.dart লিখুন**

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

- [x] **Step 2: কম্পাইলেশন ভেরিফাই করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/title_bar.dart
```

প্রত্যাশিত: কোনো সমস্যা নেই।

- [x] **Step 3: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/title_bar.dart && git commit -m "feat: add custom TitleBar with window controls"
```

---

### Task 4: BreadcrumbBar উইজেট লিখুন

**Files:**
- Create: `apps/flutter/lib/features/shell/breadcrumb.dart`

- [x] **Step 1: breadcrumb.dart লিখুন**

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

- [x] **Step 2: কম্পাইলেশন ভেরিফাই করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/breadcrumb.dart
```

প্রত্যাশিত: কোনো সমস্যা নেই।

- [x] **Step 3: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/breadcrumb.dart && git commit -m "feat: add BreadcrumbBar driven by menu config"
```

---

### Task 5: SideNav টু-লেভেল মেনু এবং কলাপ্সসহ রিরাইট করুন

**Files:**
- Rewrite: `apps/flutter/lib/features/shell/side_nav.dart`

- [x] **Step 1: নতুন side_nav.dart লিখুন**

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

- [x] **Step 2: কম্পাইলেশন ভেরিফাই করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/side_nav.dart
```

প্রত্যাশিত: কোনো সমস্যা নেই।

- [x] **Step 3: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/side_nav.dart && git commit -m "feat: rewrite SideNav with two-level menu and collapse"
```

---

### Task 6: AppShell-কে DesktopShell হিসেবে রিরাইট করুন

**Files:**
- Rewrite: `apps/flutter/lib/features/shell/app_shell.dart`

- [x] **Step 1: নতুন app_shell.dart লিখুন (DesktopShell)**

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

Note: `router.dart`-এ import পরিবর্তন এড়াতে ক্লাসের নাম `AppShell`-ই থাকে। ইমপ্লিমেন্টেশন এখন ডেস্কটপ লেআউট।

- [x] **Step 2: কম্পাইলেশন ভেরিফাই করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/features/shell/app_shell.dart
```

প্রত্যাশিত: কোনো সমস্যা নেই।

- [x] **Step 3: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/app_shell.dart && git commit -m "feat: rewrite AppShell as DesktopShell with TitleBar + SideNav + BreadcrumbBar"
```

---

### Task 7: router-কে টু-লেভেল রুটে আপডেট করুন

**Files:**
- Modify: `apps/flutter/lib/router.dart`

- [x] **Step 1: router.dart রিরাইট করুন**

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

মূল পরিবর্তন: টু-লেভেল মেনু কনফিগের সাথে ম্যাচ করতে `/campaigns` → `/campaigns/list`।

- [x] **Step 2: পুরো প্রজেক্ট কম্পাইল হয় কিনা ভেরিফাই করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

প্রত্যাশিত: কোনো সমস্যা নেই।

- [x] **Step 3: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/router.dart && git commit -m "feat: update router for two-level menu structure"
```

---

### Task 8: main.dart-এ window_manager ইনিশিয়ালাইজ করুন

**Files:**
- Modify: `apps/flutter/lib/main.dart`

- [x] **Step 1: main.dart রিরাইট করুন**

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

- [x] **Step 2: কম্পাইলেশন ভেরিফাই করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/main.dart
```

প্রত্যাশিত: কোনো সমস্যা নেই।

- [x] **Step 3: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/main.dart && git commit -m "feat: init window_manager with min size and centered startup"
```

---

### Task 9: ডেস্কটপ চেহারার জন্য থিম টিউন করুন

**Files:**
- Modify: `apps/flutter/lib/theme.dart`

- [x] **Step 1: theme.dart আপডেট করুন**

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

- [x] **Step 2: কম্পাইলেশন ভেরিফাই করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/theme.dart
```

প্রত্যাশিত: কোনো সমস্যা নেই।

- [x] **Step 3: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/theme.dart && git commit -m "feat: tune theme for desktop (scaffold bg, divider config)"
```

---

### Task 10: পুরনো TopBar সরান

**Files:**
- Delete: `apps/flutter/lib/features/shell/top_bar.dart`

- [x] **Step 1: top_bar.dart ডিলিট করুন**

```bash
rm /home/wwwroot/ads-php/apps/flutter/lib/features/shell/top_bar.dart
```

- [x] **Step 2: ডিলিট করা ফাইলটি কেউ রেফারেন্স করে না কিনা ভেরিফাই করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

প্রত্যাশিত: কোনো সমস্যা নেই (ভাঙা import নেই)।

- [x] **Step 3: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add lib/features/shell/top_bar.dart && git commit -m "chore: remove old TopBar (replaced by TitleBar)"
```

---

### Task 11: নেটিভ প্ল্যাটফর্ম উইন্ডো কনফিগার করুন (নেটিভ টাইটেল বার লুকান)

**Files:**
- Modify: `apps/flutter/macos/Runner/MainFlutterWindow.swift`
- Modify: `apps/flutter/windows/runner/main.cpp`
- Modify: `apps/flutter/linux/my_application.cc`

- [x] **Step 1: সঠিক এডিট পয়েন্ট খুঁজতে macOS Runner ফাইল পড়ুন**

```bash
cat /home/wwwroot/ads-php/apps/flutter/macos/Runner/MainFlutterWindow.swift
```

প্রত্যাশিত: `import Cocoa` এবং `awakeFromNib` সহ একটি `MainFlutterWindow` ক্লাস রয়েছে।

- [x] **Step 2: লুকানো টাইটেল বারের জন্য macOS কনফিগার করুন**

`MainFlutterWindow.swift`-এ `super.awakeFromNib()`-এর পরে যোগ করুন:

```swift
self.titleVisibility = .hidden
self.titlebarAppearsTransparent = true
self.styleMask.insert(.fullSizeContentView)
```

এটি করতে, `apps/flutter/macos/Runner/MainFlutterWindow.swift`-এর `awakeFromNib` মেথড এডিট করুন:

```swift
override func awakeFromNib() {
  super.awakeFromNib()
  self.titleVisibility = .hidden
  self.titlebarAppearsTransparent = true
  self.styleMask.insert(.fullSizeContentView)
  // existing code below (if any)
}
```

- [x] **Step 3: লুকানো টাইটেল বারের জন্য Windows কনফিগার করুন**

প্রথমে ফাইলটি পড়ুন:
```bash
cat /home/wwwroot/ads-php/apps/flutter/windows/runner/main.cpp
```

তারপর `CreateWindow` কলটি খুঁজুন এবং `dwStyle` প্যারামিটার পরিবর্তন করুন। `WS_OVERLAPPEDWINDOW`-কে বর্ডারলেস স্টাইলে পরিবর্তন করুন, অথবা `SW_HIDE` লজিক যোগ করুন। যেহেতু `window_manager` রানটাইমে এটি হ্যান্ডল করে, প্রাথমিক উইন্ডোর জন্য আমাদের `windows/runner/win32_window.cpp`-এ `Win32Window::Create` পরিবর্তন করতে হবে:

```bash
cat /home/wwwroot/ads-php/apps/flutter/windows/runner/win32_window.cpp
```

`CreateWindow` কলটি খুঁজুন এবং পরিবর্তন করুন:
```cpp
HWND hwnd = CreateWindow(
    window_class, title.c_str(),
    WS_OVERLAPPEDWINDOW | WS_VISIBLE,
    ...
```

এইভাবে:
```cpp
HWND hwnd = CreateWindow(
    window_class, title.c_str(),
    WS_POPUP | WS_VISIBLE,
    ...
```

এটি নেটিভ টাইটেল বার এবং বর্ডার সরিয়ে দেয়। `window_manager` তখন উইন্ডো ফ্রেম ম্যানেজ করবে।

- [x] **Step 4: লুকানো টাইটেল বারের জন্য Linux কনফিগার করুন**

প্রথমে ফাইলটি পড়ুন:
```bash
cat /home/wwwroot/ads-php/apps/flutter/linux/my_application.cc
```

`my_application_activate` ফাংশনে, `gtk_window_present`-এর আগে যোগ করুন:

```cpp
gtk_window_set_decorated(GTK_WINDOW(window), FALSE);
```

- [x] **Step 5: তিনটি ফাইলই সিনট্যাক্টিক্যালি অক্ষত আছে কিনা ভেরিফাই করুন (পুনরায় পড়ে দেখুন)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && head -30 macos/Runner/MainFlutterWindow.swift && echo "---" && grep -A5 'WS_POPUP' windows/runner/win32_window.cpp && echo "---" && grep 'gtk_window_set_decorated' linux/my_application.cc
```

প্রত্যাশিত: প্রতিটি সেকশনে প্রত্যাশিত পরিবর্তন দেখা যায়।

- [x] **Step 6: কমিট**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add macos/ windows/ linux/ && git commit -m "feat: hide native title bars on macOS/Windows/Linux for custom TitleBar"
```

---

### Task 12: এন্ড-টু-এন্ড ভেরিফিকেশন

- [x] **Step 1: সম্পূর্ণ স্ট্যাটিক অ্যানালাইসিস**

```bash
cd /home/wwwroot/ads-php/apps/flutter && dart analyze lib/
```

প্রত্যাশিত: কোনো সমস্যা নেই।

- [x] **Step 2: web বিল্ড কম্পাইল হয় কিনা ভেরিফাই করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter build web --no-pub
```

প্রত্যাশিত: বিল্ড সফল।

- [x] **Step 3: macOS বিল্ড চেক করুন (macOS-এ থাকলে — না থাকলে স্কিপ করুন)**

```bash
cd /home/wwwroot/ads-php/apps/flutter && flutter build macos --no-pub 2>&1 || echo "Skipped: not on macOS or build env not configured"
```

- [x] **Step 4: ফাইল লিস্ট স্পেকের সাথে ম্যাচ করে কিনা চেক করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && echo "=== Files created/modified ===" && ls -la lib/config/menu_config.dart lib/features/shell/title_bar.dart lib/features/shell/breadcrumb.dart lib/features/shell/side_nav.dart lib/features/shell/app_shell.dart lib/router.dart lib/main.dart lib/theme.dart && echo "=== Old file removed ===" && ls lib/features/shell/top_bar.dart 2>&1
```

প্রত্যাশিত: সব নতুন ফাইল আছে, top_bar.dart-এর জন্য "No such file"।

- [x] **Step 5: কোনো পরিবর্তন অবশিষ্ট থাকলে চূড়ান্ত অবস্থা কমিট করুন**

```bash
cd /home/wwwroot/ads-php/apps/flutter && git status
```

ক্লিন হলে কমিটের প্রয়োজন নেই। ডার্টি হলে স্টেজ এবং কমিট করুন:

```bash
cd /home/wwwroot/ads-php/apps/flutter && git add -A && git commit -m "chore: final verification tweaks for desktop support"
```

---

## ভেরিফিকেশন চেকলিস্ট

সব টাস্ক শেষ হওয়ার পর ভেরিফাই করুন:

1. `dart analyze lib/` জিরো সমস্যাসহ পাস করে
2. `flutter build web` কোনো এরর ছাড়াই কম্পাইল হয়
3. `flutter build macos` কম্পাইল হয় (macOS-এ)
4. স্পেকের চেঞ্জ টেবিলের সব ফাইল সঠিক লোকেশনে আছে
5. `top_bar.dart` ডিলিট হয়েছে
6. কোনো বিজনেস ফিচার ফাইল পরিবর্তন হয়নি (`git diff main -- lib/features/dashboard/ lib/features/campaign/ lib/features/report/ lib/features/account/ lib/features/alert/ lib/features/auth/` দিয়ে ভেরিফাই করুন)
