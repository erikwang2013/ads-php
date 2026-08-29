<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

use plugin\ads_api\middleware\AuthMiddleware;
use plugin\ads_api\middleware\AdminMiddleware;
use plugin\ads_api\controller\v1\AuthController;
use plugin\ads_api\controller\v1\CaptchaController;
use plugin\ads_api\controller\v1\HealthController;
use plugin\ads_api\controller\v1\PlatformController;
use plugin\ads_api\controller\v1\AccountController;
use plugin\ads_api\controller\v1\CampaignController;
use plugin\ads_api\controller\v1\AdGroupController;
use plugin\ads_api\controller\v1\CreativeController;
use plugin\ads_api\controller\v1\AssetController;
use plugin\ads_api\controller\v1\BidRuleController;
use plugin\ads_api\controller\v1\TargetingTemplateController;
use plugin\ads_api\controller\v1\DashboardController;
use plugin\ads_api\controller\v1\ReportController;
use plugin\ads_api\controller\v1\AlertController;
use plugin\ads_api\controller\v1\NotificationController;
use plugin\ads_api\controller\v1\ExportController;
use plugin\ads_api\controller\v1\DocController;
use plugin\ads_api\controller\v1\SyncController;
use plugin\ads_api\controller\v1\ConversionController;
use plugin\ads_api\controller\v1\TenantController;
use plugin\ads_api\controller\v1\admin\CdnProviderController;

require_once __DIR__ . '/../route_helpers.php';

// API 版本号通过 X-API-Version 头部传递，不再出现在 URL 路径中。
// VersionMiddleware 读取该头部并设置 $request->apiVersion，
// versioned() 辅助函数据此将请求转发到对应版本的控制器。

// Captcha (public)
Webman\Route::get('/api/captcha/generate', versioned(CaptchaController::class, 'generate'));
Webman\Route::post('/api/captcha/verify', versioned(CaptchaController::class, 'verify'));

// Health check (public, no version header required)
Webman\Route::get('/health', [HealthController::class, 'health']);
Webman\Route::get('/ping', [HealthController::class, 'ping']);

// Public routes
Webman\Route::get('/docs', [DocController::class, 'index']);
Webman\Route::post('/api/auth/login', versioned(AuthController::class, 'login'));
Webman\Route::get('/api/platforms', versioned(PlatformController::class, 'index'));

// Authenticated routes
Webman\Route::group('/api', function () {
    Webman\Route::get('/auth/me', versioned(AuthController::class, 'me'));
    Webman\Route::post('/auth/refresh', versioned(AuthController::class, 'refreshToken'));

    Webman\Route::get('/platforms/{code}/oauth-url', versioned(PlatformController::class, 'oauthUrl'));
    Webman\Route::post('/platforms/{code}/callback', versioned(PlatformController::class, 'callback'));

    Webman\Route::get('/accounts', versioned(AccountController::class, 'index'));
    Webman\Route::get('/accounts/{id}', versioned(AccountController::class, 'show'));
    Webman\Route::delete('/accounts/{id}', versioned(AccountController::class, 'destroy'));
    Webman\Route::post('/accounts/{id:\d+}/sync', versioned(AccountController::class, 'sync'));

    Webman\Route::get('/campaigns', versioned(CampaignController::class, 'index'));
    Webman\Route::post('/campaigns', versioned(CampaignController::class, 'store'));
    Webman\Route::get('/campaigns/{id}', versioned(CampaignController::class, 'show'));
    Webman\Route::put('/campaigns/{id}', versioned(CampaignController::class, 'update'));
    Webman\Route::post('/campaigns/{id:\d+}/toggle', versioned(CampaignController::class, 'toggle'));
    Webman\Route::post('/campaigns/batch/toggle', versioned(CampaignController::class, 'batchToggle'));

    Webman\Route::get('/ad-groups', versioned(AdGroupController::class, 'index'));
    Webman\Route::post('/ad-groups', versioned(AdGroupController::class, 'store'));
    Webman\Route::get('/ad-groups/{id}', versioned(AdGroupController::class, 'show'));
    Webman\Route::put('/ad-groups/{id}', versioned(AdGroupController::class, 'update'));
    Webman\Route::post('/ad-groups/{id:\d+}/toggle', versioned(AdGroupController::class, 'toggle'));

    Webman\Route::get('/creatives', versioned(CreativeController::class, 'index'));
    Webman\Route::get('/creatives/{id}', versioned(CreativeController::class, 'show'));

    Webman\Route::get('/reports/summary', versioned(DashboardController::class, 'summary'));
    Webman\Route::get('/reports/custom', versioned(ReportController::class, 'custom'));
    Webman\Route::get('/reports/export', versioned(ExportController::class, 'export'));
    Webman\Route::get('/reports/export-dashboard', versioned(ExportController::class, 'exportDashboard'));

    Webman\Route::get('/alerts/rules', versioned(AlertController::class, 'rules'));
    Webman\Route::post('/alerts/rules', versioned(AlertController::class, 'createRule'));
    Webman\Route::put('/alerts/rules/{id:\d+}', versioned(AlertController::class, 'updateRule'));
    Webman\Route::delete('/alerts/rules/{id:\d+}', versioned(AlertController::class, 'deleteRule'));

    Webman\Route::get('/bid-rules', versioned(BidRuleController::class, 'index'));
    Webman\Route::post('/bid-rules', versioned(BidRuleController::class, 'store'));
    Webman\Route::put('/bid-rules/{id:\d+}', versioned(BidRuleController::class, 'update'));
    Webman\Route::delete('/bid-rules/{id:\d+}', versioned(BidRuleController::class, 'destroy'));
    Webman\Route::get('/bid-rules/logs', versioned(BidRuleController::class, 'logs'));

    Webman\Route::get('/targeting-templates', versioned(TargetingTemplateController::class, 'index'));
    Webman\Route::get('/targeting-templates/{id}', versioned(TargetingTemplateController::class, 'show'));
    Webman\Route::post('/targeting-templates', versioned(TargetingTemplateController::class, 'store'));
    Webman\Route::put('/targeting-templates/{id:\d+}', versioned(TargetingTemplateController::class, 'update'));
    Webman\Route::delete('/targeting-templates/{id:\d+}', versioned(TargetingTemplateController::class, 'destroy'));
    Webman\Route::get('/alerts/logs', versioned(AlertController::class, 'logs'));
    Webman\Route::post('/alerts/logs/{id:\d+}/acknowledge', versioned(AlertController::class, 'acknowledge'));
    Webman\Route::get('/alerts/unread-count', versioned(AlertController::class, 'unreadCount'));

    Webman\Route::get('/notifications', versioned(NotificationController::class, 'index'));
    Webman\Route::get('/notifications/unread-count', versioned(NotificationController::class, 'unreadCount'));
    Webman\Route::post('/notifications/{id:\d+}/read', versioned(NotificationController::class, 'markRead'));
    Webman\Route::post('/notifications/read-all', versioned(NotificationController::class, 'markAllRead'));

    Webman\Route::get('/assets', versioned(AssetController::class, 'index'));
    Webman\Route::post('/assets/upload', versioned(AssetController::class, 'upload'));
    Webman\Route::post('/assets/presign', versioned(AssetController::class, 'presign'));
    Webman\Route::post('/assets/register', versioned(AssetController::class, 'register'));
    Webman\Route::get('/assets/{id}', versioned(AssetController::class, 'show'));
    Webman\Route::delete('/assets/{id}', versioned(AssetController::class, 'destroy'));

    // CDN 服务商管理 (admin, 仅平台主租户 tenant 1)
    Webman\Route::group('/admin/cdn', function () {
        Webman\Route::get('/providers', versioned(CdnProviderController::class, 'index'));
        Webman\Route::post('/providers', versioned(CdnProviderController::class, 'store'));
        Webman\Route::put('/providers/{id:\d+}', versioned(CdnProviderController::class, 'update'));
        Webman\Route::delete('/providers/{id:\d+}', versioned(CdnProviderController::class, 'destroy'));
        Webman\Route::put('/providers/{id:\d+}/default', versioned(CdnProviderController::class, 'setDefault'));
        Webman\Route::put('/providers/{id:\d+}/toggle', versioned(CdnProviderController::class, 'toggle'));
        Webman\Route::post('/providers/{id:\d+}/test', versioned(CdnProviderController::class, 'test'));
        Webman\Route::post('/providers/{id:\d+}/purge', versioned(CdnProviderController::class, 'purge'));
    })->middleware([AdminMiddleware::class]);

    Webman\Route::get('/reports/attribution', versioned(DashboardController::class, 'attribution'));
    Webman\Route::get('/reports/attribution/models', versioned(DashboardController::class, 'attributionModels'));

    Webman\Route::get('/reports/calendar', versioned(DashboardController::class, 'calendar'));
    Webman\Route::get('/reports/budget-alerts', versioned(DashboardController::class, 'budgetAlerts'));

    // 同步状态可视化 (Phase 10 Task 1)
    Webman\Route::get('/sync/status', versioned(SyncController::class, 'status'));
    Webman\Route::get('/sync/errors', versioned(SyncController::class, 'errors'));

    // 转化数据采集 (Phase 10 Task 2)
    Webman\Route::post('/conversions', versioned(ConversionController::class, 'store'));
    Webman\Route::get('/conversions', versioned(ConversionController::class, 'index'));

    // 多租户配额 (Phase 10 Task 4)
    Webman\Route::get('/tenant/quota', versioned(TenantController::class, 'quota'));
})->middleware([AuthMiddleware::class]);
