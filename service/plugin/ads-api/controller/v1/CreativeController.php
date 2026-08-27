<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;


class CreativeController
{
    use \erik\support\ControllerTrait;
        /**
     * @Title("创意列表")
     * @Group("创意")
     * @Url("/api/creatives")
     * @Method("GET")
     */
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);

        $query = DB::table('ads_creatives')
            ->join('ads_ad_groups', 'ads_creatives.ad_group_id', '=', 'ads_ad_groups.id')
            ->join('ads_campaigns', 'ads_ad_groups.campaign_id', '=', 'ads_campaigns.id')
            ->where('ads_campaigns.tenant_id', $tenantId)
            ->select(
                'ads_creatives.*',
                'ads_ad_groups.name as ad_group_name',
                'ads_campaigns.platform',
                'ads_campaigns.name as campaign_name'
            );

        if ($platform = $request->get('platform')) {
            $query->where('ads_campaigns.platform', $platform);
        }
        if ($adGroupId = $request->get('ad_group_id')) {
            $query->where('ads_creatives.ad_group_id', (int) $adGroupId);
        }
        if ($campaignId = $request->get('campaign_id')) {
            $query->where('ads_ad_groups.campaign_id', (int) $campaignId);
        }
        if ($mediaType = $request->get('media_type')) {
            $query->where('ads_creatives.media_type', $mediaType);
        }

        $this->allowedSorts = ['id', 'title', 'media_type', 'created_at', 'updated_at'];
        [$items, $total, $page, $perPage] = $this->paginate($request, $query, 'ads_creatives');

        return ApiResponse::paginated(
            $items,
            $total, $page, $perPage
        );
    }

        /**
     * @Title("创意详情")
     * @Group("创意")
     * @Url("/api/creatives/{id}")
     * @Method("GET")
     */
    public function show(int $id): \Webman\Http\Response
    {
        $creative = DB::table('ads_creatives')
            ->join('ads_ad_groups', 'ads_creatives.ad_group_id', '=', 'ads_ad_groups.id')
            ->join('ads_campaigns', 'ads_ad_groups.campaign_id', '=', 'ads_campaigns.id')
            ->where('ads_creatives.id', $id)
            ->select(
                'ads_creatives.*',
                'ads_ad_groups.name as ad_group_name',
                'ads_campaigns.platform',
                'ads_campaigns.name as campaign_name'
            )
            ->first();

        if (!$creative) {
            return ApiResponse::error('Creative not found');
        }

        $todayMetrics = DB::table('ads_report_metrics')
            ->where('creative_id', $id)
            ->where('date', date('Y-m-d'))
            ->first();

        return ApiResponse::success(['creative' => $creative, 'today' => $todayMetrics]);
    }
}
