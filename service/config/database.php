<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

return [
    // Default database connection name
    'default' => 'shared',

    // Database connection definitions
    'connections' => [
        'shared' => [
            // PDO driver — mysql, pgsql, sqlite, sqlsrv
            'driver'    => 'mysql',

            // Connection host (set via DB_HOST env var, defaults to localhost)
            'host'      => env('DB_HOST', '127.0.0.1'),

            // Connection port
            'port'      => env('DB_PORT', '3306'),

            // Database name
            'database'  => env('DB_DATABASE', 'ads'),

            // Authentication credentials
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),

            // Character set and collation for UTF-8 support (including emoji)
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',

            // Table prefix (empty = no prefix)
            'prefix'    => '',

            // Connection pooling: enable persistent connections to avoid
            // per-request handshake overhead under high concurrency.
            'persistent' => env('DB_PERSISTENT', false),

            // Connection timeout in seconds — keep low so report queries
            // fail fast rather than piling up under heavy dashboard load.
            'timeout'    => (int) env('DB_TIMEOUT', 3),

            // PDO options for performance tuning
            'options' => [
                // Buffer entire result sets on fetch to reduce network round-trips
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,

                // Run init commands on every new connection (SET NAMES, timezone, etc.)
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4', time_zone='+00:00'",

                // Let MySQL's query cache do its work — don't add PDO-level statement caching
                // (performance tests show statement caching hurts more than it helps with webman's
                // per-request worker model)
            ],
        ],
    ],
];
