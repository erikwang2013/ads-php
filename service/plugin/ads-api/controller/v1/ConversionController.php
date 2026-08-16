<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use plugin\ads_report\service\ConversionService;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use InvalidArgumentException;
use Throwable;

class ConversionController
{
    use \erik\support\ControllerTrait;

        /**
     * @Title("转化回传")
     * @Group("转化")
     * @Url("/api/conversions")
     * @Method("POST")
     */
    public function store(Request $request): \Webman\Http\Response
    {
        try {
            $conversion = (new ConversionService())->create($request->tenantId ?? 1, $request->post());
            return ApiResponse::success($conversion, 'Conversion recorded');
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            return $this->catchError($e);
        }
    }

        /**
     * @Title("转化列表")
     * @Group("转化")
     * @Url("/api/conversions")
     * @Method("GET")
     */
    public function index(Request $request): \Webman\Http\Response
    {
        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = max((int) $request->get('page', 1), 1);

        $filters = array_intersect_key(
            $request->get(),
            array_flip(['platform', 'campaign_id', 'date_start', 'date_end'])
        );

        [$items, $total, $currentPage, $currentPerPage] = (new ConversionService())->search(
            $request->tenantId ?? 1,
            $filters,
            $page,
            $perPage,
        );

        return ApiResponse::paginated($items, $total, $currentPage, $currentPerPage);
    }
}
