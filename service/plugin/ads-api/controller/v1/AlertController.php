<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use plugin\ads_alert\model\AlertRule;
use plugin\ads_alert\model\AlertLog;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use erik\support\CacheService;
use Throwable;

class AlertController
{
    use \erik\support\ControllerTrait;
    /**
     * GET /api/alerts/rules
     * List alert rules with pagination.
     */
        /**
     * @Title("告警规则列表")
     * @Group("告警")
     * @Url("/api/v1/alerts/rules")
     * @Method("GET")
     */
    public function rules(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
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

        $filters = array_intersect_key($request->all(), array_flip(['platform', 'enabled', 'metric']));
        $cacheKey = 'cache:alert_rules:' . $tenantId . ':' . md5(json_encode($filters));

        $items = CacheService::remember($cacheKey, 120, function () use ($query) {
            return $query->orderBy('id', 'desc')->get()->toArray();
        });

        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = max((int) $request->get('page', 1), 1);
        $slice = array_slice($items, ($page - 1) * $perPage, $perPage);

        return ApiResponse::paginated($slice, count($items), $page, $perPage);
    }

    /**
     * POST /api/alerts/rules
     * Create a new alert rule.
     */
        /**
     * @Title("创建告警规则")
     * @Group("告警")
     * @Url("/api/v1/alerts/rules")
     * @Method("POST")
     */
    public function createRule(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);

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
            'webhook_url'     => $request->post('webhook_url'),
            'enabled'         => (int) $request->post('enabled', 1),
        ]);

        CacheService::flush('cache:alert_rules:' . $tenantId);
        return ApiResponse::success($rule, '规则创建成功');
    }

    /**
     * PUT /api/alerts/rules/{id}
     * Update an existing alert rule.
     */
        /**
     * @Title("更新告警规则")
     * @Group("告警")
     * @Url("/api/v1/alerts/rules/{id}")
     * @Method("PUT")
     */
    public function updateRule(Request $request, int $id): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $rule = AlertRule::byTenant($tenantId)->find($id);

        if (!$rule) {
            return ApiResponse::error('规则不存在');
        }

        $validated = $this->validateRule($request, true);
        if ($validated !== true) {
            return ApiResponse::error($validated);
        }

        $data = [];
        foreach (['name', 'metric', 'condition', 'scope', 'platform', 'campaign_id', 'channels', 'webhook_url'] as $field) {
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

        CacheService::flush('cache:alert_rules:' . $tenantId);
        return ApiResponse::success($rule, '规则更新成功');
    }

    /**
     * DELETE /api/alerts/rules/{id}
     * Delete an alert rule.
     */
        /**
     * @Title("删除告警规则")
     * @Group("告警")
     * @Url("/api/v1/alerts/rules/{id}")
     * @Method("DELETE")
     */
    public function deleteRule(Request $request, int $id): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $rule = AlertRule::byTenant($tenantId)->find($id);

        if (!$rule) {
            return ApiResponse::error('规则不存在');
        }

        $rule->delete();

        CacheService::flush('cache:alert_rules:' . $tenantId);
        return ApiResponse::success(null, '规则已删除');
    }

    /**
     * GET /api/alerts/logs
     * List alert logs with pagination and status filter.
     */
        /**
     * @Title("告警记录")
     * @Group("告警")
     * @Url("/api/v1/alerts/logs")
     * @Method("GET")
     */
    public function logs(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
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
        $allowedSorts = ['id', 'name', 'created_at', 'updated_at'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'id';
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
     * POST /api/alerts/logs/{id}/acknowledge
     * Acknowledge an alert log.
     */
        /**
     * @Title("确认告警")
     * @Group("告警")
     * @Url("/api/v1/alerts/logs/{id}/acknowledge")
     * @Method("POST")
     */
    public function acknowledge(Request $request, int $id): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
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
     * GET /api/alerts/unread-count
     * Get count of triggered (unread) alerts.
     */
        /**
     * @Title("未读告警数")
     * @Group("告警")
     * @Url("/api/v1/alerts/unread-count")
     * @Method("GET")
     */
    public function unreadCount(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $count = CacheService::remember('cache:alert_unread:' . $tenantId, 30, fn() => AlertLog::byTenant($tenantId)->triggered()->count());

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

        // webhook_url 可选；提供时校验协议与长度（创建与更新均生效）
        $webhookUrl = $request->post('webhook_url');
        if ($webhookUrl !== null && $webhookUrl !== '') {
            if (mb_strlen($webhookUrl) > 512) {
                return 'webhook_url 不能超过512个字符';
            }
            if (!preg_match('#^https?://#i', $webhookUrl)) {
                return 'webhook_url 仅支持 http/https 协议';
            }
        }

        $threshold = (float) $request->post('threshold');
        if ($threshold < 0) {
            return '阈值不能为负数';
        }

        return true;
    }
}
