# 多语言翻译规范（Agent 必读）

本文件为翻译 Agent 的操作规范。目标：为 ads-php 项目全部文档和图表生成 12 种语言版本。

## 语言与文件名规则

| 语言 | 代码 | 文件名后缀 |
|------|------|-----------|
| 中文 | zh | （源文件，不翻译） |
| English | en | `<name>.en.md` |
| 한국어 | ko | `<name>.ko.md` |
| Русский | ru | `<name>.ru.md` |
| Deutsch | de | `<name>.de.md` |
| Français | fr | `<name>.fr.md` |
| Español | es | `<name>.es.md` |
| Português | pt | `<name>.pt.md` |
| हिन्दी | hi | `<name>.hi.md` |
| العربية | ar | `<name>.ar.md` |
| বাংলা | bn | `<name>.bn.md` |
| Bahasa Indonesia | id | `<name>.id.md` |
| 日本語 | ja | `<name>.ja.md` |

新文件与源文件同目录。例如 `docs/features.md` → `docs/features.en.md`、`docs/features.ko.md` … `README.md` → `README.en.md`（已存在，覆盖/修复）。

## 翻译规则

1. **标题后语言切换行**：每份文档标题（第一个 `#` 行）之后必须插入 12 语言切换行（zh 链接指向源文件）：

   ```
   [中文](README.md) | [English](docs/README.en.md) | [한국어](docs/README.ko.md) | [Русский](docs/README.ru.md) | [Deutsch](docs/README.de.md) | [Français](docs/README.fr.md) | [Español](docs/README.es.md) | [Português](docs/README.pt.md) | [हिन्दी](docs/README.hi.md) | [العربية](docs/README.ar.md) | [বাংলা](docs/README.bn.md) | [Bahasa Indonesia](docs/README.id.md) | [日本語](docs/README.ja.md)
   ```

   链接文件名跟随所在目录的文档名。docs 内文档用 `docs/xxx` 相对链接前缀。

2. **不翻译的内容**（原样保留）：
   - 代码块（``` 包裹的内容，含 JSON、SQL、bash、mermaid 源码）
   - 专有名词：SWIFT Code、ZA Bank、Citibank、BNY Mellon、银行编号、分行编号、收款人姓名（WANG KEXUN）、账号、银行地址、Webman、Redis、JWT、Erik Stack、webman-admin、Flutter、HarmonyOS、ECharts、Element Plus、Vue、Pinia
   - 银行转账信息（docs/index.md 和 README 中的打赏章节）逐字保留，仅翻译段落性说明文字
   - Copyright 行 `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz`
   - 文件路径、表名（ads_ 前缀）、字段名、API 路由、URL、端口号
   - Mermaid 图表中的节点标签可翻译，但语法结构不能变
   - docs/index.md 中的目录树（``` 代码块）不翻译

3. **链接重写**：
   - `.md` 内部链接 → 加语言后缀：`api.md#模块-2-认证` → `api.en.md#模块-2-认证`（锚点保持原文，不翻译；无锚点则只加后缀）
   - `.svg` 图片链接 → 加语言后缀：`architecture.svg` → `architecture.en.svg`
   - `.png` 链接（alipay.png / weixinpay.png）→ 不变
   - 外链（http/https、mailto）→ 不变
   - 语言切换行中指向其他语言版本的链接 → 加对应语言后缀
   - 找不到对应文件时不强求，保持原链接

4. **格式保持**：表格结构、标题层级、列表缩进、空行全部保持。翻译后行数允许不同，但结构完整。

5. **质量要求**：
   - 忠实原文，不增删段落
   - 术语一致性（同一词全文同一译法）
   - 阿拉伯语为 RTL 语言，正常书写即可，不需要额外标记
   - 完成后自查：所有 `.md`/`.svg` 内部链接已加语言后缀，切换行 12 种语言齐全

## 文档清单（34 个源文档）

- `README.md`（根目录，`README.en.md` 已存在）
- `docs/index.md`、`docs/architecture.md`、`docs/features.md`、`docs/api.md`、`docs/versions.md`、`docs/install-review.md`
- `docs/skills/`（11 个）：adapter-generator、admin-page-generator、api-endpoint、erik-stack、migration-generator、tdd-workflow、security-middleware、version-split、cache-strategy、attribution-setup、high-concurrency
- `docs/superpowers/specs/`（2 个）：design.md、2026-05-18-flutter-desktop-design.md
- `docs/superpowers/plans/`（12 个）：2026-05-14-phase1-foundation、2026-05-15-phase2-adapters-reports、2026-05-15-phase3-more-adapters、2026-05-15-phase4-17-adapters、2026-05-15-phase5-alert-system、2026-05-16-phase5-stabilization、2026-05-15-phase6-erik-stack、2026-05-18-flutter-desktop、以及其余同目录 .md
- `docs/test-reports/go.md`、`docs/test-reports/rust.md`

## 图片（5 个 mermaid 图，另行处理）

`docs/diagrams/` 下 5 个 `.mmd`（architecture、data-lifecycle、functional-modules、request-flow、security）由主 Agent 翻译并用 render.py 渲染为 `<name>.<lang>.svg`，翻译 Agent 不需要处理。

## 操作流程

1. 阅读本规范
2. 按文档清单逐文件翻译（建议按目录顺序）
3. 每完成一个文件自查链接重写
4. 汇报完成清单

## 完成标准

- 34 个源文档 × 12 语言 = 408 个翻译文件（README.en.md 已存在需检查修复）
- 每个翻译文件含 12 语言切换行
- 所有内部 `.md`/`.svg` 链接带语言后缀
- 银行信息、代码块、专有名词原样保留
