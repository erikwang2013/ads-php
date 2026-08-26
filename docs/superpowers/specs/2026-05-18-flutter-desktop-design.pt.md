# Suporte Cross-Platform Flutter Desktop — Design Spec

[中文](docs/superpowers/specs/2026-05-18-flutter-desktop-design.md) | [English](docs/superpowers/specs/2026-05-18-flutter-desktop-design.en.md) | [한국어](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ko.md) | [Русский](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ru.md) | [Deutsch](docs/superpowers/specs/2026-05-18-flutter-desktop-design.de.md) | [Français](docs/superpowers/specs/2026-05-18-flutter-desktop-design.fr.md) | [Español](docs/superpowers/specs/2026-05-18-flutter-desktop-design.es.md) | [Português](docs/superpowers/specs/2026-05-18-flutter-desktop-design.pt.md) | [हिन्दी](docs/superpowers/specs/2026-05-18-flutter-desktop-design.hi.md) | [العربية](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ar.md) | [বাংলা](docs/superpowers/specs/2026-05-18-flutter-desktop-design.bn.md) | [Bahasa Indonesia](docs/superpowers/specs/2026-05-18-flutter-desktop-design.id.md) | [日本語](docs/superpowers/specs/2026-05-18-flutter-desktop-design.ja.md)

Date: 2026-05-18
Status: approved

## Objetivo

Estender o projeto Flutter existente em `apps/flutter/` para suportar iPadOS, macOS, Windows e Linux como plataformas desktop de primeira classe, usando um estilo de UI clássico de painel administrativo desktop (inspirado em Ant Design Pro / Element UI). O suporte Web é mantido e atualizado para o mesmo layout estilo desktop.

## Plataformas Alvo

| Plataforma | Status |
|------------|--------|
| Web | Manter, atualizar para layout desktop |
| iPadOS | Nova, mesmo layout do desktop (PC de tela pequena) |
| macOS | Nova, barra de título personalizada |
| Windows | Nova, barra de título personalizada |
| Linux | Nova, barra de título personalizada |

## Design

### Arquitetura

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

### Árvore de Componentes

- `DesktopShell` — contêiner de layout de nível superior, substitui `AppShell`
- `TitleBar` — barra de título personalizada: nome do aplicativo à esquerda, controles de janela (min/max/close) à direita, arrastar para mover
- `SideNav` — navegação lateral de dois níveis recolhível, 240px expandida → 64px recolhida com animação
- `BreadcrumbBar` — gerada automaticamente a partir do caminho da rota via configuração de menu compartilhada
- `AppShell`, `TopBar`, `BottomBar` — **removidos**

### Configuração de Menu de Dois Níveis

Um único arquivo de dados `menu_config.dart` conduz tanto a renderização da `SideNav` quanto a geração de rotas do `GoRouter`:

```
/dashboard          → 仪表盘 (nível superior)
/campaigns/list     → 广告管理 > 广告计划 (2º nível)
/campaigns/creative → 广告管理 > 创意管理 (2º nível)
/reports            → 数据报表 (nível superior)
/accounts           → 平台账户 (nível superior)
/alerts             → 告警管理 (nível superior)
```

### Roteamento

O `ShellRoute` do `GoRouter` envolve as rotas com o `DesktopShell`. Rotas aninhadas sob `/campaigns` mapeiam para o grupo de menu de dois níveis.

### Comportamento Responsivo

Sem ramificação por plataforma. Um único layout se adapta à largura da janela:

| Largura | Comportamento |
|---------|---------------|
| ≥ 1024px | Sidebar expandida, desktop completo |
| 768–1023px | Sidebar recolhida por padrão |
| < 768px | Sidebar recolhida, padding de conteúdo reduzido |
| Janela mínima | 680×480 |

### Pilha de Tecnologias (sem alterações)

- Estado: Riverpod
- Roteamento: GoRouter
- HTTP: Dio
- Gráficos: fl_chart
- Nova dependência: `window_manager` ^0.3.0 para controles de janela

## Alterações de Arquivos

| Ação | Arquivo | Notas |
|------|---------|-------|
| Reescrever | `lib/features/shell/app_shell.dart` | Novo `DesktopShell` |
| Reescrever | `lib/features/shell/side_nav.dart` | Dois níveis + recolhível |
| Novo | `lib/features/shell/title_bar.dart` | Barra de título personalizada |
| Novo | `lib/features/shell/breadcrumb.dart` | Widget de breadcrumb |
| Excluir | `lib/features/shell/top_bar.dart` | Barra superior antiga |
| Novo | `lib/config/menu_config.dart` | Dados de menu compartilhados |
| Modificar | `lib/router.dart` | DesktopShell + rotas aninhadas |
| Modificar | `lib/main.dart` | Inicializar window_manager |
| Modificar | `lib/theme.dart` | Tema orientado a desktop |
| Modificar | `pubspec.yaml` | Adicionar dependência window_manager |
| Gerar | `macos/`, `windows/`, `linux/` | Runners de plataforma |
| Modificar | `macos/Runner/MainFlutterWindow.swift` | Ocultar barra de título nativa |
| Modificar | `windows/runner/main.cpp` | Ocultar barra de título nativa |
| Modificar | `linux/my_application.cc` | Ocultar barra de título nativa |

As páginas de recursos de negócio (6 arquivos em `lib/features/`) — **sem alterações**.

## Limites de Escopo

- No escopo: layout do shell, navegação, barra de título, configuração de plataforma
- Fora do escopo: novos recursos de negócio, alterações no backend, CI/CD, tela de splash, ícone do aplicativo
