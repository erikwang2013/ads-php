# Go モジュールテストレポート

[中文](docs/test-reports/go.md) | [English](docs/test-reports/go.en.md) | [한국어](docs/test-reports/go.ko.md) | [Русский](docs/test-reports/go.ru.md) | [Deutsch](docs/test-reports/go.de.md) | [Français](docs/test-reports/go.fr.md) | [Español](docs/test-reports/go.es.md) | [Português](docs/test-reports/go.pt.md) | [हिन्दी](docs/test-reports/go.hi.md) | [العربية](docs/test-reports/go.ar.md) | [বাংলা](docs/test-reports/go.bn.md) | [Bahasa Indonesia](docs/test-reports/go.id.md) | [日本語](docs/test-reports/go.ja.md)

- 結論: **N/A（Go モジュールなし）**
- 日付: 2026-08-27

## スキャン証跡

全リポジトリ（775 ファイル、`.git` / `node_modules` / `vendor` を除外）で Go ソースファイルやモジュールファイルは見つかりませんでした:

- `*.go`: 0 個
- `go.mod` / `go.sum`: 0 個
- 大文字小文字を区別しない再スキャン（`.go` / `go.mod` / `go.sum`）: 0 個
- Git サブモジュール: なし（`.gitmodules` なし、`git submodule status` は空）
- 全リポジトリ grep ツールチェーンキーワード（`go build` / `go test` / `Golang`）: 0 ヒット
- Makefile、docker-compose.yml、Dockerfile*、.github/workflows（ci.yml、deploy.yml）、scripts に Go ビルドステップなし

## N/A の説明: コードベースにおける Go の代替物

| 役割 | 実際の技術スタック |
|------|-----------|
| バックエンドサービス | PHP 8（webman フレームワーク）、`service/` ディレクトリ |
| ビルド/CI | Makefile + docker-compose + GitHub Actions（PHP/Node） |
| システムスクリプト | bash（28 個の .sh） |

結論: 本コードベースに Go コードは含まれず、記述・実行できるユニットテストはありません。今後 Go マイクロサービスを導入する場合は、`go test ./...` が通った後に本レポートを補充する必要があります。
