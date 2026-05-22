<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\controller\v1;

use plugin\ads_platform\model\BidRule;
use plugin\ads_platform\model\BidLog;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;

class BidRuleController
{
    use \erik\support\ControllerTrait;

        /**
     * @Title("出价规则列表")
     * @Group("自动出价")
     * @Url("/api/bid-rules")
     * @Method("GET")
     */
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $query = BidRule::where('tenant_id', $tenantId);

        if ($platform = $request->get('platform')) $query->where('platform', $platform);
        if ($request->get('enabled') !== null) $query->where('enabled', (int) $request->get('enabled'));

        $this->allowedSorts = ['id', 'name', 'metric', 'created_at', 'updated_at'];
        [$items, $total, $page, $perPage] = $this->paginate($request, $query);

        return ApiResponse::paginated($items, $total, $page, $perPage);
    }

        /**
     * @Title("创建出价规则")
     * @Group("自动出价")
     * @Url("/api/bid-rules")
     * @Method("POST")
     */
    public function store(Request $request): \Webman\Http\Response
    {
        $rule = BidRule::create([
            'tenant_id'        => $request->tenantId ?? 1,
            'name'             => $request->post('name'),
            'metric'           => $request->post('metric'),
            'condition'        => $request->post('condition'),
            'threshold'        => (float) $request->post('threshold'),
            'scope'            => $request->post('scope', 'tenant'),
            'platform'         => $request->post('platform'),
            'campaign_id'      => $request->post('campaign_id'),
            'action_type'      => $request->post('action_type'),
            'adjust_step'      => (int) $request->post('adjust_step', 0),
            'budget_min'       => (int) $request->post('budget_min', 0),
            'budget_max'       => (int) $request->post('budget_max', 0),
            'cooldown_minutes' => (int) $request->post('cooldown_minutes', 60),
            'enabled'          => (int) $request->post('enabled', 1),
        ]);

        return ApiResponse::success($rule, '规则创建成功');
    }

        /**
     * @Title("更新出价规则")
     * @Group("自动出价")
     * @Url("/api/bid-rules/{id}")
     * @Method("PUT")
     */
    public function update(Request $request, int $id): \Webman\Http\Response
    {
        $rule = BidRule::find($id);
        if (!$rule) return ApiResponse::error('规则不存在');

        $fields = ['name', 'metric', 'condition', 'scope', 'platform', 'action_type'];
        $data = [];
        foreach ($fields as $f) { if ($request->post($f) !== null) $data[$f] = $request->post($f); }
        if ($request->post('threshold') !== null) $data['threshold'] = (float) $request->post('threshold');
        if ($request->post('campaign_id') !== null) $data['campaign_id'] = (int) $request->post('campaign_id');
        if ($request->post('adjust_step') !== null) $data['adjust_step'] = (int) $request->post('adjust_step');
        if ($request->post('budget_min') !== null) $data['budget_min'] = (int) $request->post('budget_min');
        if ($request->post('budget_max') !== null) $data['budget_max'] = (int) $request->post('budget_max');
        if ($request->post('cooldown_minutes') !== null) $data['cooldown_minutes'] = (int) $request->post('cooldown_minutes');
        if ($request->post('enabled') !== null) $data['enabled'] = (int) $request->post('enabled');

        $rule->update($data);
        return ApiResponse::success($rule, '规则更新成功');
    }

        /**
     * @Title("删除出价规则")
     * @Group("自动出价")
     * @Url("/api/bid-rules/{id}")
     * @Method("DELETE")
     */
    public function destroy(int $id): \Webman\Http\Response
    {
        $rule = BidRule::find($id);
        if (!$rule) return ApiResponse::error('规则不存在');
        $rule->delete();
        return ApiResponse::success(null, '规则已删除');
    }

        /**
     * @Title("出价历史")
     * @Group("自动出价")
     * @Url("/api/bid-rules/logs")
     * @Method("GET")
     */
    public function logs(Request $request): \Webman\Http\Response
    {
        $query = BidLog::where('tenant_id', $request->tenantId ?? 1);
        if ($ruleId = $request->get('rule_id')) $query->where('rule_id', (int) $ruleId);
        if ($campaignId = $request->get('campaign_id')) $query->where('campaign_id', (int) $campaignId);

        $this->allowedSorts = ['id', 'created_at'];
        [$items, $total, $page, $perPage] = $this->paginate($request, $query);

        return ApiResponse::paginated($items, $total, $page, $perPage);
    }
}
