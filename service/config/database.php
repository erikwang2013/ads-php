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
        ],
    ],
];
