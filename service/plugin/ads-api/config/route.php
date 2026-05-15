<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

use plugin\ads_api\middleware\AuthMiddleware;
use plugin\ads_api\controller\AuthController;
use plugin\ads_api\controller\PlatformController;
use plugin\ads_api\controller\AccountController;
use plugin\ads_api\controller\CampaignController;
use plugin\ads_api\controller\DashboardController;
use plugin\ads_api\controller\ReportController;
use plugin\ads_api\controller\AlertController;
use plugin\ads_api\controller\ExportController;

// Public routes
Webman\Route::post('/api/v1/auth/login', [AuthController::class, 'login']);
Webman\Route::get('/api/v1/platforms', [PlatformController::class, 'index']);

// Authenticated routes
Webman\Route::group('/api/v1', function () {
    Webman\Route::get('/auth/me', [AuthController::class, 'me']);

    Webman\Route::get('/platforms/{code}/oauth-url', [PlatformController::class, 'oauthUrl']);
    Webman\Route::post('/platforms/{code}/callback', [PlatformController::class, 'callback']);

    Webman\Route::get('/accounts', [AccountController::class, 'index']);
    Webman\Route::get('/accounts/{id}', [AccountController::class, 'show']);
    Webman\Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);
    Webman\Route::post('/accounts/{id:\d+}/sync', [AccountController::class, 'sync']);

    Webman\Route::get('/campaigns', [CampaignController::class, 'index']);
    Webman\Route::post('/campaigns', [CampaignController::class, 'store']);
    Webman\Route::get('/campaigns/{id}', [CampaignController::class, 'show']);
    Webman\Route::put('/campaigns/{id}', [CampaignController::class, 'update']);
    Webman\Route::post('/campaigns/{id:\d+}/toggle', [CampaignController::class, 'toggle']);

    Webman\Route::get('/reports/summary', [DashboardController::class, 'summary']);
    Webman\Route::get('/reports/custom', [ReportController::class, 'custom']);
    Webman\Route::get('/reports/export', [ExportController::class, 'export']);
    Webman\Route::get('/reports/export-dashboard', [ExportController::class, 'exportDashboard']);

    Webman\Route::get('/alerts/rules', [AlertController::class, 'rules']);
    Webman\Route::post('/alerts/rules', [AlertController::class, 'createRule']);
    Webman\Route::put('/alerts/rules/{id:\d+}', [AlertController::class, 'updateRule']);
    Webman\Route::delete('/alerts/rules/{id:\d+}', [AlertController::class, 'deleteRule']);
    Webman\Route::get('/alerts/logs', [AlertController::class, 'logs']);
    Webman\Route::post('/alerts/logs/{id:\d+}/acknowledge', [AlertController::class, 'acknowledge']);
    Webman\Route::get('/alerts/unread-count', [AlertController::class, 'unreadCount']);
})->middleware([AuthMiddleware::class]);
