<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

return [
    // Default log channel configuration
    'default' => [
        // Registered log handlers (Monolog handlers)
        'handlers' => [
            [
                // RotatingFileHandler: writes logs to files, rotated daily
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    // Log file path within the runtime directory
                    runtime_path() . '/logs/webman.log',

                    // Maximum number of daily log files to retain (7 days)
                    7,

                    // Minimum log level to record (DEBUG = all messages)
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    // LineFormatter: single-line log entries with timestamp
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [
                        null,             // Default format string
                        'Y-m-d H:i:s',    // Date format in log entries
                        true,             // Include stack traces in log output
                    ],
                ],
            ]
        ],
    ],
];
