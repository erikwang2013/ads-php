<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

use admin\middleware\AuthCheck;
use admin\controller\AdminUserController;
use admin\controller\AuditLogController;
use admin\controller\AuthController;

// Public routes (no authentication required)
\Webman\Route::post('/api/admin/login', [AuthController::class, 'login']);
\Webman\Route::get('/api/admin/roles', [AuthController::class, 'roles']);

// Protected routes (require valid JWT or session)
\Webman\Route::group('/api/admin', function () {
    \Webman\Route::get('/me', [AuthController::class, 'me']);
    \Webman\Route::post('/logout', [AuthController::class, 'logout']);
    \Webman\Route::get('/users', [AdminUserController::class, 'index']);
    \Webman\Route::post('/users', [AdminUserController::class, 'store']);
    \Webman\Route::put('/users/{id:\d+}', [AdminUserController::class, 'update']);
    \Webman\Route::delete('/users/{id:\d+}', [AdminUserController::class, 'destroy']);
    \Webman\Route::get('/users/roles', [AdminUserController::class, 'roles']);
    \Webman\Route::get('/audit-logs', [AuditLogController::class, 'index']);
})->middleware([AuthCheck::class]);
