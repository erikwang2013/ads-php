<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use plugin\ads_tenant\service\QuotaService;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;

class TenantController
{
    use \erik\support\ControllerTrait;

        /**
     * @Title("租户配额")
     * @Group("租户")
     * @Url("/api/v1/tenant/quota")
     * @Method("GET")
     *
     * 返回当前租户的版本线、配额上限与实时用量。
     */
    public function quota(Request $request): \Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $service = new QuotaService();

        $tier = $service->tierForTenant($tenantId);

        return ApiResponse::success([
            'plan'   => $tier,
            'limits' => QuotaService::limitsFor($tier),
            'usage'  => $service->usage($tenantId),
        ]);
    }
}
