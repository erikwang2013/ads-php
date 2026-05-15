<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

return [
    // Default Redis connection configuration
    'default' => [
        // Redis server hostname or IP address
        'host'     => env('REDIS_HOST', '127.0.0.1'),

        // Redis server port
        'port'     => env('REDIS_PORT', '6379'),

        // Authentication password (empty = no password)
        'password' => env('REDIS_PASSWORD', ''),

        // Redis database index (0-15)
        'database' => 0,
    ],
];
