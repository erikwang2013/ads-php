<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 服务端服务器配置
 *
 * 架构端口说明：
 *   :8788 — service 业务 API（本文件）
 *   :8789 — admin  管理后台 webman-admin v2
 *   :5173 — vite   前端开发服务器（代理 /api → :8788）
 */

return [
    // 监听地址：绑定所有网卡，端口 8788
    'listen' => 'http://0.0.0.0:8788',

    // 传输协议：tcp 用于 HTTP，ssl 用于 HTTPS
    'transport' => 'tcp',

    // 流上下文（SSL 证书路径等）TODO
    'context' => [],

    // 进程名，在 ps aux 中可见
    'name' => 'webman',

    // Worker 进程数，通常设为 cpu_count() * 2 以充分利用 CPU
    'count' => cpu_count() * 2,

    // 以指定系统用户运行 worker（留空=当前用户）
    'user' => '',

    // 以指定系统用户组运行 worker
    'group' => '',

    // SO_REUSEPORT：多 worker 负载均衡更均匀
    'reusePort' => false,

    // 自定义事件循环类（留空=根据扩展自动选择）
    'event_loop' => '',

    // Master 进程 PID 文件路径
    'pid_file' => runtime_path() . '/webman.pid',
    'pidFile' => runtime_path() . '/webman.pid',

    // 状态文件路径（监控用）
    'status_file' => runtime_path() . '/webman.status',
    'statusFile' => runtime_path() . '/webman.status',

    // Worker 标准输出日志
    'stdout_file' => runtime_path() . '/logs/out.log',

    // Workerman 内部诊断日志
    'log_file' => runtime_path() . '/logs/workerman.log',
    'logFile' => runtime_path() . '/logs/workerman.log',

    // 最大数据包大小（10MB），防止超大请求攻击
    'max_package_size' => 10 * 1024 * 1024,
    'maxPackageSize' => 10 * 1024 * 1024,

    // 优雅停止超时（秒）：停止前等待正在处理的请求完成
    'stop_timeout' => 2,
];
