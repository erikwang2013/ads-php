# دعم Flutter عبر منصات سطح المكتب — مواصفات التصميم

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

التاريخ: 2026-05-18
الحالة: معتمدة

## الهدف

توسيع مشروع Flutter الحالي في `apps/flutter/` لدعم iPadOS وmacOS وWindows وLinux كمنصات سطح مكتب من الدرجة الأولى، باستخدام نمط واجهة لوحة إدارة سطح المكتب الكلاسيكي (مستوحى من Ant Design Pro / Element UI). يُحتفظ بدعم الويب ويُرقّى إلى نفس نمط تخطيط سطح المكتب.

## المنصات المستهدفة

| المنصة | الحالة |
|----------|--------|
| Web | إبقاء، ترقية إلى تخطيط سطح المكتب |
| iPadOS | جديد، نفس تخطيط سطح المكتب (PC بشاشة صغيرة) |
| macOS | جديد، شريط عنوان مخصص |
| Windows | جديد، شريط عنوان مخصص |
| Linux | جديد، شريط عنوان مخصص |

## التصميم

### البنية

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

### شجرة المكونات

- `DesktopShell` — حاوية التخطيط العليا، تحل محل `AppShell`
- `TitleBar` — شريط عنوان مخصص: اسم التطبيق يسارًا، أزرار النافذة (تصغير/تكبير/إغلاق) يمينًا، سحب للتحريك
- `SideNav` — تنقل جانبي بمستويين قابل للطيّ، 240px موسعًا → 64px مطويًا مع حركة
- `BreadcrumbBar` — يُولَّد تلقائيًا من مسار التوجيه عبر تكوين القائمة المشترك
- `AppShell`, `TopBar`, `BottomBar` — **مُزالة**

### تكوين القائمة بمستويين

ملف بيانات واحد `menu_config.dart` يقود كلاً من عرض `SideNav` وتوليد مسارات `GoRouter`:

```
/dashboard          → 仪表盘 (top-level)
/campaigns/list     → 广告管理 > 广告计划 (2nd level)
/campaigns/creative → 广告管理 > 创意管理 (2nd level)
/reports            → 数据报表 (top-level)
/accounts           → 平台账户 (top-level)
/alerts             → 告警管理 (top-level)
```

### التوجيه

يلف `ShellRoute` من `GoRouter` المسارات بـ `DesktopShell`. وتتوافق المسارات المتداخلة تحت `/campaigns` مع مجموعة القائمة ذات المستويين.

### السلوك المتجاوب

لا تفرع حسب المنصة. يتكيف تخطيط واحد مع عرض النافذة:

| العرض | السلوك |
|-------|----------|
| ≥ 1024px | الشريط الجانبي موسع، سطح مكتب كامل |
| 768–1023px | الشريط الجانبي مطوي افتراضيًا |
| < 768px | الشريط الجانبي مطوي، حشوة محتوى مخففة |
| الحد الأدنى للنافذة | 680×480 |

### التقنيات (بدون تغييرات)

- الحالة: Riverpod
- التوجيه: GoRouter
- HTTP: Dio
- الرسوم: fl_chart
- تبعية جديدة: `window_manager` ^0.3.0 لأزرار النافذة

## تغييرات الملفات

| الإجراء | الملف | ملاحظات |
|--------|------|-------|
| إعادة كتابة | `lib/features/shell/app_shell.dart` | `DesktopShell` جديدة |
| إعادة كتابة | `lib/features/shell/side_nav.dart` | مستويان + قابل للطيّ |
| جديد | `lib/features/shell/title_bar.dart` | شريط عنوان مخصص |
| جديد | `lib/features/shell/breadcrumb.dart` | مكوّن مسار التنقل |
| حذف | `lib/features/shell/top_bar.dart` | الشريط العلوي القديم |
| جديد | `lib/config/menu_config.dart` | بيانات القائمة المشتركة |
| تعديل | `lib/router.dart` | DesktopShell + مسارات متداخلة |
| تعديل | `lib/main.dart` | تهيئة window_manager |
| تعديل | `lib/theme.dart` | سمة موجهة لسطح المكتب |
| تعديل | `pubspec.yaml` | إضافة تبعية window_manager |
| توليد | `macos/`, `windows/`, `linux/` | مشغلات المنصات |
| تعديل | `macos/Runner/MainFlutterWindow.swift` | إخفاء شريط العنوان الأصلي |
| تعديل | `windows/runner/main.cpp` | إخفاء شريط العنوان الأصلي |
| تعديل | `linux/my_application.cc` | إخفاء شريط العنوان الأصلي |

صفحات الميزات التجارية (6 ملفات تحت `lib/features/`) — **بدون تغييرات**.

## حدود النطاق

- داخل النطاق: تخطيط الهيكل، التنقل، شريط العنوان، تهيئة المنصات
- خارج النطاق: ميزات أعمال جديدة، تغييرات الواجهة الخلفية، CI/CD، شاشة البداية، أيقونة التطبيق
