<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 管理后台路由配置
 *
 * 路由分为两组：
 *   1. 公开路由 — 无需认证（登录）
 *   2. 保护路由 — 需要 JWT Token 或 Session（通过 AuthCheck 中间件）
 *
 * 业务数据查询不在此定义路由——管理后台 Vue SPA 直接通过 Vite 代理（开发）
 * 或 Nginx 反向代理（生产）访问 service API（:8788）。
 */

use admin\middleware\AuthCheck;
use admin\controller\AuthController;
use admin\controller\InstallController;

// ============================================================================
// 安装向导路由 — 无需任何中间件
// ============================================================================

// GET /install — 安装向导页面
Webman\Route::get('/install', [InstallController::class, 'index']);

// POST /api/install/check — 测试数据库连接
Webman\Route::post('/api/install/check', [InstallController::class, 'checkDb']);

// POST /api/install/run — 执行安装
Webman\Route::post('/api/install/run', [InstallController::class, 'run']);

// GET /api/install/status — 检查是否已安装
Webman\Route::get('/api/install/status', [InstallController::class, 'status']);

// ============================================================================
// 公开路由 — 无需认证
// ============================================================================

// POST /api/admin/login — 管理员登录，返回 JWT Token
Webman\Route::post('/api/admin/login', [AuthController::class, 'login']);

// ============================================================================
// 保护路由 — 需要 JWT Bearer Token 或有效 admin Session
// ============================================================================
Webman\Route::group('/api/admin', function () {

    // GET /api/admin/me — 当前管理员信息
    Webman\Route::get('/me', [AuthController::class, 'me']);

    // POST /api/admin/logout — 退出登录，清除 Session
    Webman\Route::post('/logout', [AuthController::class, 'logout']);

})->middleware([AuthCheck::class]);
