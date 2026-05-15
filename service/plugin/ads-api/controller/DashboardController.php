<?php
namespace plugin\ads_api\controller;

use Webman\Http\Request;
use app\support\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;

class DashboardController
{
    public function summary(Request $request): \Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $dateStart = $request->get('date_start', date('Y-m-d'));
        $dateEnd   = $request->get('date_end', date('Y-m-d'));

        $overview = (array) DB::table('report_metrics')
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

        $byPlatform = DB::table('report_metrics')
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

        $daily = DB::table('report_metrics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->groupBy('date', 'platform')
            ->orderBy('date')
            ->select('date', 'platform')
            ->selectRaw('COALESCE(SUM(cost), 0) as cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
            ->get();

        return ApiResponse::success([
            'overview'    => $overview,
            'by_platform' => $byPlatform,
            'daily'       => $daily,
        ]);
    }
}
