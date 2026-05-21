<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 数据库配置 — 支持读写分离 + 连接池
 *
 * 统一使用 erik_ 表前缀 + BIGINT snowflake 主键。
 * 中小租户共享数据库（tenant_id 隔离），大客户可路由到独立库。
 *
 * 读写分离：
 *   DB_READ_HOST 指向只读副本（或与 DB_HOST 相同）
 *   select() 语句自动路由到只读连接
 */

return [
    'default' => 'shared',

    'connections' => [
        // 读写主库
        'shared' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'ads'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'persistent' => true,
            'timeout'    => (int) env('DB_TIMEOUT', 3),
            'options' => [
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4', time_zone='+00:00'",
                \PDO::ATTR_PERSISTENT => true,
            ],
        ],

        // 只读副本（reporting / analytics 查询）
        'read_replica' => [
            'driver'    => 'mysql',
            'host'      => env('DB_READ_HOST', env('DB_HOST', '127.0.0.1')),
            'port'      => env('DB_READ_PORT', env('DB_PORT', '3306')),
            'database'  => env('DB_DATABASE', 'ads'),
            'username'  => env('DB_READ_USERNAME', env('DB_USERNAME', 'root')),
            'password'  => env('DB_READ_PASSWORD', env('DB_PASSWORD', '')),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'persistent' => true,
            'timeout'    => (int) env('DB_TIMEOUT', 3),
            'options' => [
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4', time_zone='+00:00'",
                \PDO::ATTR_PERSISTENT => true,
            ],
        ],
    ],
];
