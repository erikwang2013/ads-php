<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\model\TargetingTemplate;
use plugin\ads_account\model\PlatformAccount;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;


use \erik\support\ControllerTrait;

class AdGroupController
{
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);

        $query = DB::table('erik_ad_groups')
            ->join('erik_campaigns', 'erik_ad_groups.campaign_id', '=', 'erik_campaigns.id')
            ->where('erik_campaigns.tenant_id', $tenantId)
            ->select('erik_ad_groups.*', 'erik_campaigns.platform', 'erik_campaigns.name as campaign_name');

        if ($platform = $request->get('platform')) {
            $query->where('erik_campaigns.platform', $platform);
        }
        if ($campaignId = $request->get('campaign_id')) {
            $query->where('erik_ad_groups.campaign_id', (int) $campaignId);
        }
        if ($status = $request->get('status')) {
            $query->where('erik_ad_groups.status', $status);
        }

        $this->allowedSorts = ['id', 'name', 'status', 'bid_amount', 'created_at', 'updated_at'];
        [$items, $total, $page, $perPage] = $this->paginate($request, $query, 'erik_ad_groups');

        return ApiResponse::paginated($items, $total, $page, $perPage);
    }

    public function show(int $id): \Webman\Http\Response
    {
        $adGroup = DB::table('erik_ad_groups')
            ->join('erik_campaigns', 'erik_ad_groups.campaign_id', '=', 'erik_campaigns.id')
            ->where('erik_ad_groups.id', $id)
            ->select('erik_ad_groups.*', 'erik_campaigns.platform', 'erik_campaigns.name as campaign_name')
            ->first();

        if (!$adGroup) {
            return ApiResponse::error('Ad group not found');
        }

        $todayMetrics = DB::table('erik_report_metrics')
            ->where('ad_group_id', $id)
            ->where('date', date('Y-m-d'))
            ->first();

        return ApiResponse::success(['ad_group' => $adGroup, 'today' => $todayMetrics]);
    }

    public function store(Request $request): \Webman\Http\Response
    {
        $campaignId = (int) $request->post('campaign_id');
        $campaign = DB::table('erik_campaigns')->find($campaignId);
        if (!$campaign) {
            return ApiResponse::error('Campaign not found');
        }

        $targeting = $request->post('targeting', []);
        if ($templateId = $request->post('targeting_template_id')) {
            $template = TargetingTemplate::find((int) $templateId);
            if ($template) {
                $targeting = array_merge($template->targeting, $targeting);
            }
        }

        $account = PlatformAccount::find($campaign->platform_account_id);
        $adapter = AdapterRegistry::get($campaign->platform);
        if (!$adapter) {
            return ApiResponse::error("Unsupported platform: {$campaign->platform}");
        }

        try {
            $platformAdGroupId = $adapter->createAdGroup(
                $account->access_token,
                $account->account_id_on_platform,
                $campaign->platform_campaign_id,
                $request->post()
            );

            $id = DB::table('erik_ad_groups')->insertGetId([
                'campaign_id'         => $campaignId,
                'platform_adgroup_id' => $platformAdGroupId,
                'name'                => $request->post('name', ''),
                'status'              => 'enabled',
                'bid_amount'          => (int) $request->post('bid_amount', 0),
                'bid_type'            => $request->post('bid_type', ''),
                'targeting'           => json_encode($targeting, JSON_UNESCAPED_UNICODE),
                'extra'               => json_encode($request->post('extra', []), JSON_UNESCAPED_UNICODE),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            return ApiResponse::success(['id' => $id, 'platform_adgroup_id' => $platformAdGroupId]);
        } catch (Throwable $e) {
            return $this->catchError($e);
        }
    }

    public function update(Request $request, int $id): \Webman\Http\Response
    {
        $adGroup = DB::table('erik_ad_groups')->find($id);
        if (!$adGroup) {
            return ApiResponse::error('Ad group not found');
        }

        $targeting = $request->post('targeting');
        if ($templateId = $request->post('targeting_template_id')) {
            $template = TargetingTemplate::find((int) $templateId);
            if ($template) {
                $targeting = array_merge($template->targeting, $targeting ?? []);
            }
        }

        $campaign = DB::table('erik_campaigns')->find($adGroup->campaign_id);
        $account = PlatformAccount::find($campaign->platform_account_id);
        $adapter = AdapterRegistry::get($campaign->platform);

        try {
            $adapter->updateAdGroup(
                $account->access_token,
                $account->account_id_on_platform,
                $campaign->platform_campaign_id,
                $adGroup->platform_adgroup_id,
                $request->post()
            );

            $updates = ['updated_at' => now()];
            if ($request->post('name') !== null) $updates['name'] = $request->post('name');
            if ($request->post('bid_amount') !== null) $updates['bid_amount'] = (int) $request->post('bid_amount');
            if ($request->post('bid_type') !== null) $updates['bid_type'] = $request->post('bid_type');
            if ($request->post('targeting') !== null) $updates['targeting'] = json_encode($request->post('targeting'), JSON_UNESCAPED_UNICODE);

            DB::table('erik_ad_groups')->where('id', $id)->update($updates);

            return ApiResponse::success(null, 'Updated');
        } catch (Throwable $e) {
            return $this->catchError($e);
        }
    }

    public function toggle(Request $request, int $id): \Webman\Http\Response
    {
        $adGroup = DB::table('erik_ad_groups')->find($id);
        if (!$adGroup) {
            return ApiResponse::error('Ad group not found');
        }

        $enabled = (bool) $request->post('enabled', true);
        $campaign = DB::table('erik_campaigns')->find($adGroup->campaign_id);
        $account = PlatformAccount::find($campaign->platform_account_id);
        $adapter = AdapterRegistry::get($campaign->platform);

        try {
            $adapter->toggleAdGroup(
                $account->access_token,
                $account->account_id_on_platform,
                $campaign->platform_campaign_id,
                $adGroup->platform_adgroup_id,
                $enabled
            );

            DB::table('erik_ad_groups')->where('id', $id)->update([
                'status'     => $enabled ? 'enabled' : 'paused',
                'updated_at' => now(),
            ]);

            return ApiResponse::success(null, $enabled ? 'Enabled' : 'Paused');
        } catch (Throwable $e) {
            return $this->catchError($e);
        }
    }
}
