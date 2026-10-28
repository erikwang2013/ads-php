<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use Webman\Http\Request;
use app\support\ApiResponse;
use erik\support\CacheService;
use Illuminate\Database\Capsule\Manager as DB;


class DashboardController
{
    use \erik\support\ControllerTrait;
        /**
     * @Title("仪表盘汇总")
     * @Group("报表")
     * @Url("/api/v1/reports/summary")
     * @Method("GET")
     */
    public function summary(Request $request): \Webman\Http\Response
    {
        $dateStart = $request->get('date_start', date('Y-m-d'));
        $dateEnd   = $request->get('date_end', date('Y-m-d'));

        $cacheKey = CacheService::dashboardKey($dateStart, $dateEnd);

        $data = CacheService::remember($cacheKey, 300, function () use ($dateStart, $dateEnd) {
            $overview = (array) DB::table('ads_report_metrics')
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
                ->selectRaw('COALESCE(SUM(impressions), 0) as total_impressions')
                ->selectRaw('COALESCE(SUM(clicks), 0) as total_clicks')
                ->selectRaw('COALESCE(SUM(conversions), 0) as total_conversions')
                ->first();

            $overview['avg_ctr'] = bcmul(bc_div($overview['total_clicks'], $overview['total_impressions'], 4), '100', 2);
            $overview['avg_cvr'] = bcmul(bc_div($overview['total_conversions'], $overview['total_clicks'], 4), '100', 2);
            $overview['avg_cpc'] = bc_div($overview['total_cost'], $overview['total_clicks']);
            $overview['avg_cpa'] = bc_div($overview['total_cost'], $overview['total_conversions']);

            $byPlatform = DB::table('ads_report_metrics')
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->groupBy('platform')
                ->select('platform')
                ->selectRaw('COALESCE(SUM(cost), 0) as cost')
                ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
                ->selectRaw('COALESCE(SUM(clicks), 0) as clicks')
                ->selectRaw('COALESCE(SUM(conversions), 0) as conversions')
                ->orderByDesc('cost')
                ->get();

            foreach ($byPlatform as $row) {
                $row->ctr = bcmul(bc_div($row->clicks, $row->impressions, 4), '100', 2);
                $row->cvr = bcmul(bc_div($row->conversions, $row->clicks, 4), '100', 2);
                $row->cpc = bc_div($row->cost, $row->clicks);
            }

            $daily = DB::table('ads_report_metrics')
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->groupBy('date', 'platform')
                ->orderBy('date')
                ->select('date', 'platform')
                ->selectRaw('COALESCE(SUM(cost), 0) as cost')
                ->selectRaw('COALESCE(SUM(impressions), 0) as impressions')
                ->selectRaw('COALESCE(SUM(clicks), 0) as clicks')
                ->selectRaw('COALESCE(SUM(conversions), 0) as conversions')
                ->get();

            return [
                'overview'    => $overview,
                'by_platform' => $byPlatform,
                'daily'       => $daily,
            ];
        });

        return ApiResponse::success($data);
    }
}
