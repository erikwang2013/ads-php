<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 应用核心配置
 */

return [
    // 调试模式：开启后接口返回详细错误信息，生产环境必须关闭
    'debug' => env('APP_DEBUG', false),

    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // JWT 认证配置（erikwang2013/jwt-webman）
    'jwt' => [
        // 签名密钥，通过 JWT_SECRET 环境变量设置
        'secret' => env('JWT_SECRET', ''),

        // Token 有效期，单位秒（86400 = 24小时）
        'ttl' => (int) env('JWT_TTL', 86400),
    ],
];
