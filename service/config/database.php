<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 数据库配置
 *
 * 统一使用 erik_ 表前缀 + BIGINT snowflake 主键。
 * 中小租户共享数据库（tenant_id 隔离），大客户可路由到独立库。
 */

return [
    // 默认连接名
    'default' => 'shared',

    'connections' => [
        'shared' => [
            // PDO 驱动：mysql / pgsql / sqlite / sqlsrv
            'driver'    => 'mysql',

            // 数据库主机地址，通过 DB_HOST 环境变量配置
            'host'      => env('DB_HOST', '127.0.0.1'),

            // 数据库端口
            'port'      => env('DB_PORT', '3306'),

            // 数据库名称
            'database'  => env('DB_DATABASE', 'ads'),

            // 认证凭据
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),

            // 字符集与排序规则，utf8mb4 支持 emoji 等四字节字符
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',

            // 表前缀（留空表示无前缀，各表自行使用 erik_ 命名）
            'prefix'    => '',

            // 持久连接：高并发场景下避免每次请求都重新握手
            'persistent' => env('DB_PERSISTENT', false),

            // 连接超时（秒）：设短一些，查询失败快速返回而不是堆积
            'timeout'    => (int) env('DB_TIMEOUT', 3),

            // PDO 性能调优选项
            'options' => [
                // 读取时缓冲完整结果集，减少网络往返
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,

                // 每次建连时执行的初始化命令（字符集、时区等）
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4', time_zone='+00:00'",

                // 不启用PDO语句缓存——webman 的每个请求独立 worker 模型下，
                // 语句缓存反而增加内存开销，实测不如让 MySQL 自己处理。
            ],
        ],
    ],
];
