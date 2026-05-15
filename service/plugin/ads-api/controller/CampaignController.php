<?php
namespace plugin\ads_api\controller;

use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\CampaignData;
use plugin\ads_account\model\PlatformAccount;
use Webman\Http\Request;
use app\support\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;

class CampaignController
{
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $query = DB::table('campaigns')->where('tenant_id', $tenantId);

        if ($platform = $request->get('platform')) {
            $query->where('platform', $platform);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($keyword = $request->get('keyword')) {
            $query->where('name', 'like', "%{$keyword}%");
        }

        $sort = $request->get('sort', 'id');
        $query->orderBy($sort, 'desc');

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        $summary = (array) DB::table('report_metrics')
            ->where('tenant_id', $tenantId)
            ->where('date', date('Y-m-d'))
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(impressions), 0) as total_impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as total_clicks')
            ->selectRaw('COALESCE(AVG(ctr), 0) as avg_ctr')
            ->selectRaw('COALESCE(AVG(cvr), 0) as avg_cvr')
            ->first();

        return ApiResponse::paginated(
            $paginator->items(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
            $summary
        );
    }

    public function store(Request $request): \Webman\Http\Response
    {
        $platform = $request->post('platform');
        $accountId = (int) $request->post('platform_account_id');

        $account = PlatformAccount::findOrFail($accountId);

        $adapter = AdapterRegistry::get($platform);
        if (!$adapter) {
            return ApiResponse::error("Unsupported platform: $platform");
        }

        $data = CampaignData::fromArray($request->post());
        try {
            $platformCampaignId = $adapter->createCampaign(
                $account->access_token,
                $account->account_id_on_platform,
                $data
            );

            $id = DB::table('campaigns')->insertGetId([
                'tenant_id'            => $request->tenantId ?? 1,
                'platform_account_id'  => $accountId,
                'platform'             => $platform,
                'platform_campaign_id' => $platformCampaignId,
                'name'                 => $data->name,
                'daily_budget'         => $data->dailyBudget,
                'total_budget'         => $data->totalBudget ?? 0,
                'status'               => 'enabled',
                'extra'                => json_encode($data->extra, JSON_UNESCAPED_UNICODE),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            return ApiResponse::success(['id' => $id, 'platform_campaign_id' => $platformCampaignId]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function show(int $id): \Webman\Http\Response
    {
        $campaign = DB::table('campaigns')->find($id);
        if (!$campaign) {
            return ApiResponse::error('Campaign not found');
        }

        $todayMetrics = DB::table('report_metrics')
            ->where('campaign_id', $id)
            ->where('date', date('Y-m-d'))
            ->first();

        return ApiResponse::success(['campaign' => $campaign, 'today' => $todayMetrics]);
    }

    public function update(Request $request, int $id): \Webman\Http\Response
    {
        $campaign = DB::table('campaigns')->find($id);
        if (!$campaign) {
            return ApiResponse::error('Campaign not found');
        }

        $account = PlatformAccount::find($campaign->platform_account_id);
        $adapter = AdapterRegistry::get($campaign->platform);
        $data = CampaignData::fromArray($request->post());

        try {
            $adapter->updateCampaign(
                $account->access_token,
                $account->account_id_on_platform,
                $campaign->platform_campaign_id,
                $data
            );

            DB::table('campaigns')->where('id', $id)->update([
                'name'         => $data->name,
                'daily_budget' => $data->dailyBudget,
                'updated_at'   => now(),
            ]);

            return ApiResponse::success(null, 'Updated');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function toggle(Request $request, int $id): \Webman\Http\Response
    {
        $campaign = DB::table('campaigns')->find($id);
        if (!$campaign) {
            return ApiResponse::error('Campaign not found');
        }

        $enabled = (bool) $request->post('enabled', true);
        $account = PlatformAccount::find($campaign->platform_account_id);
        $adapter = AdapterRegistry::get($campaign->platform);

        try {
            $adapter->toggleCampaign(
                $account->access_token,
                $account->account_id_on_platform,
                $campaign->platform_campaign_id,
                $enabled
            );

            DB::table('campaigns')->where('id', $id)->update([
                'status'     => $enabled ? 'enabled' : 'paused',
                'updated_at' => now(),
            ]);

            return ApiResponse::success(null, $enabled ? 'Enabled' : 'Paused');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
