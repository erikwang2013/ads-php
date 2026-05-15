<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

return [
    // Listen address — the network interface and port the server binds to
    'listen' => 'http://0.0.0.0:8788',

    // Transport protocol — tcp for standard HTTP, ssl for HTTPS
    'transport' => 'tcp',

    // Stream context options (e.g., SSL certificate paths)
    'context' => [],

    // Server process name shown in process listings
    'name' => 'webman',

    // Number of worker processes — typically cpu_count() * 2 for optimal throughput
    'count' => cpu_count() * 2,

    // Run worker processes as this system user (empty = current user)
    'user' => '',

    // Run worker processes as this system group (empty = current group)
    'group' => '',

    // Enable SO_REUSEPORT for better load distribution across workers
    'reusePort' => false,

    // Custom event-loop class (empty = default based on available extensions)
    'event_loop' => '',

    // PID file path — stores the master process ID
    'pid_file' => runtime_path() . '/webman.pid',
    'pidFile' => runtime_path() . '/webman.pid',

    // Status file path — used for monitoring the server state
    'status_file' => runtime_path() . '/webman.status',
    'statusFile' => runtime_path() . '/webman.status',

    // Stdout log file — captures worker standard output
    'stdout_file' => runtime_path() . '/logs/out.log',

    // Workerman log file — internal workerman diagnostics
    'log_file' => runtime_path() . '/logs/workerman.log',
    'logFile' => runtime_path() . '/logs/workerman.log',

    // Maximum package size in bytes (10 MB) — protects against oversized requests
    'max_package_size' => 10 * 1024 * 1024,
    'maxPackageSize' => 10 * 1024 * 1024,

    // Graceful stop timeout in seconds — time to finish pending requests before exit
    'stop_timeout' => 2,
];
