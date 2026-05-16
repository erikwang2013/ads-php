<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Global middleware stack for the admin panel.
 *
 * Request flow per middleware registered below:
 *   Request → middleware::process() → next middleware → ... → controller
 *
 * Currently the admin panel relies on the webman-admin plugin's built-in
 * session/auth handling. Add additional middleware (CORS, rate limiting,
 * etc.) here as the admin panel grows.
 *
 * Auth is handled per-route-group in config/route.php via AuthCheck.
 */

return [
    'global' => [
        // Add global middleware class names here, e.g.:
        // \admin\middleware\CorsMiddleware::class,
        // \admin\middleware\RateLimitMiddleware::class,
    ],
];
