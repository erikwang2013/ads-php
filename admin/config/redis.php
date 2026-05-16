<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Redis configuration for the admin panel.
 *
 * Redis is used for:
 * - Session storage (webman-admin built-in)
 * - Rate limiting counters (RateLimitMiddleware)
 * - Cache for proxied service API responses (optional)
 */

return [
    // Default Redis connection
    'default' => [
        // Redis server hostname or IP address
        'host'     => env('REDIS_HOST', '127.0.0.1'),

        // Redis server port
        'port'     => env('REDIS_PORT', '6379'),

        // Authentication password (empty = no password)
        'password' => env('REDIS_PASSWORD', ''),

        // Redis database index (0-15).
        // Using database 1 to isolate admin session/cache keys
        // from the service (which uses database 0).
        'database' => 1,
    ],
];
