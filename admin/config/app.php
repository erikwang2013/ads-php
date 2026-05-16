<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Admin panel application configuration.
 *
 * The admin panel is a standalone webman-admin v2 instance that:
 * - Serves the Vue 3 SPA from public/web/
 * - Provides admin-specific APIs (user management, audit logs, RBAC)
 * - Proxies business API calls to the service backend (:8788) via ServiceProxy
 */

return [
    // Enable debug mode — shows detailed error messages in responses.
    // Set to false in production.
    'debug' => env('APP_DEBUG', false),

    // Default timezone for the application
    'default_timezone' => 'Asia/Shanghai',

    // Base URL of the user-facing business service API.
    // All admin business queries (campaigns, reports, accounts, alerts)
    // are proxied through ServiceProxy to this endpoint.
    'service_api_url' => env('SERVICE_API_URL', 'http://127.0.0.1:8788/api/v1'),

    // JWT authentication configuration for admin panel
    'jwt' => [
        // Secret key used to sign and verify admin JWT tokens
        'secret' => env('JWT_SECRET', ''),

        // Token time-to-live in seconds (default: 86400 = 24 hours)
        'ttl' => (int) env('JWT_TTL', 86400),
    ],
];
