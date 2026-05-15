<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller;

use Webman\Http\Request;
use app\support\ApiResponse;
use erik\support\CacheService;
use Illuminate\Database\Capsule\Manager as DB;

class DashboardController
{
    public function summary(Request $request): Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $dateStart = $request->get('date_start', date('Y-m-d'));
        $dateEnd   = $request->get('date_end', date('Y-m-d'));

        $cacheKey = CacheService::dashboardKey($tenantId, $dateStart, $dateEnd);

        $data = CacheService::remember($cacheKey, function () use ($tenantId, $dateStart, $dateEnd) {
            $overview = (array) DB::table('erik_report_metrics')
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
                ->selectRaw('COALESCE(SUM(impressions), 0) as total_impressions')
                ->selectRaw('COALESCE(SUM(clicks), 0) as total_clicks')
                ->selectRaw('COALESCE(SUM(conversions), 0) as total_conversions')
                ->selectRaw('CASE WHEN SUM(impressions) > 0 THEN ROUND(SUM(clicks)/SUM(impressions)*100, 2) ELSE 0 END as avg_ctr')
                ->selectRaw('CASE WHEN SUM(clicks) > 0 THEN ROUND(SUM(conversions)/SUM(clicks)*100, 2) ELSE 0 END as avg_cvr')
                ->selectRaw('CASE WHEN SUM(cost) > 0 THEN ROUND(SUM(cost)/SUM(conversions)/100, 2) ELSE 0 END as avg_cpa')
                ->first();

            $byPlatform = DB::table('erik_report_metrics')
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->groupBy('platform')
                ->select('platform')
                ->selectRaw('COALESCE(SUM(cost), 0) as cost')
                ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
                ->selectRaw('COALESCE(SUM(clicks), 0) as clicks')
                ->selectRaw('COALESCE(SUM(conversions), 0) as conversions')
                ->orderByDesc('cost')
                ->get();

            $daily = DB::table('erik_report_metrics')
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->groupBy('date', 'platform')
                ->orderBy('date')
                ->select('date', 'platform')
                ->selectRaw('COALESCE(SUM(cost), 0) as cost')
                ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
                ->get();

            return [
                'overview'    => $overview,
                'by_platform' => $byPlatform,
                'daily'       => $daily,
            ];
        }, 300);

        return ApiResponse::success($data);
    }
}
