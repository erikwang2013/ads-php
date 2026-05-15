<?php
namespace plugin\ads_api\controller;

use plugin\ads_account\model\PlatformAccount;
use Webman\Http\Request;
use app\support\ApiResponse;

class AccountController
{
    public function index(Request $request): \Webman\Http\Response
    {
        $query = PlatformAccount::query()
            ->where('tenant_id', $request->tenantId ?? 1);

        if ($platform = $request->get('platform')) {
            $query->byPlatform($platform);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        return ApiResponse::paginated(
            $paginator->items(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage()
        );
    }

    public function show(int $id): \Webman\Http\Response
    {
        $account = PlatformAccount::findOrFail($id);
        return ApiResponse::success($account);
    }

    public function destroy(int $id): \Webman\Http\Response
    {
        $account = PlatformAccount::findOrFail($id);
        $account->update(['status' => 0]);
        return ApiResponse::success(null, 'Account disabled');
    }

    public function sync(Request $request, int $id): \Webman\Http\Response
    {
        $account = PlatformAccount::findOrFail($id);
        $account->update(['last_sync_at' => now()]);
        return ApiResponse::success(null, 'Sync triggered');
    }
}
