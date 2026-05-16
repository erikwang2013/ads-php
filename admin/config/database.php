<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 管理后台数据库配置
 *
 * 存储 admin 专用表：admin_users / admin_roles / admin_audit_logs。
 *
 * 业务数据（erik_campaigns、erik_report_metrics 等）存储在同一个 MySQL 实例中，
 * 但 admin 不直接操作业务表——所有业务查询通过 service API（:8788）完成。
 * 两者通过表命名前缀区分：业务表 erik_* ，管理表 admin_*。
 */

return [
    // 默认连接名
    'default' => 'admin',

    'connections' => [
        'admin' => [
            // PDO 驱动：mysql / pgsql / sqlite / sqlsrv
            'driver'    => 'mysql',

            // 数据库主机地址
            'host'      => env('DB_HOST', '127.0.0.1'),

            // 数据库端口
            'port'      => env('DB_PORT', '3306'),

            // 数据库名——与 service 共享同一个库以简化运维
            'database'  => env('DB_DATABASE', 'ads'),

            // 认证凭据
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),

            // 字符集与排序规则
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',

            // 表前缀：留空，各表自行以 admin_ 命名（admin_users, admin_roles 等）
            'prefix'    => '',
        ],
    ],
];
