<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\CampaignData;
use plugin\ads_account\model\PlatformAccount;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class CampaignController
{
    use \erik\support\ControllerTrait;

    protected array $allowedSorts = ['id', 'name', 'platform', 'daily_budget', 'status', 'created_at', 'updated_at'];
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $query = DB::table('erik_campaigns')->where('tenant_id', $tenantId);

        if ($platform = $request->get('platform')) $query->where('platform', $platform);
        if ($status = $request->get('status')) $query->where('status', $status);
        if ($keyword = $request->get('keyword')) $query->where('name', 'like', "%{$keyword}%");

        [$items, $total, $page, $perPage] = $this->paginate($request, $query);

        $summary = (array) DB::table('erik_report_metrics')
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

            $id = DB::table('erik_campaigns')->insertGetId([
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
        } catch (Throwable $e) {
            return $this->catchError($e);
        }
    }

    public function show(int $id): \Webman\Http\Response
    {
        $campaign = DB::table('erik_campaigns')->find($id);
        if (!$campaign) {
            return ApiResponse::error('Campaign not found');
        }

        $todayMetrics = DB::table('erik_report_metrics')
            ->where('campaign_id', $id)
            ->where('date', date('Y-m-d'))
            ->first();

        return ApiResponse::success(['campaign' => $campaign, 'today' => $todayMetrics]);
    }

    public function update(Request $request, int $id): \Webman\Http\Response
    {
        $campaign = DB::table('erik_campaigns')->find($id);
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

            DB::table('erik_campaigns')->where('id', $id)->update([
                'name'         => $data->name,
                'daily_budget' => $data->dailyBudget,
                'updated_at'   => now(),
            ]);

            return ApiResponse::success(null, 'Updated');
        } catch (Throwable $e) {
            return $this->catchError($e);
        }
    }

    public function toggle(Request $request, int $id): \Webman\Http\Response
    {
        $campaign = DB::table('erik_campaigns')->find($id);
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

            DB::table('erik_campaigns')->where('id', $id)->update([
                'status'     => $enabled ? 'enabled' : 'paused',
                'updated_at' => now(),
            ]);

            return ApiResponse::success(null, $enabled ? 'Enabled' : 'Paused');
        } catch (Throwable $e) {
            return $this->catchError($e);
        }
    }

    public function batchToggle(Request $request): \Webman\Http\Response
    {
        $ids = $request->post('ids', []);
        $enabled = (bool) $request->post('enabled', true);

        if (empty($ids) || !is_array($ids)) {
            return ApiResponse::error('ids must be a non-empty array');
        }

        $ids = array_map('intval', $ids);
        $campaigns = DB::table('erik_campaigns')->whereIn('id', $ids)->get();
        $accountIds = $campaigns->pluck('platform_account_id')->unique()->toArray();
        $accounts = PlatformAccount::whereIn('id', $accountIds)->get()->keyBy('id');

        $success = 0;
        $failed = count($ids) - count($campaigns);

        foreach ($campaigns as $campaign) {
            try {
                $account = $accounts[$campaign->platform_account_id] ?? null;
                if ($account) {
                    $adapter = AdapterRegistry::get($campaign->platform);
                    if ($adapter) {
                        $adapter->toggleCampaign(
                            $account->access_token,
                            $account->account_id_on_platform,
                            $campaign->platform_campaign_id,
                            $enabled
                        );
                    }
                }

                DB::table('erik_campaigns')->where('id', $campaign->id)->update([
                    'status'     => $enabled ? 'enabled' : 'paused',
                    'updated_at' => now(),
                ]);

                $success++;
            } catch (Throwable $e) {
                $this->logError($e);
                $failed++;
            }
        }

        return ApiResponse::success([
            'success' => $success,
            'failed'  => $failed,
            'total'   => count($ids),
        ], $enabled ? 'Batch enabled' : 'Batch paused');
    }
}
