# Flutter डेस्कटॉप क्रॉस-प्लेटफ़ॉर्म समर्थन — डिज़ाइन स्पेक

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

दिनांक: 2026-05-18
स्थिति: अनुमोदित

## लक्ष्य

मौजूदा `apps/flutter/` Flutter प्रोजेक्ट का विस्तार करके iPadOS, macOS, Windows और Linux को प्रथम श्रेणी के डेस्कटॉप प्लेटफ़ॉर्म के रूप में समर्थन दें, क्लासिक डेस्कटॉप एडमिन-पैनल UI शैली (Ant Design Pro / Element UI प्रेरित) का उपयोग करते हुए। वेब समर्थन बनाए रखा गया है और उसे भी उसी डेस्कटॉप-शैली लेआउट में अपग्रेड किया गया है।

## लक्ष्य प्लेटफ़ॉर्म

| प्लेटफ़ॉर्म | स्थिति |
|----------|--------|
| Web | बनाए रखें, डेस्कटॉप लेआउट में अपग्रेड करें |
| iPadOS | नया, डेस्कटॉप जैसा ही लेआउट (छोटी स्क्रीन वाला PC) |
| macOS | नया, कस्टम टाइटल बार |
| Windows | नया, कस्टम टाइटल बार |
| Linux | नया, कस्टम टाइटल बार |

## डिज़ाइन

### आर्किटेक्चर

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

### कंपोनेंट ट्री

- `DesktopShell` — शीर्ष-स्तरीय लेआउट कंटेनर, `AppShell` की जगह लेता है
- `TitleBar` — कस्टम टाइटल बार: बाईं ओर ऐप नाम, दाईं ओर विंडो कंट्रोल (न्यूनतम/अधिकतम/बंद), ड्रैग-टू-मूव
- `SideNav` — संकुचित होने योग्य दो-स्तरीय साइड नेविगेशन, 240px विस्तारित → एनिमेशन के साथ 64px संकुचित
- `BreadcrumbBar` — साझा मेनू कॉन्फ़िग से रूट पाथ से स्वतः उत्पन्न
- `AppShell`, `TopBar`, `BottomBar` — **हटाए गए**

### दो-स्तरीय मेनू कॉन्फ़िग

एक एकल `menu_config.dart` डेटा फ़ाइल `SideNav` रेंडरिंग और `GoRouter` रूट जनरेशन दोनों को चलाती है:

```
/dashboard          → 仪表盘 (top-level)
/campaigns/list     → 广告管理 > 广告计划 (2nd level)
/campaigns/creative → 广告管理 > 创意管理 (2nd level)
/reports            → 数据报表 (top-level)
/accounts           → 平台账户 (top-level)
/alerts             → 告警管理 (top-level)
```

### रूटिंग

`GoRouter` `ShellRoute` रूट्स को `DesktopShell` से लपेटता है। `/campaigns` के अंतर्गत नेस्टेड रूट्स दो-स्तरीय मेनू समूह में मैप होते हैं।

### रिस्पॉन्सिव व्यवहार

कोई प्लेटफ़ॉर्म ब्रांचिंग नहीं। एकल लेआउट विंडो चौड़ाई के अनुसार अनुकूलित होता है:

| चौड़ाई | व्यवहार |
|-------|----------|
| ≥ 1024px | साइडबार विस्तारित, पूर्ण डेस्कटॉप |
| 768–1023px | साइडबार डिफ़ॉल्ट रूप से संकुचित |
| < 768px | साइडबार संकुचित, कम कंटेंट पैडिंग |
| न्यूनतम विंडो | 680×480 |

### तकनीकी स्टैक (कोई बदलाव नहीं)

- स्टेट: Riverpod
- रूटिंग: GoRouter
- HTTP: Dio
- चार्ट: fl_chart
- नई डिपेंडेंसी: विंडो कंट्रोल के लिए `window_manager` ^0.3.0

## फ़ाइल परिवर्तन

| क्रिया | फ़ाइल | नोट्स |
|--------|------|-------|
| Rewrite | `lib/features/shell/app_shell.dart` | नया `DesktopShell` |
| Rewrite | `lib/features/shell/side_nav.dart` | दो-स्तरीय + संकुचित होने योग्य |
| New | `lib/features/shell/title_bar.dart` | कस्टम टाइटल बार |
| New | `lib/features/shell/breadcrumb.dart` | ब्रेडक्रंब विजेट |
| Delete | `lib/features/shell/top_bar.dart` | पुराना टॉप बार |
| New | `lib/config/menu_config.dart` | साझा मेनू डेटा |
| Modify | `lib/router.dart` | DesktopShell + नेस्टेड रूट्स |
| Modify | `lib/main.dart` | window_manager इनिशियलाइज़ करें |
| Modify | `lib/theme.dart` | डेस्कटॉप-उन्मुख थीम |
| Modify | `pubspec.yaml` | window_manager डिपेंडेंसी जोड़ें |
| Generate | `macos/`, `windows/`, `linux/` | प्लेटफ़ॉर्म रनर |
| Modify | `macos/Runner/MainFlutterWindow.swift` | नेटिव टाइटल बार छिपाएँ |
| Modify | `windows/runner/main.cpp` | नेटिव टाइटल बार छिपाएँ |
| Modify | `linux/my_application.cc` | नेटिव टाइटल बार छिपाएँ |

बिज़नेस फ़ीचर पेज (`lib/features/` के अंतर्गत 6 फ़ाइलें) — **कोई बदलाव नहीं**।

## स्कोप सीमाएँ

- दायरे में: शेल लेआउट, नेविगेशन, टाइटल बार, प्लेटफ़ॉर्म कॉन्फ़िगरेशन
- दायरे से बाहर: नई बिज़नेस सुविधाएँ, बैकएंड परिवर्तन, CI/CD, स्प्लैश स्क्रीन, ऐप आइकन
