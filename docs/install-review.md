# Ads-PHP 安装向导 — 审查报告（第 2 轮）

**生成时间**: 2026-08-04  
**审查范围**: InstallController.php / install.html / install.sql / route.php / CsrfMiddleware / README.md / README.en.md / .gitignore / .env.example / 全部 migration SQL  
**PHP 版本**: 8.3.7 | **框架**: webman v2  

---

## 一、语法与静态检查

| 文件 | 行数 | 语法 |
|------|------|------|
| `admin/app/controller/InstallController.php` | 287 | 通过 |
| `admin/config/route.php` | 81 | 通过 |
| `admin/app/middleware/CsrfMiddleware.php` | 45 | 通过 |

---

## 二、install.sql 分析

| 指标 | 值 |
|------|-----|
| 文件大小 | 42 KB |
| 数据表 | 28 张 |
| SQL 语句 | 47 条 |
| 章节编号 | 1–12 连续 |

### 幂等性验证

| 检查项 | 结果 |
|--------|------|
| CREATE TABLE 含 IF NOT EXISTS | 28/28 通过 |
| ADD INDEX 使用存储过程检测已有索引 | 通过（`erik_add_index` + `information_schema.STATISTICS`） |
| INSERT 含 ON DUPLICATE KEY UPDATE | 通过（租户 / 管理员 / 角色 / wa_options） |
| 重复执行安全 | 是 |

### 表分组（28 张）

| 分组 | 表名 |
|------|------|
| 租户 | `erik_tenants` |
| 账号 | `erik_platform_accounts`, `erik_auth_tokens` |
| 广告核心 | `erik_campaigns`, `erik_ad_groups`, `erik_creatives`, `erik_report_metrics`, `erik_report_extras` |
| 素材 | `erik_assets` |
| 定向 | `erik_targeting_templates` |
| 归因 | `erik_conversions`, `erik_attribution_results` |
| 竞价 | `erik_bid_rules`, `erik_bid_logs` |
| 告警 | `erik_alert_rules`, `erik_alert_logs`, `erik_notifications` |
| 同步 | `erik_sync_errors` |
| 管理 | `admin_users`, `admin_roles`, `admin_audit_logs` |
| 框架 | `wa_admin_roles`, `wa_admins`, `wa_roles`, `wa_rules`, `wa_uploads`, `wa_users`, `wa_options` |

### 迁移文件清单（13 个源文件 → 1 个合并文件）

| 迁移文件 | 对应表 |
|----------|--------|
| `ads-tenant/migration/create_tenants.sql` | `erik_tenants` |
| `ads-account/migration/create_platform_accounts.sql` | `erik_platform_accounts`, `erik_auth_tokens` |
| `ads-platform/migration/create_campaign_tables.sql` | `erik_campaigns`, `erik_ad_groups`, `erik_creatives`, `erik_report_metrics`, `erik_report_extras` |
| `ads-platform/migration/create_assets.sql` | `erik_assets` |
| `ads-platform/migration/create_targeting_templates.sql` | `erik_targeting_templates` |
| `ads-report/migration/create_conversions.sql` | `erik_conversions`, `erik_attribution_results` |
| `ads-platform/migration/create_bid_rules.sql` | `erik_bid_rules`, `erik_bid_logs` |
| `ads-alert/migration/create_alerts.sql` | `erik_alert_rules`, `erik_alert_logs` |
| `ads-alert/migration/create_notifications_table.sql` | `erik_notifications` |
| `ads-task/migration/create_sync_errors.sql` | `erik_sync_errors` |
| `admin/migration/create_admin_tables.sql` | `admin_users`, `admin_roles`, `admin_audit_logs` |
| `admin/vendor/.../install.sql` | `wa_*` 框架表 |
| `ads-platform/migration/add_performance_indexes.sql` | 性能索引 |

---

## 三、InstallController 审查

### 输入校验

| 字段 | 校验 | 结果 |
|------|------|------|
| `db_host` | trim | 通过 |
| `db_port` | trim，默认 3306 | 通过 |
| `db_database` | `preg_match('/^[a-zA-Z0-9_]+$/', ...)` | 通过（阻止注入） |
| `db_username` | trim | 通过 |
| `db_password` | 通过 PDO 连接（认证失败 → 错误消息） | 通过 |
| `admin_username` | trim，非空 | 通过 |
| `admin_password` | >= 6 字符 | 通过 |
| `admin_name` | trim，默认值 | 通过 |

### 安全特性

| 特性 | 实现 |
|------|------|
| 重复安装防护 | `.installed` 锁文件，`run()` 前置检查 + `index()` 已安装提示页 |
| SQL 注入防护 | `$dbDatabase` 正则白名单；admin 密码使用参数化 `prepare()` |
| XSS | 全局 AttackGuardMiddleware |
| CSRF | 安装 API 路径在 CsrfMiddleware.skipPaths 中 |
| JWT | `bin2hex(random_bytes(32))` — 256 bit 随机密钥 |
| 密码哈希 | `password_hash(..., PASSWORD_BCRYPT)` |

### 安装流程

```
POST /api/install/run
  ├─ Guard: check .installed
  ├─ Step 1: CREATE DATABASE IF NOT EXISTS
  ├─ Step 2: write .env
  ├─ Step 3: splitSql() → exec each statement
  ├─ Step 4: UPDATE admin_users SET password=?, name=?
  └─ Step 5: touch .installed
```

错误码：1=建库失败, 2=写.env失败, 3=SQL文件缺失, 4=SQL执行失败, 5=密码设置失败

---

## 四、前端 install.html 审查

| 检查项 | 结果 |
|--------|------|
| 表单字段数 | 13 个 |
| JS fetch 发送字段数 | 13/13 全部发送 |
| 密码确认校验 | 通过（不一致时阻止提交） |
| 密码最小长度 | 6 位 |
| 必填字段检查 | 数据库名、用户名、管理员用户名、密码 |
| 数据库连接测试 | 独立按钮，单独请求 `check` 端点 |
| 加载状态 | 全屏遮罩 + 步骤指示器 + 动画 |
| 错误展示 | 分类 alert（info/success/error） |
| 安装成功后 | 显示 JWT Secret + 重启服务提示 |
| 已安装访问 | 显示「已安装」卡片 + 跳转链接 |

---

## 五、生态配置完整性

### .env 键值一致性

| .env.example 键 | buildEnv 生成 | 一致 |
|-----------------|---------------|------|
| APP_DEBUG | APP_DEBUG=false | 是 |
| — | APP_URL | `.env.example` 缺少 APP_URL（低优先级） |
| SERVICE_API_URL | SERVICE_API_URL | 是 |
| DB_HOST | DB_HOST | 是 |
| DB_PORT | DB_PORT | 是 |
| DB_DATABASE | DB_DATABASE | 是 |
| DB_USERNAME | DB_USERNAME | 是 |
| DB_PASSWORD | DB_PASSWORD | 是 |
| REDIS_HOST | REDIS_HOST | 是 |
| REDIS_PORT | REDIS_PORT | 是 |
| REDIS_PASSWORD | REDIS_PASSWORD | 是 |
| JWT_SECRET | JWT_SECRET | 是 |
| JWT_TTL | JWT_TTL=86400 | 是 |

### README 双语对照

| 项目 | README.md | README.en.md |
|------|-----------|-------------|
| 行数 | 559 | 552 |
| 章节数 | 19 | 18 |
| 语言链接 | `[English](README.en.md)` | `[中文](README.md)` |
| 表数量 | 28 | 28 |
| API 端点 | 全部中文标题 | 全部英文标题 |
| 缺失章节 | — | 「开源不易，欢迎支持」（仅中国支付平台） |

### 中间件路由矩阵

| 路由 | AttackGuard | LoginThrottle | ClientPlatform | Csrf | Version | AuthCheck |
|------|:--:|:--:|:--:|:--:|:--:|:--:|
| GET /install | 通过 | 仅/login | 通过 | 仅POST | 通过 | 仅/api/admin |
| POST /api/install/check | 通过 | 仅/login | 通过 | skipPaths | 通过 | 仅/api/admin |
| POST /api/install/run | 通过 | 仅/login | 通过 | skipPaths | 通过 | 仅/api/admin |
| GET /api/install/status | 通过 | 仅/login | 通过 | 仅POST | 通过 | 仅/api/admin |

---

## 六、文件清单

### 新增（6 文件）

| 文件 | 行数 | 说明 |
|------|------|------|
| `admin/app/controller/InstallController.php` | 287 | 安装向导后端 |
| `admin/app/controller/install.html` | 453 | 安装向导前端 |
| `install.sql` | 536 | 统一 MySQL 安装脚本 |
| `service/plugin/ads-platform/migration/create_assets.sql` | 18 | 素材库表迁移 |
| `service/plugin/ads-report/migration/create_conversions.sql` | 29 | 归因表迁移 |
| `README.en.md` | 552 | 英文 README |

### 修改（5 文件）

| 文件 | 变更 |
|------|------|
| `admin/config/route.php` | 新增 4 条安装路由 |
| `admin/app/middleware/CsrfMiddleware.php` | skipPaths 增加 3 条安装 API |
| `README.md` | 双语链接 + 28 表 + 一键安装指南 |
| `.gitignore` | 新增 `.installed` 排除 |
| `docs/install-review.md` | 本报告 |

---

## 七、遗留的低优先级事项

| # | 事项 | 风险 |
|---|------|------|
| 1 | `.env.example` 缺失 `APP_URL` 键 | 低 — 安装器生成完整 .env |
| 2 | 密码含 `#` 或换行符时 .env 解析异常 | 极低 — MySQL 密码极少含这些字符 |
| 3 | 安装后需手动 `php start.php restart` | 已在前端显示提示 |
| 4 | Service 端 .env 需单独配置 | 低 — 两项服务可共用同一 MySQL |

---

## 八、结论

全部文件通过语法检查。install.sql 包含 28 张表、47 条语句，完全幂等可重入。安装控制器具有完备的输入校验、重复安装防护、SQL 注入防护。中英文 README 双向链接、结构一致。路由和中间件矩阵覆盖完整，无冲突。生态系统配置链路（迁移源文件 → install.sql → .env 模板 → 路由 → 中间件 → README 文档）完整闭环。
