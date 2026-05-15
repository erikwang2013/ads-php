<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

return [
    // Global middleware — applied to every request in the application.
    // Add fully-qualified middleware class names here to run them on all routes.
    'global' => [
        \plugin\ads_api\middleware\CorsMiddleware::class,
        \plugin\ads_api\middleware\RateLimitMiddleware::class,
        \plugin\ads_api\middleware\SqlGuardMiddleware::class,
        \plugin\ads_api\middleware\ValidationMiddleware::class,
        \plugin\ads_api\middleware\EncryptionMiddleware::class,
    ],
];
