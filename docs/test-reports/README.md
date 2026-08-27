# 测试报告总览

- 日期：2026-08-27
- 团队：PHP 单元测试 · API 自动化测试 · UI 端到端测试

## 总览

| 套件 | 用例 | 断言 | 结果 | 报告 |
|------|------|------|------|------|
| PHP 单元测试（7 插件 + 中间件） | 143 | 333 | ✅ 全部通过 | [php-unit.md](php-unit.md) |
| API 自动化测试（66 路由） | 101 | 321 | ✅ 通过，1 skipped | [api.md](api.md) |
| UI 端到端（admin 18 页面） | 18 | — | ✅ 全部通过 | [ui-e2e.md](ui-e2e.md) |
| Go 模块 | — | — | N/A（无 Go 代码） | [go.md](go.md) |
| Rust 模块 | — | — | N/A（无 Rust 代码） | [rust.md](rust.md) |

**合计：244 PHP tests / 654 assertions + 18 UI E2E 用例，全部通过**

## SQL 合并结论

- 应用 SQL 仅有根目录 `install.sql`（28 张表），**无其他应用 SQL 文件**，无需合并。
- `.swarm/schema.sql` 为 RuFlo 工具自身的 schema（非应用代码），保留未删。
- 与请求核对：Go/Rust 代码不存在，Go/Rust 测试团队跳过（报告见上，N/A）。

## Bug 汇总

**已修复（8）**：AuthToken 缺 timestamps · PlatformController 缺 `use Throwable` · 3 个 store 控制器 insertGetId → 显式 ID · `snowflake_id()` 未定义 · AccountBind.vue `platforms` 赋值崩溃 · ui-e2e mock 前缀匹配 bug · **ValidationMiddleware `$request->set()` → setGet/setPost** · **EncryptionMiddleware 改用 `EncryptionManagerFactory::fromMasterKey()`（含 32 字节密钥派生）** · **CaptchaService 改用真实 Poster 包 API（GD + Storage，验证码接口恢复可用）**

**未修复（2，详见各报告）**：

| 级别 | Bug | 位置 |
|------|-----|------|
| 高 | TenantIdentify `$request->sessionGet()` 不存在（暂未注册） | plugin/ads-tenant |
| 中 | TokenRefreshTask 加密空串匹配失效 | plugin/ads-task |

**最终状态：244 tests / 660 assertions 全绿，0 skipped**（修复后重新验证）。
