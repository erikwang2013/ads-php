# Flutter Desktop Cross-Platform Support — Design Spec

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

날짜: 2026-05-18
상태: 승인됨

## 목표

기존 `apps/flutter/` Flutter 프로젝트를 확장하여 iPadOS, macOS, Windows, Linux를 일급 데스크톱 플랫폼으로 지원하고, 클래식 데스크톱 관리자 패널 UI 스타일(Ant Design Pro / Element UI 영감)을 적용합니다. 웹 지원은 유지되며 동일한 데스크톱 스타일 레이아웃으로 업그레이드됩니다.

## 대상 플랫폼

| 플랫폼 | 상태 |
|----------|--------|
| Web | 유지, 데스크톱 레이아웃으로 업그레이드 |
| iPadOS | 신규, 데스크톱과 동일한 레이아웃 (소형 화면 PC) |
| macOS | 신규, 커스텀 타이틀 바 |
| Windows | 신규, 커스텀 타이틀 바 |
| Linux | 신규, 커스텀 타이틀 바 |

## 설계

### 아키텍처

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

### 컴포넌트 트리

- `DesktopShell` — 최상위 레이아웃 컨테이너, `AppShell` 대체
- `TitleBar` — 커스텀 타이틀 바: 왼쪽 앱 이름, 오른쪽 창 컨트롤(최소화/최대화/닫기), 드래그 이동
- `SideNav` — 접이식 2단계 사이드 내비게이션, 240px 확장 → 64px 접기 (애니메이션)
- `BreadcrumbBar` — 공유 메뉴 설정을 통해 라우트 경로에서 자동 생성
- `AppShell`, `TopBar`, `BottomBar` — **제거됨**

### 2단계 메뉴 설정

단일 `menu_config.dart` 데이터 파일이 `SideNav` 렌더링과 `GoRouter` 라우트 생성을 모두 구동:

```
/dashboard          → 仪表盘 (top-level)
/campaigns/list     → 广告管理 > 广告计划 (2nd level)
/campaigns/creative → 广告管理 > 创意管理 (2nd level)
/reports            → 数据报表 (top-level)
/accounts           → 平台账户 (top-level)
/alerts             → 告警管理 (top-level)
```

### 라우팅

`GoRouter` `ShellRoute`가 라우트를 `DesktopShell`로 감쌉니다. `/campaigns` 하위의 중첩 라우트는 2단계 메뉴 그룹에 매핑됩니다.

### 반응형 동작

플랫폼 분기 없음. 단일 레이아웃이 창 너비에 적응:

| 너비 | 동작 |
|-------|----------|
| ≥ 1024px | 사이드바 확장, 전체 데스크톱 |
| 768–1023px | 사이드바 기본 접힘 |
| < 768px | 사이드바 접힘, 콘텐츠 패딩 축소 |
| 최소 창 | 680×480 |

### 기술 스택 (변경 없음)

- 상태: Riverpod
- 라우팅: GoRouter
- HTTP: Dio
- 차트: fl_chart
- 새 의존성: `window_manager` ^0.3.0 (창 컨트롤용)

## 파일 변경

| 작업 | 파일 | 비고 |
|--------|------|-------|
| 재작성 | `lib/features/shell/app_shell.dart` | 새 `DesktopShell` |
| 재작성 | `lib/features/shell/side_nav.dart` | 2단계 + 접이식 |
| 신규 | `lib/features/shell/title_bar.dart` | 커스텀 타이틀 바 |
| 신규 | `lib/features/shell/breadcrumb.dart` | 브레드크럼 위젯 |
| 삭제 | `lib/features/shell/top_bar.dart` | 기존 상단 바 |
| 신규 | `lib/config/menu_config.dart` | 공유 메뉴 데이터 |
| 수정 | `lib/router.dart` | DesktopShell + 중첩 라우트 |
| 수정 | `lib/main.dart` | window_manager 초기화 |
| 수정 | `lib/theme.dart` | 데스크톱 중심 테마 |
| 수정 | `pubspec.yaml` | window_manager 의존성 추가 |
| 생성 | `macos/`, `windows/`, `linux/` | 플랫폼 러너 |
| 수정 | `macos/Runner/MainFlutterWindow.swift` | 네이티브 타이틀 바 숨기기 |
| 수정 | `windows/runner/main.cpp` | 네이티브 타이틀 바 숨기기 |
| 수정 | `linux/my_application.cc` | 네이티브 타이틀 바 숨기기 |

비즈니스 기능 페이지 (`lib/features/` 아래 6개 파일) — **변경 없음**.

## 범위 경계

- 범위 내: 셸 레이아웃, 내비게이션, 타이틀 바, 플랫폼 설정
- 범위 외: 새 비즈니스 기능, 백엔드 변경, CI/CD, 스플래시 화면, 앱 아이콘
