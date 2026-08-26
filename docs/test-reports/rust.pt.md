# Relatório de testes do módulo Rust

[中文](docs/test-reports/rust.md) | [English](docs/test-reports/rust.en.md) | [한국어](docs/test-reports/rust.ko.md) | [Русский](docs/test-reports/rust.ru.md) | [Deutsch](docs/test-reports/rust.de.md) | [Français](docs/test-reports/rust.fr.md) | [Español](docs/test-reports/rust.es.md) | [Português](docs/test-reports/rust.pt.md) | [हिन्दी](docs/test-reports/rust.hi.md) | [العربية](docs/test-reports/rust.ar.md) | [বাংলা](docs/test-reports/rust.bn.md) | [Bahasa Indonesia](docs/test-reports/rust.id.md) | [日本語](docs/test-reports/rust.ja.md)

- Conclusão: **N/A (sem módulo Rust)**
- Data: 2026-08-27

## Evidências da varredura

Nenhum arquivo de origem ou módulo Rust encontrado em todo o repositório (775 arquivos, excluindo `.git` / `node_modules` / `vendor`):

- `*.rs`: 0
- `Cargo.toml` / `Cargo.lock`: 0
- `build.zig` / `*.zig`: 0
- Revarredura sem diferenciar maiúsculas (`.rs` / `cargo` / `rustc` / `build.zig`): 0
- Submódulos Git: nenhum (sem `.gitmodules`, `git submodule status` vazio)
- grep em todo o repositório por palavras-chave do toolchain (`cargo` / `rustc` / `Rust`): 0 ocorrências
- Makefile, docker-compose.yml, Dockerfile* e .github/workflows sem etapas de build Rust

## Explicação do N/A: substitutos de Rust no repositório

| Responsabilidade | Stack tecnológica real |
|------|-----------|
| App mobile (Android/iOS) | Dart (Flutter), `apps/flutter/` (24 arquivos .dart) |
| App HarmonyOS | ArkTS (.ets, 18 arquivos), `apps/harmonyos/` |
| Shell nativo de desktop do Flutter | C++ (linux/windows runner, 17 arquivos .cpp/.cc/.h no total, gerados pelo scaffolding do Flutter, não são código de negócio) |
| Backend | PHP 8 (webman), `service/` |

Conclusão: este repositório não contém código Rust, portanto não há testes unitários a escrever ou executar (`cargo test` sem alvos executáveis). Se um módulo Rust for introduzido no futuro, este relatório deve ser atualizado após `cargo test` passar.

