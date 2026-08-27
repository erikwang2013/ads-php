# API 自动化测试报告

- 日期：2026-08-27
- 执行：`cd service && ECC_GATEGUARD=off DB_PASSWORD='' vendor/bin/phpunit`
- 基类：`service/tests/Integration/ApiTestCase.php`（mock 适配器 + 种子数据）

## 结果

**265 tests / 717 assertions 全部通过，0 skipped**（Integration 101 tests / 327 assertions，Unit 164 tests / 390 assertions）

## 端点覆盖（66 条路由，认证 + 公开端点全覆盖）

| 模块 | 用例数 | 覆盖内容 |
|------|--------|----------|
| 认证 | 12 | 登录 / me / refresh / 中间件链 |
| 平台账户 | 11 | 列表 / OAuth URL / callback 非法 state / 同步 |
| 计划/广告组/创意 | 17 | CRUD / 批量启停 / 过滤器 |
| 告警 | 12 | 规则 CRUD + 7 种校验 / 确认 / 未读 |
| 出价规则 + 定向模板 | 11 | CRUD / 启停 |
| 报表 | 9 | 汇总 / 自定义 / 归因 / 日历 / 预算告警 / 导出 |
| 通知 + 素材 | 13 | CRUD / 推送记录 |
| 同步状态/错误 + 转化回传 + 租户配额 | 10 | 状态 / 错误列表 / 回传 / 配额 |
| 健康 / 文档 / 验证码 | 6 | health / docs / captcha |

测试文件（`service/tests/Integration/`）：ApiTestCase、AuthApiTest、PlatformAccountApiTest、CampaignApiTest、AlertApiTest、BidRuleTemplateApiTest、ReportApiTest、NotificationAssetApiTest、SyncConversionTenantApiTest、HealthDocApiTest

## 跳过用例

无（captcha verify 用例随 bug 修复取消跳过，已通过）。

## 已修复的 Bug（7 个，本次会话）

1. `AuthToken` 模型缺 `$timestamps` 定义
2. `PlatformController` 缺 `use Throwable` 导入
3. 3 个 store 控制器在无 AUTO_INCREMENT 表上用 `insertGetId`（改用显式 ID）
4. `snowflake_id()` 未定义，导致归因 / 预算任务崩溃（`service/support/helpers.php` 补齐）
5. **ValidationMiddleware** `$request->set()` → `setGet()`/`setPost()`（全局中间件，带参请求 500 问题）
6. **EncryptionMiddleware** 改用 `EncryptionManagerFactory::fromMasterKey()`（32 字节主密钥派生）+ 按 workerman parsePost 规则解密写回
7. **CaptchaService** 改用真实 Poster 包 API（`CaptchaManager` + GD 驱动 + Storage），验证码接口恢复可用；verify 容差内通过 / 超容差拒绝 / 成功后 key 删除防重放

## 未修复的 Bug

| 级别 | Bug | 建议 |
|------|-----|------|
| 高 | TenantIdentify `$request->sessionGet()` 不存在（暂未注册） | 统一会话读取方式 |
| 中 | TokenRefreshTask 加密空串匹配失效 | 改用 `isNull` / 解密后比较 |
