# 测试报告总览

- 日期：2026-08-27
- 团队：PHP 单元测试 · API 自动化测试 · UI 端到端测试

## 总览

| 套件 | 用例 | 断言 | 结果 | 报告 |
|------|------|------|------|------|
| PHP 单元测试（7 插件 + 中间件 + 熔断） | 164 | 390 | ✅ 全部通过 | [php-unit.md](php-unit.md) |
| API 自动化测试（66 路由） | 101 | 327 | ✅ 全部通过 | [api.md](api.md) |
| UI 端到端（admin 18 页面） | 18 | — | ✅ 全部通过 | [ui-e2e.md](ui-e2e.md) |
| Go 模块 | — | — | N/A（无 Go 代码） | [go.md](go.md) |
| Rust 模块 | — | — | N/A（无 Rust 代码） | [rust.md](rust.md) |

**合计：265 PHP tests / 717 assertions + 18 UI E2E 用例，全部通过**

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

**最终状态：265 tests / 717 assertions 全绿，0 skipped**（修复后重新验证）。

## 熔断/降级/超时机制（2026-08-27 新增）

- **CircuitBreaker**（`plugin/ads-platform/src/CircuitBreaker.php`）：per-platform 状态机 CLOSED→OPEN（连续 5 次失败）→HALF_OPEN（冷却 30s 探活）→成功恢复 / 再失败回 OPEN；单节点静态内存存储（多节点需换 Redis）
- **GuardedAdapter**（`src/GuardedAdapter.php`）：`AdapterRegistry::get()` 返回代理，14 个调用点零改动；open 时抛 `CircuitBreakerOpenException` fast-fail，任务层现有 catch 吸收 = 逐平台降级跳过；Generator 方法（fetch*）迭代完成记 success / 中断记 failure
- **超时**：核实现有 29 个适配器均含 `CURLOPT_TIMEOUT`（30/60s）+ `CURLOPT_CONNECTTIMEOUT`（10s），无需改动
- 测试：CircuitBreakerTest 8 例 + GuardedAdapterTest 13 例（含审查补盲区 6 例：OPEN 反复调用全拦截、Generator 提前抛、元数据直通不重置计数等）
