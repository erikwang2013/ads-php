# Flutter Desktop Cross-Platform Support — Design Spec

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

Date: 2026-05-18
Status: approved

## Goal

বিদ্যমান `apps/flutter/` Flutter প্রজেক্টকে iPadOS, macOS, Windows এবং Linux-কে ফার্স্ট-ক্লাস ডেস্কটপ প্ল্যাটফর্ম হিসেবে সাপোর্ট করার জন্য সম্প্রসারণ করুন, ক্লাসিক ডেস্কটপ অ্যাডমিন-প্যানেল UI স্টাইলে (Ant Design Pro / Element UI অনুপ্রাণিত)। Web সাপোর্ট রেখে একই ডেস্কটপ-স্টাইল লেআউটে আপগ্রেড করা হয়েছে।

## টার্গেট প্ল্যাটফর্ম

| প্ল্যাটফর্ম | স্ট্যাটাস |
|----------|--------|
| Web | রাখা হয়েছে, ডেস্কটপ লেআউটে আপগ্রেড |
| iPadOS | নতুন, ডেস্কটপের মতো একই লেআউট (ছোট-স্ক্রিন PC) |
| macOS | নতুন, কাস্টম টাইটেল বার |
| Windows | নতুন, কাস্টম টাইটেল বার |
| Linux | নতুন, কাস্টম টাইটেল বার |

## ডিজাইন

### আর্কিটেকচার

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

### কম্পোনেন্ট ট্রি

- `DesktopShell` — টপ-লেভেল লেআউট কন্টেইনার, `AppShell` প্রতিস্থাপন করে
- `TitleBar` — কাস্টম টাইটেল বার: বামে অ্যাপ নাম, ডানে উইন্ডো কন্ট্রোল (min/max/close), ড্র্যাগ-টু-মুভ
- `SideNav` — কলাপসিবল দুই-লেভেল সাইড নেভিগেশন, 240px সম্প্রসারিত → 64px কলাপসড, অ্যানিমেশনসহ
- `BreadcrumbBar` — শেয়ার্ড মেনু কনফিগ থেকে রুট পাথের মাধ্যমে অটো-জেনারেটেড
- `AppShell`, `TopBar`, `BottomBar` — **অপসারণ করা হয়েছে**

### টু-লেভেল মেনু কনফিগ

একটি `menu_config.dart` ডেটা ফাইল `SideNav` রেন্ডারিং এবং `GoRouter` রুট জেনারেশন দুইটিই চালায়:

```
/dashboard          → 仪表盘 (টপ-লেভেল)
/campaigns/list     → 广告管理 > 广告计划 (২য় লেভেল)
/campaigns/creative → 广告管理 > 创意管理 (২য় লেভেল)
/reports            → 数据报表 (টপ-লেভেল)
/accounts           → 平台账户 (টপ-লেভেল)
/alerts             → 告警管理 (টপ-লেভেল)
```

### রাউটিং

`GoRouter` `ShellRoute` রুটগুলোকে `DesktopShell` দিয়ে মোড়ানো করে। `/campaigns`-এর অধীনে নেস্টেড রুট দুই-লেভেল মেনু গ্রুপে ম্যাপ হয়।

### রেসপনসিভ আচরণ

কোনো প্ল্যাটফর্ম ব্রাঞ্চিং নেই। একক লেআউট উইন্ডো প্রস্থের সাথে অ্যাডাপ্ট হয়:

| প্রস্থ | আচরণ |
|-------|----------|
| ≥ 1024px | সাইডবার সম্প্রসারিত, ফুল ডেস্কটপ |
| 768–1023px | সাইডবার ডিফল্টভাবে কলাপসড |
| < 768px | সাইডবার কলাপসড, কনটেন্ট প্যাডিং কমে |
| ন্যূনতম উইন্ডো | 680×480 |

### টেক স্ট্যাক (কোনো পরিবর্তন নেই)

- State: Riverpod
- Routing: GoRouter
- HTTP: Dio
- Charts: fl_chart
- নতুন ডিপেন্ডেন্সি: উইন্ডো কন্ট্রোলের জন্য `window_manager` ^0.3.0

## ফাইল পরিবর্তন

| অ্যাকশন | ফাইল | নোট |
|--------|------|-------|
| Rewrite | `lib/features/shell/app_shell.dart` | নতুন `DesktopShell` |
| Rewrite | `lib/features/shell/side_nav.dart` | দুই-লেভেল + কলাপসিবল |
| New | `lib/features/shell/title_bar.dart` | কাস্টম টাইটেল বার |
| New | `lib/features/shell/breadcrumb.dart` | Breadcrumb উইজেট |
| Delete | `lib/features/shell/top_bar.dart` | পুরনো টপ বার |
| New | `lib/config/menu_config.dart` | শেয়ার্ড মেনু ডেটা |
| Modify | `lib/router.dart` | DesktopShell + নেস্টেড রুট |
| Modify | `lib/main.dart` | window_manager ইনিশিয়ালাইজ |
| Modify | `lib/theme.dart` | ডেস্কটপ-ওরিয়েন্টেড থিম |
| Modify | `pubspec.yaml` | window_manager ডিপ যোগ |
| Generate | `macos/`, `windows/`, `linux/` | প্ল্যাটফর্ম রানার |
| Modify | `macos/Runner/MainFlutterWindow.swift` | নেটিভ টাইটেল বার লুকান |
| Modify | `windows/runner/main.cpp` | নেটিভ টাইটেল বার লুকান |
| Modify | `linux/my_application.cc` | নেটিভ টাইটেল বার লুকান |

বিজনেস ফিচার পেজ (`lib/features/`-এর অধীনে ৬টি ফাইল) — **কোনো পরিবর্তন নেই**।

## স্কোপ সীমানা

- স্কোপের মধ্যে: শেল লেআউট, নেভিগেশন, টাইটেল বার, প্ল্যাটফর্ম কনফিগারেশন
- স্কোপের বাইরে: নতুন বিজনেস ফিচার, ব্যাকএন্ড পরিবর্তন, CI/CD, স্প্ল্যাশ স্ক্রিন, অ্যাপ আইকন
