# Flutter Desktop Cross-Platform Support — Design Spec

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

Tanggal: 2026-05-18
Status: disetujui

## Tujuan

Memperluas proyek Flutter `apps/flutter/` yang ada untuk mendukung iPadOS, macOS, Windows, dan Linux sebagai platform desktop kelas satu, menggunakan gaya UI panel admin desktop klasik (terinspirasi Ant Design Pro / Element UI). Dukungan Web dipertahankan dan ditingkatkan ke tata letak gaya desktop yang sama.

## Platform Target

| Platform | Status |
|----------|--------|
| Web | Pertahankan, tingkatkan ke tata letak desktop |
| iPadOS | Baru, tata letak sama dengan desktop (PC layar kecil) |
| macOS | Baru, title bar kustom |
| Windows | Baru, title bar kustom |
| Linux | Baru, title bar kustom |

## Desain

### Arsitektur

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

### Pohon Komponen

- `DesktopShell` — kontainer tata letak tingkat atas, menggantikan `AppShell`
- `TitleBar` — title bar kustom: nama aplikasi di kiri, kontrol jendela (min/max/close) di kanan, seret untuk memindah
- `SideNav` — navigasi samping dua tingkat yang dapat dilipat, 240px terbuka → 64px terlipat dengan animasi
- `BreadcrumbBar` — dihasilkan otomatis dari path rute melalui konfigurasi menu bersama
- `AppShell`, `TopBar`, `BottomBar` — **dihapus**

### Konfigurasi Menu Dua Tingkat

Satu file data `menu_config.dart` menggerakkan baik render `SideNav` maupun generasi rute `GoRouter`:

```
/dashboard          → 仪表盘 (top-level)
/campaigns/list     → 广告管理 > 广告计划 (2nd level)
/campaigns/creative → 广告管理 > 创意管理 (2nd level)
/reports            → 数据报表 (top-level)
/accounts           → 平台账户 (top-level)
/alerts             → 告警管理 (top-level)
```

### Routing

`GoRouter` `ShellRoute` membungkus rute dengan `DesktopShell`. Rute bertingkat di bawah `/campaigns` dipetakan ke grup menu dua tingkat.

### Perilaku Responsif

Tanpa percabangan platform. Satu tata letak beradaptasi dengan lebar jendela:

| Lebar | Perilaku |
|-------|----------|
| ≥ 1024px | Sidebar terbuka, desktop penuh |
| 768–1023px | Sidebar terlipat secara default |
| < 768px | Sidebar terlipat, padding konten dikurangi |
| Jendela minimum | 680×480 |

### Tumpukan Teknologi (tanpa perubahan)

- State: Riverpod
- Routing: GoRouter
- HTTP: Dio
- Charts: fl_chart
- Dep baru: `window_manager` ^0.3.0 untuk kontrol jendela

## Perubahan File

| Aksi | File | Catatan |
|--------|------|-------|
| Tulis ulang | `lib/features/shell/app_shell.dart` | `DesktopShell` baru |
| Tulis ulang | `lib/features/shell/side_nav.dart` | Dua tingkat + dapat dilipat |
| Baru | `lib/features/shell/title_bar.dart` | Title bar kustom |
| Baru | `lib/features/shell/breadcrumb.dart` | Widget breadcrumb |
| Hapus | `lib/features/shell/top_bar.dart` | Top bar lama |
| Baru | `lib/config/menu_config.dart` | Data menu bersama |
| Modifikasi | `lib/router.dart` | DesktopShell + rute bertingkat |
| Modifikasi | `lib/main.dart` | Inisialisasi window_manager |
| Modifikasi | `lib/theme.dart` | Tema berorientasi desktop |
| Modifikasi | `pubspec.yaml` | Tambah dependensi window_manager |
| Generate | `macos/`, `windows/`, `linux/` | Runner platform |
| Modifikasi | `macos/Runner/MainFlutterWindow.swift` | Sembunyikan title bar native |
| Modifikasi | `windows/runner/main.cpp` | Sembunyikan title bar native |
| Modifikasi | `linux/my_application.cc` | Sembunyikan title bar native |

Halaman fitur bisnis (6 file di bawah `lib/features/`) — **tanpa perubahan**.

## Batasan Cakupan

- Dalam cakupan: tata letak shell, navigasi, title bar, konfigurasi platform
- Di luar cakupan: fitur bisnis baru, perubahan backend, CI/CD, splash screen, ikon aplikasi
