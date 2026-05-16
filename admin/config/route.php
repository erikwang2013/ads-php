<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 管理后台路由配置
 *
 * 路由分为两组：
 *   1. 公开路由 — 无需认证（登录、角色列表）
 *   2. 保护路由 — 需要 JWT Token 或 Session（通过 AuthCheck 中间件）
 *
 * 业务数据查询不在此定义路由——管理后台 Vue SPA 直接通过 Vite 代理（开发）
 * 或 Nginx 反向代理（生产）访问 service API（:8788）。
 */

use admin\middleware\AuthCheck;
use admin\controller\AdminUserController;
use admin\controller\AuditLogController;
use admin\controller\AuthController;

// ============================================================================
// 公开路由 — 无需认证
// ============================================================================

// POST /api/admin/login — 管理员登录，返回 JWT Token
Webman\Route::post('/api/admin/login', [AuthController::class, 'login']);

// GET /api/admin/roles — 获取可用角色列表（登录页下拉框使用）
Webman\Route::get('/api/admin/roles', [AuthController::class, 'roles']);

// ============================================================================
// 保护路由 — 需要 JWT Bearer Token 或有效 admin Session
// ============================================================================
Webman\Route::group('/api/admin', function () {

    // GET /api/admin/me — 当前管理员信息（含角色与权限）
    Webman\Route::get('/me', [AuthController::class, 'me']);

    // POST /api/admin/logout — 退出登录，清除 Session 并记录审计
    Webman\Route::post('/logout', [AuthController::class, 'logout']);

    // === 用户管理 ===

    // GET /api/admin/users — 用户列表（支持关键词/角色筛选，分页）
    Webman\Route::get('/users', [AdminUserController::class, 'index']);

    // POST /api/admin/users — 创建管理员用户（密码自动 bcrypt 哈希）
    Webman\Route::post('/users', [AdminUserController::class, 'store']);

    // PUT /api/admin/users/{id} — 更新用户信息（姓名/邮箱/角色）
    Webman\Route::put('/users/{id:\d+}', [AdminUserController::class, 'update']);

    // DELETE /api/admin/users/{id} — 软删除用户（设置 status=0）
    Webman\Route::delete('/users/{id:\d+}', [AdminUserController::class, 'destroy']);

    // GET /api/admin/users/roles — 可用角色列表（用户编辑页下拉框）
    Webman\Route::get('/users/roles', [AdminUserController::class, 'roles']);

    // === 审计日志 ===

    // GET /api/admin/audit-logs — 审计日志列表
    // 查询参数：user_id（操作人）/ action（操作类型）/ date_start / date_end
    Webman\Route::get('/audit-logs', [AuditLogController::class, 'index']);

})->middleware([AuthCheck::class]);
