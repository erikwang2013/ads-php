<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use Webman\Http\Request;
use app\support\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;

class CampaignController
{
    use \erik\support\ControllerTrait;

    /**
     * @Title("计划列表")
     * @Group("广告计划")
     * @Url("/api/v1/campaigns")
     * @Method("GET")
     */
    public function index(Request $request): \Webman\Http\Response
    {
        $this->allowedSorts = ['id', 'name', 'platform', 'daily_budget', 'status', 'created_at', 'updated_at', 'total_cost'];
        // ads_campaigns 无 cost 列，sort=cost 映射到 join 子查询的 total_cost 别名
        if ($request->get('sort') === 'cost') {
            $request->setGet('sort', 'total_cost');
        }
        $query = DB::table('ads_campaigns as c')
            ->leftJoinSub(
                DB::table('ads_report_metrics')
                    ->where('date', date('Y-m-d'))
                    ->select('campaign_id')
                    ->selectRaw('COALESCE(SUM(cost), 0) as cost')
                    ->groupBy('campaign_id'),
                'm',
                'm.campaign_id',
                '=',
                'c.id'
            )
            ->select('c.*')
            ->selectRaw('COALESCE(m.cost, 0) as total_cost');

        if ($platform = $request->get('platform')) $query->where('platform', $platform);
        if ($status = $request->get('status')) $query->where('status', $status);
        if ($keyword = $request->get('keyword')) {
            $keyword = addcslashes((string) $keyword, '%_\\');
            $query->where('name', 'like', "%{$keyword}%");
        }

        [$items, $total, $page, $perPage] = $this->paginate($request, $query);

        $summary = (array) DB::table('ads_report_metrics')
            ->where('date', date('Y-m-d'))
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as total_impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as total_clicks')
            ->selectRaw('COALESCE(SUM(conversions), 0) as total_conversions')
            ->first();

        $summary['avg_ctr'] = bcmul(bc_div($summary['total_clicks'], $summary['total_impressions'], 4), '100', 2);
        $summary['avg_cvr'] = bcmul(bc_div($summary['total_conversions'], $summary['total_clicks'], 4), '100', 2);

        return ApiResponse::paginated($items, $total, $page, $perPage, $summary);
    }
}
