<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 全局中间件配置
 *
 * 请求流：Request → CORS → SecurityHeaders → AttackGuard → ClientPlatform → Version → RateLimit → SQLGuard → Validation → ResponseTime → Encryption → Controller
 *
 * 每个中间件职责：
 *   CorsMiddleware            — 跨域请求处理，支持 X-Tenant-Id / X-Encrypted 自定义头
 *   SecurityHeadersMiddleware — 安全响应头（X-Frame-Options, X-Content-Type-Options, HSTS 等）
 *   AttackGuardMiddleware     — XSS/路径遍历/请求头注入/Body大小/Content-Type 检测拦截
 *   ClientPlatformMiddleware  — 操作来源端识别
 *   VersionMiddleware         — API 版本路由（X-API-Version 头）
 *   RateLimitMiddleware       — Redis 滑动窗口限流，默认 60次/60秒
 *   SqlGuardMiddleware        — SQL 注入模式检测（UNION/DROP/ALTER/注释符）
 *   ValidationMiddleware      — 输入裁剪 + HTML 标签过滤
 *   ResponseTimeMiddleware    — 响应时间监控（X-Response-Time 头 + 慢请求日志）
 *   EncryptionMiddleware      — 请求解密 + 响应加密（X-Encrypted 头）
 *
 * 以下中间件在路由层单独注册（非全局）：
 *   AuthMiddleware            — JWT Bearer Token 认证
 *   TenantIdentify            — 多租户解析（X-Tenant-Id 头 / Session）
 */

return [
    'global' => [
        plugin\ads_api\middleware\CorsMiddleware::class,
        plugin\ads_api\middleware\OriginGuardMiddleware::class,
        plugin\ads_api\middleware\SecurityHeadersMiddleware::class,
        plugin\ads_api\middleware\AttackGuardMiddleware::class,
        plugin\ads_api\middleware\ClientPlatformMiddleware::class,
        plugin\ads_api\middleware\ReplayGuardMiddleware::class,
        plugin\ads_api\middleware\VersionMiddleware::class,
        plugin\ads_api\middleware\RateLimitMiddleware::class,
        plugin\ads_api\middleware\LoginThrottleMiddleware::class,
        plugin\ads_api\middleware\SessionLimitMiddleware::class,
        plugin\ads_api\middleware\SqlGuardMiddleware::class,
        plugin\ads_api\middleware\ValidationMiddleware::class,
        plugin\ads_api\middleware\ResponseTimeMiddleware::class,
        plugin\ads_api\middleware\EncryptionMiddleware::class,
    ],
];
