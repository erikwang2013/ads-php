<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 日志配置（Monolog）
 *
 * 格式控制：
 *   LOG_FORMAT=json   → JSON 结构化日志（生产推荐）
 *   LOG_FORMAT=line   → 单行文本（开发默认）
 *
 * 级别控制：
 *   APP_DEBUG=true    → DEBUG
 *   APP_DEBUG=false   → WARNING
 */

$isJson = env('LOG_FORMAT', 'line') === 'json';
$logLevel = env('APP_DEBUG', false) ? \Monolog\Logger::DEBUG : \Monolog\Logger::WARNING;

return [
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/webman.log',
                    7,
                    $logLevel,
                ],
                'formatter' => $isJson ? [
                    'class' => Monolog\Formatter\JsonFormatter::class,
                ] : [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [
                        null,
                        'Y-m-d H:i:s',
                        true,
                    ],
                ],
            ]
        ],
    ],
];
