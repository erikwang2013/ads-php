<?php
return [
    'debug' => env('APP_DEBUG', false),
    'default_timezone' => 'Asia/Shanghai',
    'jwt' => [
        'secret' => env('JWT_SECRET', ''),
        'ttl' => (int) env('JWT_TTL', 86400),
    ],
];
