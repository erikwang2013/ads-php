# Docs 디렉터리 구조

[中文](docs/index.md) | [English](docs/index.en.md) | [한국어](docs/index.ko.md) | [Русский](docs/index.ru.md) | [Deutsch](docs/index.de.md) | [Français](docs/index.fr.md) | [Español](docs/index.es.md) | [Português](docs/index.pt.md) | [हिन्दी](docs/index.hi.md) | [العربية](docs/index.ar.md) | [বাংলা](docs/index.bn.md) | [Bahasa Indonesia](docs/index.id.md) | [日本語](docs/index.ja.md)

```
docs/
├── index.md                                # 本文件
├── architecture.md                         # 架构设计文档 (部署/安全/目录/数据模型)
├── features.md                             # 功能设计文档 (21 模块/业务流程)
├── api.md                                  # API 接口文档 (接口定义已统一移至此)
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

## 언어 버전

모든 문서는 12개 언어 버전(`<name>.<lang>.md`)을 제공하며, 다이어그램은 `<name>.<lang>.svg` 형식입니다:

| 언어 | 코드 | 언어 | 코드 |
|------|------|------|------|
| 中文 | zh | Português | pt |
| English | en | हिन्दी | hi |
| 한국어 | ko | العربية | ar |
| Русский | ru | বাংলা | bn |
| Deutsch | de | Bahasa Indonesia | id |
| Français | fr | 日本語 | ja |
| Español | es | | |

## 후원 지원

| WeChat | Alipay |
|:---:|:---:|
| ![微信](weixinpay.png "微信") | ![支付宝](alipay.png "支付宝") |

### 글로벌 송금 후원 (Global Transfer Donation)

**수취인 정보 (Beneficiary)**

| 필드 | 값 |
|------|-----|
| 收款人姓名 (Name) | WANG KEXUN |
| 收款账户号码 (Account No.) | 881015918251 |

**수취 은행 (Receiving Bank) — ZA Bank**

| 필드 | 값 |
|------|-----|
| SWIFT Code | AABLHKHHXXX |
| 银行名称 (Bank Name) | ZA Bank Limited |
| 银行编号 (Bank Code) | 387 |
| 银行地址 (Bank Address) | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **해외송금 중계 은행 (필요 시, Correspondent Bank)**：이는 중계(거쳐가는) 은행 정보로, 수취 은행 정보가 아닙니다. 송금 은행에 필요 여부를 문의하세요.
>
> - **홍콩 달러, 위안화 및 미국 달러**: Citibank N.A. Hong Kong — SWIFT `CITIHKHXXXX` · 银行编号 006 · Hong Kong Branch（分行编号 391）· Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - **기타 통화**: THE BANK OF NEW YORK MELLON — SWIFT `IRVTUS3NXXX` · 240 GREENWICH STREET, NEW YORK, United States
