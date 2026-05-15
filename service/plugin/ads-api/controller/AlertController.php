<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller;

use plugin\ads_alert\model\AlertRule;
use plugin\ads_alert\model\AlertLog;
use Webman\Http\Request;
use app\support\ApiResponse;

class AlertController
{
    /**
     * GET /api/v1/alerts/rules
     * List alert rules with pagination.
     */
    public function rules(Request $request): Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $query = AlertRule::byTenant($tenantId);

        if ($platform = $request->get('platform')) {
            $query->where('platform', $platform);
        }
        if ($request->get('enabled') !== null) {
            $query->where('enabled', (int) $request->get('enabled'));
        }
        if ($metric = $request->get('metric')) {
            $query->where('metric', $metric);
        }

        $sort = $request->get('sort', 'id');
        $query->orderBy($sort, 'desc');

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        return ApiResponse::paginated(
            $paginator->items(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage()
        );
    }

    /**
     * POST /api/v1/alerts/rules
     * Create a new alert rule.
     */
    public function createRule(Request $request): Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;

        $validated = $this->validateRule($request);
        if ($validated !== true) {
            return ApiResponse::error($validated);
        }

        $rule = AlertRule::create([
            'tenant_id'       => $tenantId,
            'name'            => $request->post('name'),
            'metric'          => $request->post('metric'),
            'condition'       => $request->post('condition'),
            'threshold'       => (float) $request->post('threshold'),
            'scope'           => $request->post('scope', 'tenant'),
            'platform'        => $request->post('platform'),
            'campaign_id'     => $request->post('campaign_id'),
            'check_interval'  => (int) $request->post('check_interval', 5),
            'channels'        => $request->post('channels', ['web']),
            'enabled'         => (int) $request->post('enabled', 1),
        ]);

        return ApiResponse::success($rule, '规则创建成功');
    }

    /**
     * PUT /api/v1/alerts/rules/{id}
     * Update an existing alert rule.
     */
    public function updateRule(Request $request, int $id): Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $rule = AlertRule::byTenant($tenantId)->find($id);

        if (!$rule) {
            return ApiResponse::error('规则不存在');
        }

        $validated = $this->validateRule($request, true);
        if ($validated !== true) {
            return ApiResponse::error($validated);
        }

        $data = [];
        foreach (['name', 'metric', 'condition', 'scope', 'platform', 'campaign_id', 'channels'] as $field) {
            if ($request->post($field) !== null) {
                $data[$field] = $request->post($field);
            }
        }
        if ($request->post('threshold') !== null) {
            $data['threshold'] = (float) $request->post('threshold');
        }
        if ($request->post('check_interval') !== null) {
            $data['check_interval'] = (int) $request->post('check_interval');
        }
        if ($request->post('enabled') !== null) {
            $data['enabled'] = (int) $request->post('enabled');
        }

        $rule->update($data);

        return ApiResponse::success($rule, '规则更新成功');
    }

    /**
     * DELETE /api/v1/alerts/rules/{id}
     * Delete an alert rule.
     */
    public function deleteRule(Request $request, int $id): Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $rule = AlertRule::byTenant($tenantId)->find($id);

        if (!$rule) {
            return ApiResponse::error('规则不存在');
        }

        $rule->delete();

        return ApiResponse::success(null, '规则已删除');
    }

    /**
     * GET /api/v1/alerts/logs
     * List alert logs with pagination and status filter.
     */
    public function logs(Request $request): Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $query = AlertLog::byTenant($tenantId);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($ruleId = $request->get('rule_id')) {
            $query->where('rule_id', (int) $ruleId);
        }
        if ($metric = $request->get('metric')) {
            $query->where('metric', $metric);
        }

        $sort = $request->get('sort', 'id');
        $query->orderBy($sort, 'desc');

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        return ApiResponse::paginated(
            $paginator->items(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage()
        );
    }

    /**
     * POST /api/v1/alerts/logs/{id}/acknowledge
     * Acknowledge an alert log.
     */
    public function acknowledge(Request $request, int $id): Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $log = AlertLog::byTenant($tenantId)->find($id);

        if (!$log) {
            return ApiResponse::error('告警记录不存在');
        }

        if ($log->status !== 'triggered') {
            return ApiResponse::error('该告警已处理');
        }

        $log->markAcknowledged();

        return ApiResponse::success($log, '已确认');
    }

    /**
     * GET /api/v1/alerts/unread-count
     * Get count of triggered (unread) alerts.
     */
    public function unreadCount(Request $request): Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $count = AlertLog::byTenant($tenantId)->triggered()->count();

        return ApiResponse::success(['count' => $count]);
    }

    /**
     * Validate rule input. Returns true if valid, or error message string.
     */
    protected function validateRule(Request $request, bool $isUpdate = false): true|string
    {
        $validMetrics = ['cost', 'impressions', 'clicks', 'conversions', 'ctr', 'cvr', 'roi'];
        $validConditions = ['gt', 'gte', 'lt', 'lte'];
        $validScopes = ['tenant', 'platform', 'campaign'];

        $metric = $request->post('metric');
        $condition = $request->post('condition');
        $scope = $request->post('scope', 'tenant');

        if (!$isUpdate) {
            if (empty($request->post('name')) || mb_strlen($request->post('name')) > 100) {
                return '规则名称不能为空且不超过100个字符';
            }
            if (empty($metric) || !in_array($metric, $validMetrics)) {
                return '无效的指标类型';
            }
            if (empty($condition) || !in_array($condition, $validConditions)) {
                return '无效的条件';
            }
            if ($request->post('threshold') === null || $request->post('threshold') === '') {
                return '阈值不能为空';
            }
        } else {
            if ($metric !== null && !in_array($metric, $validMetrics)) {
                return '无效的指标类型';
            }
            if ($condition !== null && !in_array($condition, $validConditions)) {
                return '无效的条件';
            }
        }

        if (!in_array($scope, $validScopes)) {
            return '无效的范围类型';
        }

        if ($scope === 'platform' && empty($request->post('platform'))) {
            return '平台范围须指定 platform';
        }

        if ($scope === 'campaign' && empty($request->post('campaign_id'))) {
            return '计划范围须指定 campaign_id';
        }

        $threshold = (float) $request->post('threshold');
        if ($threshold < 0) {
            return '阈值不能为负数';
        }

        return true;
    }
}
