<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 管理后台全局中间件配置
 *
 * 请求流：Request → global middleware → route middleware → Controller
 *
 * 当前 admin 依赖 webman-admin 插件内置的 session/auth 处理。
 * 认证中间件 AuthCheck 在路由层单独注册（见 route.php），非全局生效。
 *
 * 随着管理后台功能扩展，可在此添加：
 *   - 跨域中间件（CORS）
 *   - 限流中间件（RateLimit）
 *   - 操作审计中间件（AuditLog）
 */

return [
    'global' => [
        // 在此添加全局中间件类名，例如：
        // admin\middleware\CorsMiddleware::class,
        // admin\middleware\RateLimitMiddleware::class,
    ],
];
