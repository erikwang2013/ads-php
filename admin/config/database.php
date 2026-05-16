<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Admin panel database configuration.
 *
 * Stores admin-specific tables: admin_users, admin_roles, admin_audit_logs.
 * Business data (campaigns, reports, platform accounts) lives in the
 * service database and is accessed via the service API (:8788), NOT
 * through direct database queries from the admin panel.
 *
 * Both admin and service share the same physical MySQL server but
 * use separate table prefixes for clean logical separation.
 */

return [
    // Default connection name — used by DB::table() when no connection is specified
    'default' => 'admin',

    'connections' => [
        'admin' => [
            // PDO driver — mysql, pgsql, sqlite, sqlsrv
            'driver'    => 'mysql',

            // Connection host (set via DB_HOST env var, defaults to localhost)
            'host'      => env('DB_HOST', '127.0.0.1'),

            // Connection port
            'port'      => env('DB_PORT', '3306'),

            // Database name — shared with the service for operational simplicity
            'database'  => env('DB_DATABASE', 'ads'),

            // Authentication credentials
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),

            // Character set and collation for UTF-8 support (including emoji)
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',

            // Table prefix — admin tables use literal 'admin_' in their names
            // (admin_users, admin_roles, admin_audit_logs) rather than a prefix here.
            'prefix'    => '',
        ],
    ],
];
