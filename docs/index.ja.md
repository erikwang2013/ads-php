# Docs ディレクトリ構成

[中文](docs/index.md) | [English](docs/index.en.md) | [한국어](docs/index.ko.md) | [Русский](docs/index.ru.md) | [Deutsch](docs/index.de.md) | [Français](docs/index.fr.md) | [Español](docs/index.es.md) | [Português](docs/index.pt.md) | [हिन्दी](docs/index.hi.md) | [العربية](docs/index.ar.md) | [বাংলা](docs/index.bn.md) | [Bahasa Indonesia](docs/index.id.md) | [日本語](docs/index.ja.md)

```
docs/
├── index.md                                # 本ファイル
├── architecture.md                         # アーキテクチャ設計ドキュメント (デプロイ/セキュリティ/構成/データモデル)
├── features.md                             # 機能設計ドキュメント (21 モジュール/業務フロー)
├── api.md                                  # API インターフェースドキュメント (インターフェース定義はここに一元化)
├── usage.md                                # 使用说明文档 (安装后使用流程)
├── versions.md                             # 3 バージョン比較
├── apidoc-header.md                        # hg/apidoc 共通仕様ヘッダー
├── diagrams/                               # Mermaid 可視化チャート (5 個)
│   ├── architecture.md                     #   システムアーキテクチャ図 (C4 コンテナレベルトポロジ)
│   ├── request-flow.md                     #   リクエストフロー図 (15+10 層ミドルウェアパイプライン)
│   ├── functional-modules.md               #   機能モジュール図 (21 モジュール依存関係+データフロー)
│   ├── data-lifecycle.md                   #   データライフサイクル図 (6 段階+ガントチャート+キャッシュ状態遷移図)
│   └── security.md                         #   セキュリティアーキテクチャ図
├── skills/                                 # 再利用可能なプロジェクトスキル (11 個)
│   ├── adapter-generator.md                # プラットフォームアダプターテンプレート生成
│   ├── admin-page-generator.md             # Vue3 管理バックエンドページ生成
│   ├── api-endpoint.md                     # RESTful API エンドポイント追加
│   ├── erik-stack.md                       # Erik Stack 8 パッケージ利用ガイド
│   ├── migration-generator.md              # SQL マイグレーションファイル生成
│   ├── tdd-workflow.md                     # TDD 検証フロー
│   ├── security-middleware.md              # セキュリティミドルウェア開発
│   ├── version-split.md                    # 3 バージョン分割管理
│   ├── cache-strategy.md                   # 3 段キャッシュ戦略
│   ├── attribution-setup.md                # クロスプラットフォームアトリビューションエンジン
│   └── high-concurrency.md                 # 高並行処理 8 項目の最適化
├── superpowers/
│   ├── specs/                              # 設計仕様
│   │   ├── design.md                       # 完全なシステムアーキテクチャ（74KB）
│   │   └── 2026-05-18-flutter-desktop-design.md  # Flutter デスクトップ設計仕様
│   └── plans/                              # 実装計画（タイムライン順）
│       ├── 2026-05-14-phase1-foundation.md       # Phase 1: 基本骨組み
│       ├── 2026-05-15-phase2-adapters-reports.md # Phase 2: アダプター+レポート
│       ├── 2026-05-15-phase3-more-adapters.md    # Phase 3: アダプター追加
│       ├── 2026-05-15-phase4-17-adapters.md      # Phase 4: 17 アダプター
│       ├── 2026-05-15-phase5-alert-system.md     # Phase 5: アラートシステム
│       ├── 2026-05-16-phase5-stabilization.md    # Phase 5: 安定性
│       ├── 2026-05-15-phase6-erik-stack.md       # Phase 6: Erik Stack
│       └── 2026-05-18-flutter-desktop.md         # Flutter デスクトップ実装
├── alipay.png / weixinpay.png              # 投げ銭用 QR コード
```

## 言語バージョン

各ドキュメントは 12 言語バージョン（`<name>.<lang>.md`）、図は `<name>.<lang>.svg` を提供：

| 言語 | コード | 言語 | コード |
|------|------|------|------|
| 中文 | zh | Português | pt |
| English | en | हिन्दी | hi |
| 한국어 | ko | العربية | ar |
| Русский | ru | বাংলা | bn |
| Deutsch | de | Bahasa Indonesia | id |
| Français | fr | 日本語 | ja |
| Español | es | | |

## 投げ銭サポート

| WeChat | Alipay |
|:---:|:---:|
| ![微信](weixinpay.png "微信") | ![支付宝](alipay.png "支付宝") |

### 全球转账打赏 (Global Transfer Donation)

**收款人信息 (Beneficiary)**

| 字段 | 值 |
|------|-----|
| 收款人姓名 (Name) | WANG KEXUN |
| 收款账户号码 (Account No.) | 881015918251 |

**收款银行 (Receiving Bank) — ZA Bank**

| 字段 | 值 |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| 银行名称 (Bank Name) | ZA Bank Limited |
| 银行编号 (Bank Code) | 387 |
| 银行地址 (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **跨境汇款代理银行（如需，Correspondent Bank）**：此为代理（中转）银行信息，非收款银行信息，请向汇款银行查询是否需要提供。
>
> - **港元、人民币及美元**：Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · 银行编号 006 · Hong Kong Branch（分行编号 391）· Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **其他币种**：THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States
