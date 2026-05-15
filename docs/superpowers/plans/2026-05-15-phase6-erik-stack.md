# Phase 6: Erik Stack Architecture Refactoring

> 全面重构：数据库前缀、ID体系、加密体系、版权、代码规范

## 变更清单

| # | 变更 | 包 | 影响范围 |
|---|------|----|---------|
| 1 | 数据库表前缀 `erik_` | — | 所有 SQL/迁移文件 |
| 2 | 主键 Snowflake ID (无自增) | erikwang2013/snowflake-php | 所有 Model + SQL |
| 3 | API ID hashids 加解密 | erikwang2013/hashids | 所有 Controller 响应 |
| 4 | JWT 认证切换 | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | API 敏感数据加解密 | erikwang2013/encryption | API 请求/响应层 |
| 6 | DB 敏感数据加解密 | erikwang2013/encryptable | Eloquent Model 层 |
| 7 | ES 数据同步/查询 | erikwang2013/webman-scout | 报表搜索 |
| 8 | 国家旗帜 | erikwang2013/season | 前端平台标签 |
| 9 | 版权声明 | — | 所有文件头部 |
| 10 | 移除全局 `\` 前缀 | — | 所有 PHP 文件 |
| 11 | 配置文件加注释 | — | config/*.php |
| 12 | Flutter Web PC 布局 | — | Flutter 项目 |
| 13 | Admin 面板可视化增强 | — | 仪表盘图表 |
| 14 | 面板数据导出 PDF | — | 新增导出格式 |
| 15 | Excel 导出(Client+Admin) | — | 增强导出 |
| 16 | HarmonyOS App | — | 新建鸿蒙项目 |

## 实施顺序

**Batch A: 基础设施（依赖 + ID + 加密）**
- 更新 composer.json 添加 6 个 erikwang2013 包
- 重写所有 SQL 迁移文件（erik_ 前缀 + bigint 无自增）
- 创建 Snowflake ID trait
- 更新所有 Model（使用 SnowflakeTrait）
- 配置 hashids 中间件
- 切换 JWT 到 jwt-webman

**Batch B: 代码清理**
- 移除所有 `\` 全局前缀
- 所有文件添加版权头
- 配置文件加注释

**Batch C: 前端增强**
- Admin 面板可视化增强（更多图表、实时数据）
- 面板数据导出 PDF
- Excel 导出增强

**Batch D: Flutter + HarmonyOS**
- Flutter Web PC 布局项目
- HarmonyOS 项目骨架
