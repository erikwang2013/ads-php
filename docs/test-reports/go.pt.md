# Relatório de testes do módulo Go

[中文](docs/test-reports/go.md) | [English](docs/test-reports/go.en.md) | [한국어](docs/test-reports/go.ko.md) | [Русский](docs/test-reports/go.ru.md) | [Deutsch](docs/test-reports/go.de.md) | [Français](docs/test-reports/go.fr.md) | [Español](docs/test-reports/go.es.md) | [Português](docs/test-reports/go.pt.md) | [हिन्दी](docs/test-reports/go.hi.md) | [العربية](docs/test-reports/go.ar.md) | [বাংলা](docs/test-reports/go.bn.md) | [Bahasa Indonesia](docs/test-reports/go.id.md) | [日本語](docs/test-reports/go.ja.md)

- Conclusão: **N/A (sem módulo Go)**
- Data: 2026-08-27

## Evidências da varredura

Nenhum arquivo de origem ou módulo Go encontrado em todo o repositório (775 arquivos, excluindo `.git` / `node_modules` / `vendor`):

- `*.go`: 0
- `go.mod` / `go.sum`: 0
- Revarredura sem diferenciar maiúsculas (`.go` / `go.mod` / `go.sum`): 0
- Submódulos Git: nenhum (sem `.gitmodules`, `git submodule status` vazio)
- grep em todo o repositório por palavras-chave do toolchain (`go build` / `go test` / `Golang`): 0 ocorrências
- Makefile, docker-compose.yml, Dockerfile*, .github/workflows (ci.yml, deploy.yml) e scripts sem etapas de build Go

## Explicação do N/A: substitutos de Go no repositório

| Responsabilidade | Stack tecnológica real |
|------|-----------|
| Backend | PHP 8 (framework webman), diretório `service/` |
| Build/CI | Makefile + docker-compose + GitHub Actions (PHP/Node) |
| Scripts de sistema | bash (28 arquivos .sh) |

Conclusão: este repositório não contém código Go, portanto não há testes unitários a escrever ou executar. Se um microsserviço Go for introduzido no futuro, este relatório deve ser atualizado após `go test ./...` passar.

