<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Admin panel route definitions.
 *
 * Route groups:
 * 1. Public routes — no authentication required (login, roles list)
 * 2. Protected routes — require JWT or session via AuthCheck middleware
 *
 * All business data (campaigns, reports, accounts) is accessed through
 * the service API (:8788), NOT through routes defined here. The admin
 * Vue SPA calls service API endpoints directly via the Vite proxy
 * (dev) or Nginx reverse proxy (production).
 */

use admin\middleware\AuthCheck;
use admin\controller\AdminUserController;
use admin\controller\AuditLogController;
use admin\controller\AuthController;

// ============================================================================
// Public routes — no authentication required
// ============================================================================

// POST /api/admin/login — Authenticate admin user, return JWT token
\Webman\Route::post('/api/admin/login', [AuthController::class, 'login']);

// GET /api/admin/roles — List available admin roles (used by login form)
\Webman\Route::get('/api/admin/roles', [AuthController::class, 'roles']);

// ============================================================================
// Protected routes — require JWT Bearer token or valid admin session
// ============================================================================
\Webman\Route::group('/api/admin', function () {

    // GET /api/admin/me — Current admin user info with role and permissions
    \Webman\Route::get('/me', [AuthController::class, 'me']);

    // POST /api/admin/logout — Clear session and audit the logout event
    \Webman\Route::post('/logout', [AuthController::class, 'logout']);

    // --- User Management ---

    // GET /api/admin/users — Paginated user list with keyword/role filters
    \Webman\Route::get('/users', [AdminUserController::class, 'index']);

    // POST /api/admin/users — Create a new admin user (bcrypt password)
    \Webman\Route::post('/users', [AdminUserController::class, 'store']);

    // PUT /api/admin/users/{id} — Update user details (name, email, role)
    \Webman\Route::put('/users/{id:\d+}', [AdminUserController::class, 'update']);

    // DELETE /api/admin/users/{id} — Soft-delete user (sets status=0)
    \Webman\Route::delete('/users/{id:\d+}', [AdminUserController::class, 'destroy']);

    // GET /api/admin/users/roles — Available roles for user assignment dropdowns
    \Webman\Route::get('/users/roles', [AdminUserController::class, 'roles']);

    // --- Audit Logging ---

    // GET /api/admin/audit-logs — Paginated audit trail with filters
    // Query params: user_id, action, date_start, date_end
    \Webman\Route::get('/audit-logs', [AuditLogController::class, 'index']);

})->middleware([AuthCheck::class]);
