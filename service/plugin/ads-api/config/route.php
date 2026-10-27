<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

use plugin\ads_api\middleware\AuthMiddleware;
use plugin\ads_api\controller\v1\AuthController;
use plugin\ads_api\controller\v1\CaptchaController;
use plugin\ads_api\controller\v1\HealthController;
use plugin\ads_api\controller\v1\PlatformController;
use plugin\ads_api\controller\v1\AccountController;
use plugin\ads_api\controller\v1\CampaignController;
use plugin\ads_api\controller\v1\AssetController;
use plugin\ads_api\controller\v1\DashboardController;
use plugin\ads_api\controller\v1\AlertController;
use plugin\ads_api\controller\v1\NotificationController;
use plugin\ads_api\controller\v1\DocController;
use plugin\ads_api\controller\v1\SyncController;
use plugin\ads_api\controller\v1\ConversionController;
use plugin\ads_api\controller\v1\TenantController;

// API 版本号固定在路由路径中：/api/v1/... → plugin\ads_api\controller\v1\*。
// 新增版本时注册独立分组（如 /api/v2 → controller\v2\*）。
// /health、/ping、/docs 为无版本公开端点。

// Captcha (public)
Webman\Route::get('/api/v1/captcha/generate', [CaptchaController::class, 'generate']);
Webman\Route::post('/api/v1/captcha/verify', [CaptchaController::class, 'verify']);

// Health check (unversioned, public)
Webman\Route::get('/health', [HealthController::class, 'health']);
Webman\Route::get('/ping', [HealthController::class, 'ping']);

// Public routes
Webman\Route::get('/docs', [DocController::class, 'index']);
Webman\Route::post('/api/v1/auth/login', [AuthController::class, 'login']);
Webman\Route::get('/api/v1/platforms', [PlatformController::class, 'index']);

// Authenticated routes
Webman\Route::group('/api/v1', function () {
    Webman\Route::get('/auth/me', [AuthController::class, 'me']);
    Webman\Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);

    Webman\Route::get('/platforms/{code}/oauth-url', [PlatformController::class, 'oauthUrl']);
    Webman\Route::post('/platforms/{code}/callback', [PlatformController::class, 'callback']);

    Webman\Route::get('/accounts', [AccountController::class, 'index']);
    Webman\Route::get('/accounts/{id}', [AccountController::class, 'show']);
    Webman\Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);
    Webman\Route::post('/accounts/{id:\d+}/sync', [AccountController::class, 'sync']);

    Webman\Route::get('/campaigns', [CampaignController::class, 'index']);

    Webman\Route::get('/reports/summary', [DashboardController::class, 'summary']);

    Webman\Route::get('/alerts/rules', [AlertController::class, 'rules']);
    Webman\Route::post('/alerts/rules', [AlertController::class, 'createRule']);
    Webman\Route::put('/alerts/rules/{id:\d+}', [AlertController::class, 'updateRule']);
    Webman\Route::delete('/alerts/rules/{id:\d+}', [AlertController::class, 'deleteRule']);

    Webman\Route::get('/alerts/logs', [AlertController::class, 'logs']);
    Webman\Route::post('/alerts/logs/{id:\d+}/acknowledge', [AlertController::class, 'acknowledge']);
    Webman\Route::get('/alerts/unread-count', [AlertController::class, 'unreadCount']);

    Webman\Route::get('/notifications', [NotificationController::class, 'index']);
    Webman\Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Webman\Route::post('/notifications/{id:\d+}/read', [NotificationController::class, 'markRead']);
    Webman\Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    Webman\Route::get('/assets', [AssetController::class, 'index']);
    Webman\Route::post('/assets/upload', [AssetController::class, 'upload']);
    Webman\Route::post('/assets/presign', [AssetController::class, 'presign']);
    Webman\Route::post('/assets/register', [AssetController::class, 'register']);
    Webman\Route::get('/assets/{id}', [AssetController::class, 'show']);
    Webman\Route::delete('/assets/{id}', [AssetController::class, 'destroy']);

    // 同步状态可视化 (Phase 10 Task 1)
    Webman\Route::get('/sync/status', [SyncController::class, 'status']);
    Webman\Route::get('/sync/errors', [SyncController::class, 'errors']);

    // 转化数据采集 (Phase 10 Task 2)
    Webman\Route::post('/conversions', [ConversionController::class, 'store']);
    Webman\Route::get('/conversions', [ConversionController::class, 'index']);

    // 多租户配额 (Phase 10 Task 4)
    Webman\Route::get('/tenant/quota', [TenantController::class, 'quota']);
})->middleware([AuthMiddleware::class]);
