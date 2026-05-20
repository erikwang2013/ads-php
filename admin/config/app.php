<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 管理后台应用配置
 *
 * admin 是一个独立的 webman-admin v2 实例，职责：
 *   - 提供 Vue 3 SPA 的静态文件服务（public/web/）
 *   - 处理管理员认证（JWT + Session 双通道）
 *   - 提供管理员专用 API（用户管理 / RBAC / 审计日志）
 *   - 通过 ServiceProxy 将业务查询转发到 service API（:8788）
 */

return [
    // 调试模式：开启后返回详细错误信息，生产环境关闭
    'debug' => env('APP_DEBUG', false),

    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 业务服务 API 地址
    // admin 中所有的广告数据查询（计划、报表、账户、告警等）均通过
    // ServiceProxy（cURL HTTP 代理）转发到此地址，不在 admin 内直接操作业务表。
    'service_api_url' => env('SERVICE_API_URL', 'http://127.0.0.1:8788/api'),

    // JWT 认证配置（erikwang2013/jwt-webman）
    'jwt' => [
        // 签名密钥，必须与 service 的 JWT_SECRET 不同以保证安全隔离
        'secret' => env('JWT_SECRET', ''),

        // Token 有效期（秒），默认 86400 = 24 小时
        'ttl' => (int) env('JWT_TTL', 86400),
    ],
];
