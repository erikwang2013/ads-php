<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
return [
    'default' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => env('REDIS_PORT', '6379'),
        'password' => env('REDIS_PASSWORD', ''),
        'database' => 0,
    ],
];
