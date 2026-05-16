<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Redis 配置
 *
 * 用途：仪表盘缓存、限流计数器、消息队列、告警 Pub/Sub、Session 存储
 */

return [
    'default' => [
        // Redis 服务器地址
        'host'     => env('REDIS_HOST', '127.0.0.1'),

        // Redis 端口
        'port'     => env('REDIS_PORT', '6379'),

        // 认证密码（留空表示无密码）
        'password' => env('REDIS_PASSWORD', ''),

        // Redis 数据库编号（0-15），不同业务可隔离到不同编号
        'database' => 0,
    ],
];
