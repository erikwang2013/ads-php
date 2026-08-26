# Flutter Desktop クロスプラットフォーム対応 — 設計仕様

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

日付: 2026-05-18
ステータス: approved

## Goal

既存の `apps/flutter/` Flutter プロジェクトを拡張し、iPadOS、macOS、Windows、Linux をファーストクラスのデスクトッププラットフォームとしてサポートします。クラシックなデスクトップ管理パネル UI スタイル（Ant Design Pro / Element UI 由来）を採用。Web サポートは維持し、同じデスクトップスタイルのレイアウトにアップグレードします。

## Target Platforms

| プラットフォーム | ステータス |
|----------|--------|
| Web | 維持、デスクトップレイアウトにアップグレード |
| iPadOS | 新規、デスクトップと同じレイアウト（小型 PC） |
| macOS | 新規、カスタムタイトルバー |
| Windows | 新規、カスタムタイトルバー |
| Linux | 新規、カスタムタイトルバー |

## Design

### アーキテクチャ

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

### コンポーネントツリー

- `DesktopShell` — 最上位レイアウトコンテナ、`AppShell` を置き換え
- `TitleBar` — カスタムタイトルバー: 左にアプリ名、右にウィンドウ操作ボタン (min/max/close)、ドラッグで移動
- `SideNav` — 折りたたみ可能な 2 段階サイドナビゲーション、240px 展開 → 64px 折りたたみ（アニメーション付き）
- `BreadcrumbBar` — 共有メニュー設定からルートパスに基づき自動生成
- `AppShell`, `TopBar`, `BottomBar` — **削除**

### 2 段階メニュー設定

単一の `menu_config.dart` データファイルが `SideNav` のレンダリングと `GoRouter` のルート生成の両方を駆動します:

```
/dashboard          → 仪表盘 (top-level)
/campaigns/list     → 广告管理 > 广告计划 (2nd level)
/campaigns/creative → 广告管理 > 创意管理 (2nd level)
/reports            → 数据报表 (top-level)
/accounts           → 平台账户 (top-level)
/alerts             → 告警管理 (top-level)
```

### ルーティング

`GoRouter` の `ShellRoute` がルートを `DesktopShell` でラップします。`/campaigns` 配下のネストルートは 2 段階メニューグループに対応します。

### レスポンシブ動作

プラットフォーム分岐なし。単一レイアウトがウィンドウ幅に適応します:

| 幅 | 動作 |
|-------|----------|
| ≥ 1024px | サイドバー展開、フルデスクトップ |
| 768–1023px | サイドバーはデフォルトで折りたたみ |
| < 768px | サイドバー折りたたみ、コンテンツパディング縮小 |
| 最小ウィンドウ | 680×480 |

### 技術スタック（変更なし）

- State: Riverpod
- Routing: GoRouter
- HTTP: Dio
- Charts: fl_chart
- 新規依存: `window_manager` ^0.3.0（ウィンドウ操作用）

## File Changes

| アクション | ファイル | メモ |
|--------|------|-------|
| 書き換え | `lib/features/shell/app_shell.dart` | 新 `DesktopShell` |
| 書き換え | `lib/features/shell/side_nav.dart` | 2 段階 + 折りたたみ可能 |
| 新規 | `lib/features/shell/title_bar.dart` | カスタムタイトルバー |
| 新規 | `lib/features/shell/breadcrumb.dart` | パンくずウィジェット |
| 削除 | `lib/features/shell/top_bar.dart` | 旧トップバー |
| 新規 | `lib/config/menu_config.dart` | 共有メニューデータ |
| 変更 | `lib/router.dart` | DesktopShell + ネストルート |
| 変更 | `lib/main.dart` | window_manager 初期化 |
| 変更 | `lib/theme.dart` | デスクトップ向けテーマ |
| 変更 | `pubspec.yaml` | window_manager 依存を追加 |
| 生成 | `macos/`, `windows/`, `linux/` | プラットフォームランナー |
| 変更 | `macos/Runner/MainFlutterWindow.swift` | ネイティブタイトルバーを非表示 |
| 変更 | `windows/runner/main.cpp` | ネイティブタイトルバーを非表示 |
| 変更 | `linux/my_application.cc` | ネイティブタイトルバーを非表示 |

業務機能ページ（`lib/features/` 配下 6 ファイル）— **変更なし**。

## Scope Boundaries

- 対象: シェルレイアウト、ナビゲーション、タイトルバー、プラットフォーム設定
- 対象外: 新規ビジネス機能、バックエンド変更、CI/CD、スプラッシュ画面、アプリアイコン
