# Estrutura do diretório Docs

[中文](docs/index.md) | [English](docs/index.en.md) | [한국어](docs/index.ko.md) | [Русский](docs/index.ru.md) | [Deutsch](docs/index.de.md) | [Français](docs/index.fr.md) | [Español](docs/index.es.md) | [Português](docs/index.pt.md) | [हिन्दी](docs/index.hi.md) | [العربية](docs/index.ar.md) | [বাংলা](docs/index.bn.md) | [Bahasa Indonesia](docs/index.id.md) | [日本語](docs/index.ja.md)

```
docs/
├── index.md                                # 本文件
├── architecture.md                         # 架构设计文档 (部署/安全/目录/数据模型)
├── features.md                             # 功能设计文档 (21 模块/业务流程)
├── api.md                                  # API 接口文档 (接口定义已统一移至此)
├── usage.md                                # 使用说明文档 (安装后使用流程)
├── versions.md                             # 三版本对比
├── apidoc-header.md                        # hg/apidoc 通用规范头部
├── diagrams/                               # Mermaid 可视化图表 (5 个)
│   ├── architecture.md                     #   系统架构图 (C4 容器级拓扑)
│   ├── request-flow.md                     #   请求流程图 (15+10 层中间件管道)
│   ├── functional-modules.md               #   功能模块图 (21 模块依赖+数据流)
│   ├── data-lifecycle.md                   #   数据生命周期图 (6 阶段+甘特图+缓存状态机)
│   └── security.md                         #   安全架构图
├── skills/                                 # 可复用项目技能 (11 个)
│   ├── adapter-generator.md                # 生成平台适配器模板
│   ├── admin-page-generator.md             # 生成 Vue3 管理后台页面
│   ├── api-endpoint.md                     # 添加 RESTful API 端点
│   ├── erik-stack.md                       # Erik Stack 8 包使用指南
│   ├── migration-generator.md              # SQL 迁移文件生成
│   ├── tdd-workflow.md                     # TDD 验证流程
│   ├── security-middleware.md              # 安全中间件开发
│   ├── version-split.md                    # 三版本拆分管理
│   ├── cache-strategy.md                   # 三级缓存策略
│   ├── attribution-setup.md                # 跨平台归因引擎
│   └── high-concurrency.md                 # 高并发 8 项优化
├── superpowers/
│   ├── specs/                              # 设计规范
│   │   ├── design.md                       # 完整系统架构（74KB）
│   │   └── 2026-05-18-flutter-desktop-design.md  # Flutter 桌面设计规范
│   └── plans/                              # 实施计划（按时间线）
│       ├── 2026-05-14-phase1-foundation.md       # Phase 1: 基础骨架
│       ├── 2026-05-15-phase2-adapters-reports.md # Phase 2: 适配器+报表
│       ├── 2026-05-15-phase3-more-adapters.md    # Phase 3: 更多适配器
│       ├── 2026-05-15-phase4-17-adapters.md      # Phase 4: 17个适配器
│       ├── 2026-05-15-phase5-alert-system.md     # Phase 5: 告警系统
│       ├── 2026-05-16-phase5-stabilization.md    # Phase 5: 稳定性
│       ├── 2026-05-15-phase6-erik-stack.md       # Phase 6: Erik Stack
│       └── 2026-05-18-flutter-desktop.md         # Flutter 桌面实施
├── alipay.png / weixinpay.png              # 打赏二维码
```

## Versões de idioma

Cada documento é fornecido em 12 versões de idioma (`<name>.<lang>.md`), e os diagramas como `<name>.<lang>.svg`:

| Idioma | Código | Idioma | Código |
|------|------|------|------|
| Chinês | zh | Português | pt |
| English | en | हिन्दी | hi |
| 한국어 | ko | العربية | ar |
| Русский | ru | বাংলা | bn |
| Deutsch | de | Bahasa Indonesia | id |
| Francês | fr | Japonês | ja |
| Español | es | | |

## Apoio com doações

| WeChat | Alipay |
|:---:|:---:|
| ![WeChat](weixinpay.png "WeChat") | ![Alipay](alipay.png "Alipay") |

### Doação por transferência global (Global Transfer Donation)

**Informações do beneficiário (Beneficiary)**

| Campo | Valor |
|------|-----|
| Nome do beneficiário (Name) | WANG KEXUN |
| Número da conta do beneficiário (Account No.) | 881015918251 |

**Banco receptor (Receiving Bank) — ZA Bank**

| Campo | Valor |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| Nome do banco (Bank Name) | ZA Bank Limited |
| Código do banco (Bank Code) | 387 |
| Endereço do banco (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **Banco correspondente para remessas transfronteiriças (se necessário, Correspondent Bank)**: estas são informações do banco intermediário (agente), não do banco receptor; consulte o banco remetente para saber se é necessário fornecê-las.
>
> - **Dólar de Hong Kong, RMB e dólar americano**: Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · código do banco 006 · Hong Kong Branch (código da filial 391) · Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **Outras moedas**: THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States

