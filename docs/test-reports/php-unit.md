# PHP 单元测试报告

- 日期：2026-08-29
- 执行：`cd service && ECC_GATEGUARD=off vendor/bin/phpunit`
- 环境：SQLite 内存库（`SqliteTestCase` 基类）+ Spy Adapter 桩，不依赖真实 MySQL / 外部广告平台

## 结果

**全套件：288 tests / 862 assertions**（含 API 集成测试）

## 模块覆盖（8 插件 + 中间件，新增文件位于 `service/tests/Unit/`）

| 插件 | 用例数 | 覆盖内容 |
|------|--------|----------|
| ads-api | 54 | 14 个中间件（AttackGuard/Cors/ReplayGuard/OriginGuard/Validation/ClientPlatform/Auth/RateLimit/LoginThrottle/SessionLimit/SqlGuard/SecurityHeaders/Version/ResponseTime）+ VersionedRouteHelper |
| ads-report | 32 | AttributionEngine、ReportExporter、ConversionService、QuotaService、ReportBuilder |
| ads-alert | 20 | NotificationServiceChannel、AlertEngine、BudgetAlertService |
| ads-platform | 20 | BidEngine、FieldMapping、HashidsService、CampaignData |
| ads-account | 10 | OAuthService、AdapterRegistry |
| ads-task | 7 | TokenRefreshTask、DataSyncTask |
| ads-storage | 21 | Storage 门面/驱动 (local/oss/cos/s3)、预签名 URL 生成、CDN purge 签名、CDN 服务商 API（CdnProviderApiTest 14 + AssetStorageTest 6 + AlibabaCdnSignatureTest 1） |
| 熔断机制 | 21 | CircuitBreaker 状态机（阈值/冷却/半开/恢复）、GuardedAdapter 代理（透传/快失败/计数/Generator） |

## 发现的 Bug

已由 backend-dev 修复（2026-08-27）：ValidationMiddleware `$request->set()` → setGet/setPost · EncryptionMiddleware 改用 `EncryptionManagerFactory::fromMasterKey()` · CaptchaService 改用真实 Poster 包 API。

**仍未修复**：

| 级别 | Bug | 影响 |
|------|-----|------|
| 高 | `TenantIdentify` 调用不存在的 `$request->sessionGet()`（未注册，暂休眠） | 启用后租户识别崩溃 |
| 中 | TokenRefreshTask `where('refresh_token','!=','')` 受 Encryptable cast 加密空串影响，匹配失效 | 空 refresh_token 行未被清理 |
| 低 | AttackGuard 不拦截 `%2e%2e` 编码路径穿越（webman 路由不解码，直接 404） | 无实际穿透 |
