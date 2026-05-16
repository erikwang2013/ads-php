<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Admin panel server configuration.
 *
 * Key ports in the architecture:
 *   :8788 — service (user-facing business API, webman v2)
 *   :8789 — admin   (management panel, webman-admin v2)
 *   :5173 — vite   (frontend dev server, proxy /api → :8788)
 */

return [
    // Listen address — the admin panel binds to port 8789.
    // In production, Nginx reverse-proxies :80 → :8789 for the admin SPA
    // and :80/api/* → :8788 for the service API.
    'listen' => 'http://0.0.0.0:8789',

    // Stream context options (e.g., SSL certificate paths for HTTPS)
    'context' => [],

    // Number of worker processes — admin panel is lighter than service,
    // typically 1-2 workers are sufficient as it primarily serves the SPA
    // and handles low-volume admin API requests.
    'count' => 2,
];
