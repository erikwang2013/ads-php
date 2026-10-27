<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;

class SyncController
{
    use \erik\support\ControllerTrait;

        /**
     * @Title("同步状态摘要")
     * @Group("同步")
     * @Url("/api/v1/sync/status")
     * @Method("GET")
     *
     * 账户维度同步状态 + 整体摘要。
     * 数据源：ads_platform_accounts（last_sync_at / sync_enabled）+ ads_sync_errors。
     */
    public function status(Request $request): \Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $now = now();

        $accounts = DB::table('ads_platform_accounts')
            ->where('tenant_id', $tenantId)
            ->where('sync_enabled', 1)
            ->orderBy('id')
            ->get(['id', 'account_name', 'platform', 'last_sync_at']);

        // 批量统计，避免逐账户 N+1 查询
        $sevenDaysAgo = $now->copy()->subDays(7);
        $errorCounts = [];
        $pendingCounts = [];

        $accountIds = $accounts->pluck('id')->all();
        if ($accountIds) {
            $errorCounts = DB::table('ads_sync_errors')
                ->whereIn('platform_account_id', $accountIds)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->groupBy('platform_account_id')
                ->selectRaw('platform_account_id, COUNT(*) as cnt')
                ->pluck('cnt', 'platform_account_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $pendingCounts = DB::table('ads_sync_errors')
                ->whereIn('platform_account_id', $accountIds)
                ->where('retry_count', '<', 3)
                ->where('next_retry_at', '<=', $now)
                ->groupBy('platform_account_id')
                ->selectRaw('platform_account_id, COUNT(*) as cnt')
                ->pluck('cnt', 'platform_account_id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        $list = [];
        foreach ($accounts as $account) {
            $list[] = [
                'id'                => (int) $account->id,
                'account_name'      => $account->account_name,
                'platform'          => $account->platform,
                'last_sync_at'      => $account->last_sync_at,
                'sync_errors_count' => $errorCounts[$account->id] ?? 0,
                'pending_retries'   => $pendingCounts[$account->id] ?? 0,
            ];
        }

        // 24 小时内同步过的账户数（last_sync_at >= now-24h）
        $since24h = $now->copy()->subDay()->getTimestamp();
        $synced24h = $accounts->filter(function ($a) use ($since24h) {
            return $a->last_sync_at !== null
                && strtotime((string) $a->last_sync_at) >= $since24h;
        })->count();

        $summary = [
            'total_accounts'  => count($list),
            'synced_24h'      => $synced24h,
            'error_7d'        => array_sum($errorCounts),
            'pending_retries' => array_sum($pendingCounts),
        ];

        return ApiResponse::success(['accounts' => $list, 'summary' => $summary]);
    }

        /**
     * @Title("同步错误列表")
     * @Group("同步")
     * @Url("/api/v1/sync/errors")
     * @Method("GET")
     *
     * 分页返回 ads_sync_errors，join ads_platform_accounts 取 account_name，
     * 并按账户 tenant_id 隔离。
     */
    public function errors(Request $request): \Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $this->allowedSorts = ['id', 'retry_count', 'next_retry_at', 'created_at'];

        $query = DB::table('ads_sync_errors')
            ->join('ads_platform_accounts as a', 'a.id', '=', 'ads_sync_errors.platform_account_id')
            ->where('a.tenant_id', $tenantId)
            ->select(
                'ads_sync_errors.id',
                'ads_sync_errors.platform_account_id',
                'ads_sync_errors.platform',
                'ads_sync_errors.error_message',
                'ads_sync_errors.retry_count',
                'ads_sync_errors.last_error',
                'ads_sync_errors.next_retry_at',
                'ads_sync_errors.created_at',
                'a.account_name',
            );

        if ($platform = $request->get('platform')) {
            $query->where('ads_sync_errors.platform', $platform);
        }

        [$items, $total, $page, $perPage] = $this->paginate($request, $query, 'ads_sync_errors');

        return ApiResponse::paginated($items, $total, $page, $perPage);
    }
}
