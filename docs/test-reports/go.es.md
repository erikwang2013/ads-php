# Informe de Pruebas del Módulo Go

[中文](docs/test-reports/go.md) | [English](docs/test-reports/go.en.md) | [한국어](docs/test-reports/go.ko.md) | [Русский](docs/test-reports/go.ru.md) | [Deutsch](docs/test-reports/go.de.md) | [Français](docs/test-reports/go.fr.md) | [Español](docs/test-reports/go.es.md) | [Português](docs/test-reports/go.pt.md) | [हिन्दी](docs/test-reports/go.hi.md) | [العربية](docs/test-reports/go.ar.md) | [বাংলা](docs/test-reports/go.bn.md) | [Bahasa Indonesia](docs/test-reports/go.id.md) | [日本語](docs/test-reports/go.ja.md)

- Conclusión: **N/A (sin módulo Go)**
- Fecha: 2026-08-27

## Evidencia del escaneo

No se encontró ningún archivo fuente o de módulo Go en todo el repositorio (775 archivos, excluyendo `.git` / `node_modules` / `vendor`):

- `*.go`: 0
- `go.mod` / `go.sum`: 0
- Re-escaneo sin distinción de mayúsculas (`.go` / `go.mod` / `go.sum`): 0
- Submódulos Git: ninguno (sin `.gitmodules`, `git submodule status` vacío)
- Búsqueda grep en todo el repositorio de palabras clave de la cadena de herramientas (`go build` / `go test` / `Golang`): 0 coincidencias
- Makefile, docker-compose.yml, Dockerfile*, .github/workflows (ci.yml, deploy.yml), scripts: sin pasos de compilación Go

## Nota N/A: sustitutos de Go en el código base

| Función | Pila tecnológica real |
|------|-----------|
| Servicio backend | PHP 8 (framework webman), directorio `service/` |
| Compilación/CI | Makefile + docker-compose + GitHub Actions (PHP/Node) |
| Scripts de sistema | bash (28 .sh) |

Conclusión: este código base no contiene código Go; no hay pruebas unitarias que escribir o ejecutar. Si en el futuro se introduce un microservicio Go, habrá que actualizar este informe tras pasar `go test ./...`.
