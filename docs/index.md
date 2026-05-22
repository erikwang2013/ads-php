# Docs 目录结构

```
docs/
├── index.md                                # 本文件
├── architecture.md                         # 架构设计文档 (部署/安全/目录/数据模型)
├── features.md                             # 功能设计文档 (20 模块/API/业务流程)
├── api.md                                  # API 接口文档
├── versions.md                             # 三版本对比
├── apidoc-header.md                        # hg/apidoc 通用规范头部
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
