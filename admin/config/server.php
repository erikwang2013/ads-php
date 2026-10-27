<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 管理后台服务器配置
 *
 * 架构端口一览：
 *   :8788 — service  用户端业务 API（webman v2）
 *   :8789 — admin    管理后台（webman-admin v2） ← 本文件
 *   :5173 — vite     前端开发服务器（开发模式，代理 /api → :8788）
 *
 * 生产模式：
 *   Nginx :80 → /          → admin:8789（管理后台 SPA）
 *   Nginx :80 → /api/*     → service:8788（业务 API）
 */

return [
    // 监听地址：绑定所有网卡，端口 8789
    'listen' => 'http://0.0.0.0:8789',

    // 流上下文（SSL 证书路径等）
    'context' => [],

    // Worker 进程数：管理后台负载较轻，2 个 worker 足够
    // 主要工作是返回 SPA 静态文件和少量管理 API 请求
    'count' => 2,
];
