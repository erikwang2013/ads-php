<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

return [
    // Enable debug mode — shows detailed error messages in responses
    'debug' => env('APP_DEBUG', false),

    // Default timezone for the application
    'default_timezone' => 'Asia/Shanghai',

    // JWT authentication configuration
    'jwt' => [
        // Secret key used to sign and verify tokens (set via JWT_SECRET env var)
        'secret' => env('JWT_SECRET', ''),

        // Token time-to-live in seconds (default: 86400 = 24 hours)
        'ttl' => (int) env('JWT_TTL', 86400),
    ],
];
