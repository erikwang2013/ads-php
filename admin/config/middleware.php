<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 管理后台全局中间件配置
 *
 * 请求流：Request → CORS → SecurityHeaders → AttackGuard → ClientPlatform
 *           → RateLimit → LoginThrottle → SQLGuard → Validation → CSRF → Controller
 *
 * 每个中间件职责：
 *   CorsMiddleware            — 跨域请求处理（debug 模式放行所有，生产白名单）
 *   SecurityHeadersMiddleware — 安全响应头（X-Frame-Options, X-Content-Type-Options, HSTS 等）
 *   AttackGuardMiddleware     — XSS/路径遍历/请求头注入/Body大小/Content-Type 检测拦截
 *   ClientPlatformMiddleware  — 操作来源端识别
 *   RateLimitMiddleware       — Redis 滑动窗口限流，默认 60次/60秒
 *   LoginThrottleMiddleware   — 登录爆破保护，5次失败锁15分钟
 *   SqlGuardMiddleware        — SQL 注入模式检测（UNION/DROP/ALTER/注释符）
 *   ValidationMiddleware      — 输入裁剪 + HTML 标签过滤
 *   CsrfMiddleware            — CSRF Token 校验（安装和登录接口豁免）
 *
 * 以下中间件在路由层单独注册（非全局）：
 *   AuthCheck                 — JWT Bearer Token + Session 认证
 */

return [
    'global' => [
        admin\middleware\CorsMiddleware::class,
        admin\middleware\SecurityHeadersMiddleware::class,
        admin\middleware\AttackGuardMiddleware::class,
        admin\middleware\ClientPlatformMiddleware::class,
        admin\middleware\RateLimitMiddleware::class,
        admin\middleware\LoginThrottleMiddleware::class,
        admin\middleware\SqlGuardMiddleware::class,
        admin\middleware\ValidationMiddleware::class,
        admin\middleware\CsrfMiddleware::class,
    ],
];
