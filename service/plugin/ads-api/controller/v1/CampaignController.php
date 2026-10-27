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
        $this->allowedSorts = ['id', 'name', 'platform', 'daily_budget', 'status', 'created_at', 'updated_at', 'cost'];
        $tenantId = $this->tenantId($request);
        $query = DB::table('ads_campaigns as c')
            ->where('c.tenant_id', $tenantId)
            ->leftJoinSub(
                DB::table('ads_report_metrics')
                    ->where('tenant_id', $tenantId)
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
            ->where('tenant_id', $tenantId)
            ->where('date', date('Y-m-d'))
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as total_impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as total_clicks')
            ->selectRaw('COALESCE(AVG(ctr), 0) as avg_ctr')
            ->selectRaw('COALESCE(AVG(cvr), 0) as avg_cvr')
            ->first();

        return ApiResponse::paginated($items, $total, $page, $perPage, $summary);
    }
}
