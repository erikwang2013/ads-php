<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use plugin\ads_report\service\ReportBuilder;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;

class ReportController
{
        /**
     * @Title("自定义报表")
     * @Group("报表")
     * @Url("/api/v1/reports/custom")
     * @Method("GET")
     */
    public function custom(Request $request): \Webman\Http\Response
    {
        $builder = new ReportBuilder();
        $result = $builder->buildCustom(
            $request->tenantId ?? 1,
            $request->all()
        );
        return ApiResponse::success($result);
    }
}
