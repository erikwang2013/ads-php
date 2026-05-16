<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 管理后台 Redis 配置
 *
 * 用途：
 *   - Session 存储（webman-admin 内置）
 *   - 限流计数器（RateLimitMiddleware）
 *   - 可选的 API 响应缓存
 *
 * 使用数据库 1，与 service（数据库 0）隔离开，避免 key 冲突。
 */

return [
    'default' => [
        // Redis 服务器地址
        'host'     => env('REDIS_HOST', '127.0.0.1'),

        // Redis 端口
        'port'     => env('REDIS_PORT', '6379'),

        // 认证密码（留空表示无密码）
        'password' => env('REDIS_PASSWORD', ''),

        // 数据库编号：设为 1 与 service（0）隔离
        'database' => 1,
    ],
];
