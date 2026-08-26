# Отчёт о тестировании Go-модуля

[中文](docs/test-reports/go.md) | [English](docs/test-reports/go.en.md) | [한국어](docs/test-reports/go.ko.md) | [Русский](docs/test-reports/go.ru.md) | [Deutsch](docs/test-reports/go.de.md) | [Français](docs/test-reports/go.fr.md) | [Español](docs/test-reports/go.es.md) | [Português](docs/test-reports/go.pt.md) | [हिन्दी](docs/test-reports/go.hi.md) | [العربية](docs/test-reports/go.ar.md) | [বাংলা](docs/test-reports/go.bn.md) | [Bahasa Indonesia](docs/test-reports/go.id.md) | [日本語](docs/test-reports/go.ja.md)

- Заключение: **N/A (нет Go-модуля)**
- Дата: 2026-08-27

## Свидетельства сканирования

Во всём репозитории (775 файлов, исключая `.git` / `node_modules` / `vendor`) не найдено ни одного Go-исходника или модульного файла:

- `*.go`: 0
- `go.mod` / `go.sum`: 0
- Повторное сканирование без учёта регистра (`.go` / `go.mod` / `go.sum`): 0
- Git-субмодули: нет (нет `.gitmodules`, `git submodule status` пуст)
- Grep по всему репозиторию ключевых слов тулчейна (`go build` / `go test` / `Golang`): 0 совпадений
- В Makefile, docker-compose.yml, Dockerfile*, .github/workflows (ci.yml, deploy.yml), scripts нет Go-шагов сборки

## Пояснение N/A: чем Go заменён в кодовой базе

| Роль | Фактический стек |
|------|-----------|
| Бэкенд-сервис | PHP 8 (фреймворк webman), каталог `service/` |
| Сборка/CI | Makefile + docker-compose + GitHub Actions (PHP/Node) |
| Системные скрипты | bash (28 .sh) |

Заключение: в данной кодовой базе нет Go-кода, писать или запускать юнит-тесты нечего. Если в дальнейшем будет добавлен Go-микросервис, необходимо дополнить настоящий отчёт после прохождения `go test ./...`.
