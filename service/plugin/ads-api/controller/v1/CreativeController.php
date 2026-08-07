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

        $query = DB::table('erik_creatives')
            ->join('erik_ad_groups', 'erik_creatives.ad_group_id', '=', 'erik_ad_groups.id')
            ->join('erik_campaigns', 'erik_ad_groups.campaign_id', '=', 'erik_campaigns.id')
            ->where('erik_campaigns.tenant_id', $tenantId)
            ->select(
                'erik_creatives.*',
                'erik_ad_groups.name as ad_group_name',
                'erik_campaigns.platform',
                'erik_campaigns.name as campaign_name'
            );

        if ($platform = $request->get('platform')) {
            $query->where('erik_campaigns.platform', $platform);
        }
        if ($adGroupId = $request->get('ad_group_id')) {
            $query->where('erik_creatives.ad_group_id', (int) $adGroupId);
        }
        if ($campaignId = $request->get('campaign_id')) {
            $query->where('erik_ad_groups.campaign_id', (int) $campaignId);
        }
        if ($mediaType = $request->get('media_type')) {
            $query->where('erik_creatives.media_type', $mediaType);
        }

        $this->allowedSorts = ['id', 'title', 'media_type', 'created_at', 'updated_at'];
        [$items, $total, $page, $perPage] = $this->paginate($request, $query, 'erik_creatives');

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
        $creative = DB::table('erik_creatives')
            ->join('erik_ad_groups', 'erik_creatives.ad_group_id', '=', 'erik_ad_groups.id')
            ->join('erik_campaigns', 'erik_ad_groups.campaign_id', '=', 'erik_campaigns.id')
            ->where('erik_creatives.id', $id)
            ->select(
                'erik_creatives.*',
                'erik_ad_groups.name as ad_group_name',
                'erik_campaigns.platform',
                'erik_campaigns.name as campaign_name'
            )
            ->first();

        if (!$creative) {
            return ApiResponse::error('Creative not found');
        }

        $todayMetrics = DB::table('erik_report_metrics')
            ->where('creative_id', $id)
            ->where('date', date('Y-m-d'))
            ->first();

        return ApiResponse::success(['creative' => $creative, 'today' => $todayMetrics]);
    }
}
