<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 日志配置（Monolog）
 */

return [
    // 默认日志通道
    'default' => [
        // 已注册的日志处理器
        'handlers' => [
            [
                // RotatingFileHandler：按日轮转的日志文件
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    // 日志文件路径（位于 runtime 目录下）
                    runtime_path() . '/logs/webman.log',

                    // 保留最近 7 天的日志文件
                    7,

                    // 最低记录级别（DEBUG = 记录所有日志）
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    // LineFormatter：单行格式，带时间戳
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [
                        null,             // 使用默认格式字符串
                        'Y-m-d H:i:s',    // 日志中的日期格式
                        true,             // 输出中包含异常堆栈信息
                    ],
                ],
            ]
        ],
    ],
];
