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
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => env('REDIS_PORT', '6379'),
        'password' => env('REDIS_PASSWORD', ''),
        'database' => 0,
        'persistent' => true,
        'read_write_timeout' => 3,
        'connection_timeout' => 3,
        'retry_interval' => 100,
    ],
    // 读写分离（哨兵模式下启用）
    'readonly' => [
        'host'     => env('REDIS_READ_HOST', env('REDIS_HOST', '127.0.0.1')),
        'port'     => env('REDIS_READ_PORT', env('REDIS_PORT', '6379')),
        'password' => env('REDIS_READ_PASSWORD', env('REDIS_PASSWORD', '')),
        'database' => env('REDIS_READ_DB', 0),
        'persistent' => true,
    ],
];
