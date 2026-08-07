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

class AccountController
{
    use \erik\support\ControllerTrait;
        /**
     * @Title("账户列表")
     * @Group("账户")
     * @Url("/api/accounts")
     * @Method("GET")
     */
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $filters = array_intersect_key($request->all(), array_flip(['platform', 'status']));
        $cacheKey = 'cache:accounts:' . $tenantId . ':' . md5(json_encode($filters));

        $items = CacheService::remember($cacheKey, 300, function () use ($request, $tenantId) {
            $query = PlatformAccount::query()->where('tenant_id', $tenantId);
            if ($platform = $request->get('platform')) $query->byPlatform($platform);
            return $query->get()->toArray();
        });

        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = max((int) $request->get('page', 1), 1);
        $slice = array_slice($items, ($page - 1) * $perPage, $perPage);

        return ApiResponse::paginated($slice, count($items), $page, $perPage);
    }

        /**
     * @Title("账户详情")
     * @Group("账户")
     * @Url("/api/accounts/{id}")
     * @Method("GET")
     */
    public function show(int $id): \Webman\Http\Response
    {
        $account = CacheService::remember('cache:accounts:show:' . $id, 300, fn() => PlatformAccount::findOrFail($id));
        return ApiResponse::success($account);
    }

        /**
     * @Title("解绑账户")
     * @Group("账户")
     * @Url("/api/accounts/{id}")
     * @Method("DELETE")
     */
    public function destroy(int $id): \Webman\Http\Response
    {
        $account = PlatformAccount::findOrFail($id);
        $account->update(['status' => 0]);
        CacheService::forget('cache:accounts:show:' . $id);
        return ApiResponse::success(null, 'Account disabled');
    }

        /**
     * @Title("手动同步")
     * @Group("账户")
     * @Url("/api/accounts/{id}/sync")
     * @Method("POST")
     */
    public function sync(Request $request, int $id): \Webman\Http\Response
    {
        $account = PlatformAccount::findOrFail($id);
        $account->update(['last_sync_at' => now()]);
        CacheService::forget('cache:accounts:show:' . $id);
        return ApiResponse::success(null, 'Sync triggered');
    }
}
