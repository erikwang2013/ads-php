<?php
/**
 * Admin panel application configuration.
 */
return [
    'debug' => env('APP_DEBUG', false),
    'default_timezone' => 'Asia/Shanghai',
    'service_api_url' => env('SERVICE_API_URL', 'http://127.0.0.1:8788/api/v1'),
    'jwt' => [
        'secret' => env('JWT_SECRET', ''),
        'ttl' => (int) env('JWT_TTL', 86400),
    ],
];
