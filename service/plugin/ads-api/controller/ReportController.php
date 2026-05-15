<?php
namespace plugin\ads_api\controller;

use plugin\ads_report\service\ReportBuilder;
use Webman\Http\Request;
use app\support\ApiResponse;

class ReportController
{
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
