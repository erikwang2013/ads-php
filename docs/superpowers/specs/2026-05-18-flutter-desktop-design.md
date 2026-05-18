# Flutter Desktop Cross-Platform Support — Design Spec

Date: 2026-05-18
Status: approved

## Goal

Extend the existing `apps/flutter/` Flutter project to support iPadOS, macOS, Windows, and Linux as first-class desktop platforms, using a classic desktop admin-panel UI style (Ant Design Pro / Element UI inspired). Web support is retained and upgraded to the same desktop-style layout.

## Target Platforms

| Platform | Status |
|----------|--------|
| Web | Keep, upgrade to desktop layout |
| iPadOS | New, same layout as desktop (small-screen PC) |
| macOS | New, custom title bar |
| Windows | New, custom title bar |
| Linux | New, custom title bar |

## Design

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

### Component Tree

- `DesktopShell` — top-level layout container, replaces `AppShell`
- `TitleBar` — custom title bar: app name left, window controls (min/max/close) right, drag-to-move
- `SideNav` — collapsible two-level side navigation, 240px expanded → 64px collapsed with animation
- `BreadcrumbBar` — auto-generated from route path via shared menu config
- `AppShell`, `TopBar`, `BottomBar` — **removed**

### Two-Level Menu Config

A single `menu_config.dart` data file drives both `SideNav` rendering and `GoRouter` route generation:

```
/dashboard          → 仪表盘 (top-level)
/campaigns/list     → 广告管理 > 广告计划 (2nd level)
/campaigns/creative → 广告管理 > 创意管理 (2nd level)
/reports            → 数据报表 (top-level)
/accounts           → 平台账户 (top-level)
/alerts             → 告警管理 (top-level)
```

### Routing

`GoRouter` `ShellRoute` wraps routes with `DesktopShell`. Nested routes under `/campaigns` map to the two-level menu group.

### Responsive Behavior

No platform branching. Single layout adapts to window width:

| Width | Behavior |
|-------|----------|
| ≥ 1024px | Sidebar expanded, full desktop |
| 768–1023px | Sidebar collapsed by default |
| < 768px | Sidebar collapsed, reduced content padding |
| Minimum window | 680×480 |

### Tech Stack (no changes)

- State: Riverpod
- Routing: GoRouter
- HTTP: Dio
- Charts: fl_chart
- New dep: `window_manager` ^0.3.0 for window controls

## File Changes

| Action | File | Notes |
|--------|------|-------|
| Rewrite | `lib/features/shell/app_shell.dart` | New `DesktopShell` |
| Rewrite | `lib/features/shell/side_nav.dart` | Two-level + collapsible |
| New | `lib/features/shell/title_bar.dart` | Custom title bar |
| New | `lib/features/shell/breadcrumb.dart` | Breadcrumb widget |
| Delete | `lib/features/shell/top_bar.dart` | Old top bar |
| New | `lib/config/menu_config.dart` | Shared menu data |
| Modify | `lib/router.dart` | DesktopShell + nested routes |
| Modify | `lib/main.dart` | Init window_manager |
| Modify | `lib/theme.dart` | Desktop-oriented theme |
| Modify | `pubspec.yaml` | Add window_manager dep |
| Generate | `macos/`, `windows/`, `linux/` | Platform runners |
| Modify | `macos/Runner/MainFlutterWindow.swift` | Hide native title bar |
| Modify | `windows/runner/main.cpp` | Hide native title bar |
| Modify | `linux/my_application.cc` | Hide native title bar |

Business feature pages (6 files under `lib/features/`) — **no changes**.

## Scope Boundaries

- In scope: shell layout, navigation, title bar, platform configuration
- Out of scope: new business features, backend changes, CI/CD, splash screen, app icon
