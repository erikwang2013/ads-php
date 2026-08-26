# Flutter Desktop Cross-Platform Support — Design Spec

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

Date: 2026-05-18
Status: approved

## Цель

Расширить существующий проект Flutter `apps/flutter/` для поддержки iPadOS, macOS, Windows и Linux как полноценных десктопных платформ, используя классический стиль интерфейса десктопной админ-панели (в духе Ant Design Pro / Element UI). Веб-поддержка сохраняется и обновляется до того же десктопного макета.

## Целевые платформы

| Платформа | Статус |
|----------|--------|
| Web | Сохранить, обновить до десктопного макета |
| iPadOS | Новая, тот же макет, что и десктоп (ПК с маленьким экраном) |
| macOS | Новая, пользовательская строка заголовка |
| Windows | Новая, пользовательская строка заголовка |
| Linux | Новая, пользовательская строка заголовка |

## Дизайн

### Архитектура

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

### Дерево компонентов

- `DesktopShell` — контейнер макета верхнего уровня, заменяет `AppShell`
- `TitleBar` — пользовательская строка заголовка: название приложения слева, элементы управления окном (свернуть/развернуть/закрыть) справа, перетаскивание для перемещения
- `SideNav` — сворачиваемая двухуровневая боковая навигация, 240px развернуто → 64px свернуто с анимацией
- `BreadcrumbBar` — автоматически генерируется из пути маршрута через общий конфиг меню
- `AppShell`, `TopBar`, `BottomBar` — **удалены**

### Двухуровневый конфиг меню

Один файл данных `menu_config.dart` управляет и рендерингом `SideNav`, и генерацией маршрутов `GoRouter`:

```
/dashboard          → 仪表盘 (top-level)
/campaigns/list     → 广告管理 > 广告计划 (2nd level)
/campaigns/creative → 广告管理 > 创意管理 (2nd level)
/reports            → 数据报表 (top-level)
/accounts           → 平台账户 (top-level)
/alerts             → 告警管理 (top-level)
```

### Маршрутизация

`ShellRoute` в `GoRouter` оборачивает маршруты в `DesktopShell`. Вложенные маршруты под `/campaigns` соответствуют группе двухуровневого меню.

### Адаптивное поведение

Без ветвления по платформам. Единый макет адаптируется к ширине окна:

| Ширина | Поведение |
|-------|----------|
| ≥ 1024px | Сайдбар развернут, полноценный десктоп |
| 768–1023px | Сайдбар свернут по умолчанию |
| < 768px | Сайдбар свернут, уменьшенные отступы контента |
| Минимальное окно | 680×480 |

### Технологический стек (без изменений)

- Состояние: Riverpod
- Маршрутизация: GoRouter
- HTTP: Dio
- Графики: fl_chart
- Новая зависимость: `window_manager` ^0.3.0 для управления окнами

## Изменения файлов

| Действие | Файл | Примечания |
|--------|------|-------|
| Переписать | `lib/features/shell/app_shell.dart` | Новый `DesktopShell` |
| Переписать | `lib/features/shell/side_nav.dart` | Двухуровневый + сворачиваемый |
| Новый | `lib/features/shell/title_bar.dart` | Пользовательская строка заголовка |
| Новый | `lib/features/shell/breadcrumb.dart` | Виджет хлебных крошек |
| Удалить | `lib/features/shell/top_bar.dart` | Старая верхняя панель |
| Новый | `lib/config/menu_config.dart` | Общие данные меню |
| Изменить | `lib/router.dart` | DesktopShell + вложенные маршруты |
| Изменить | `lib/main.dart` | Инициализация window_manager |
| Изменить | `lib/theme.dart` | Тема под десктоп |
| Изменить | `pubspec.yaml` | Добавить зависимость window_manager |
| Сгенерировать | `macos/`, `windows/`, `linux/` | Платформенные раннеры |
| Изменить | `macos/Runner/MainFlutterWindow.swift` | Скрыть нативную строку заголовка |
| Изменить | `windows/runner/main.cpp` | Скрыть нативную строку заголовка |
| Изменить | `linux/my_application.cc` | Скрыть нативную строку заголовка |

Бизнес-страницы (6 файлов в `lib/features/`) — **без изменений**.

## Границы области

- В области: макет оболочки, навигация, строка заголовка, конфигурация платформ
- Вне области: новые бизнес-функции, изменения бэкенда, CI/CD, экран загрузки, иконка приложения
