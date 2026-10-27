<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\controller\v1;

use plugin\ads_platform\model\TargetingTemplate;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;

class TargetingTemplateController
{
    use \erik\support\ControllerTrait;

        /**
     * @Title("模板列表")
     * @Group("定向模板")
     * @Url("/api/v1/targeting-templates")
     * @Method("GET")
     */
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $query = TargetingTemplate::byTenant($tenantId);
        if ($platform = $request->get('platform')) $query->where('platform', $platform);

        $this->allowedSorts = ['id', 'name', 'platform', 'created_at'];
        [$items, $total, $page, $perPage] = $this->paginate($request, $query);

        return ApiResponse::paginated($items, $total, $page, $perPage);
    }

        /**
     * @Title("模板详情")
     * @Group("定向模板")
     * @Url("/api/v1/targeting-templates/{id}")
     * @Method("GET")
     */
    public function show(int $id): \Webman\Http\Response
    {
        $template = TargetingTemplate::find($id);
        if (!$template) return ApiResponse::error('模板不存在');
        return ApiResponse::success($template);
    }

        /**
     * @Title("创建模板")
     * @Group("定向模板")
     * @Url("/api/v1/targeting-templates")
     * @Method("POST")
     */
    public function store(Request $request): \Webman\Http\Response
    {
        $template = TargetingTemplate::create([
            'tenant_id'  => $request->tenantId ?? 1,
            'name'       => $request->post('name'),
            'platform'   => $request->post('platform', ''),
            'targeting'  => $request->post('targeting', []),
            'is_shared'  => (int) $request->post('is_shared', 0),
        ]);

        return ApiResponse::success($template, '模板创建成功');
    }

        /**
     * @Title("更新模板")
     * @Group("定向模板")
     * @Url("/api/v1/targeting-templates/{id}")
     * @Method("PUT")
     */
    public function update(Request $request, int $id): \Webman\Http\Response
    {
        $template = TargetingTemplate::find($id);
        if (!$template) return ApiResponse::error('模板不存在');

        $data = [];
        foreach (['name', 'platform'] as $f) { if ($request->post($f) !== null) $data[$f] = $request->post($f); }
        if ($request->post('targeting') !== null) $data['targeting'] = $request->post('targeting');
        if ($request->post('is_shared') !== null) $data['is_shared'] = (int) $request->post('is_shared');

        $template->update($data);
        return ApiResponse::success($template, '模板更新成功');
    }

        /**
     * @Title("删除模板")
     * @Group("定向模板")
     * @Url("/api/v1/targeting-templates/{id}")
     * @Method("DELETE")
     */
    public function destroy(int $id): \Webman\Http\Response
    {
        $template = TargetingTemplate::find($id);
        if (!$template) return ApiResponse::error('模板不存在');
        $template->delete();
        return ApiResponse::success(null, '模板已删除');
    }
}
