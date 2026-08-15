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
 *   - 广告数据查询由 SPA 直连 service API（:8788），ServiceProxy 为预留基础设施（暂未接线）
 */

return [
    // 调试模式：开启后返回详细错误信息，生产环境关闭
    'debug' => env('APP_DEBUG', false),

    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 业务服务 API 地址
    // 当前架构：admin 的广告数据查询（计划、报表、账户、告警等）由 Vue SPA
    // 经 axios（baseURL /api，Nginx 分流到 :8788）直连 service；
    // ServiceProxy（cURL HTTP 代理）为预留基础设施，当前无活跃调用方。
    'service_api_url' => env('SERVICE_API_URL', 'http://127.0.0.1:8788/api'),

    // JWT 认证配置（erikwang2013/jwt-webman）
    'jwt' => [
        // 签名密钥，必须与 service 的 JWT_SECRET 不同以保证安全隔离
        'secret' => env('JWT_SECRET', ''),

        // Token 有效期（秒），默认 86400 = 24 小时
        'ttl' => (int) env('JWT_TTL', 86400),
    ],
];
