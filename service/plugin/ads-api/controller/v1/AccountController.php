<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use plugin\ads_account\model\PlatformAccount;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use erik\support\CacheService;
use Throwable;

use \erik\support\ControllerTrait;

class AccountController
{
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $cacheKey = 'cache:accounts:' . $tenantId . ':' . md5(json_encode($request->all()));

        $result = CacheService::remember($cacheKey, 300, function () use ($request, $tenantId) {
            $query = PlatformAccount::query()->where('tenant_id', $tenantId);
            if ($platform = $request->get('platform')) $query->byPlatform($platform);
            $perPage = min((int) $request->get('per_page', 20), 100);
            $paginator = $query->paginate($perPage);
            return [$paginator->items(), $paginator->total(), $paginator->currentPage(), $paginator->perPage()];
        });

        return ApiResponse::paginated($result[0], $result[1], $result[2], $result[3]);
    }

    public function show(int $id): \Webman\Http\Response
    {
        $account = CacheService::remember('cache:accounts:show:' . $id, 300, fn() => PlatformAccount::findOrFail($id));
        return ApiResponse::success($account);
    }

    public function destroy(int $id): \Webman\Http\Response
    {
        $account = PlatformAccount::findOrFail($id);
        $account->update(['status' => 0]);
        CacheService::forget('cache:accounts:show:' . $id);
        return ApiResponse::success(null, 'Account disabled');
    }

    public function sync(Request $request, int $id): \Webman\Http\Response
    {
        $account = PlatformAccount::findOrFail($id);
        $account->update(['last_sync_at' => now()]);
        CacheService::forget('cache:accounts:show:' . $id);
        return ApiResponse::success(null, 'Sync triggered');
    }
}
