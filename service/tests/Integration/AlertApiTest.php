<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 告警：规则 CRUD/校验、告警记录、确认、未读计数。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\AlertController;
use plugin\ads_alert\model\AlertRule;

class AlertApiTest extends ApiTestCase
{
    protected function validRule(): array
    {
        return [
            'name'      => '花费超限',
            'metric'    => 'cost',
            'condition' => 'gt',
            'threshold' => 100000,
            'scope'     => 'tenant',
        ];
    }

    public function testRuleListEmpty(): void
    {
        $request = $this->authedRequest('GET', '/api/alerts/rules');
        $body = $this->assertSuccess((new AlertController())->rules($request));
        $this->assertEquals(0, $body['data']['pagination']['total']);
    }

    public function testCreateRuleSuccess(): void
    {
        $request = $this->authedRequest('POST', '/api/alerts/rules', $this->validRule());
        $body = $this->assertSuccess((new AlertController())->createRule($request));
        $this->assertEquals('花费超限', $body['data']['name']);
        $this->assertEquals('cost', $body['data']['metric']);

        $this->assertEquals(1, AlertRule::count());
    }

    public function testCreateRuleValidationErrors(): void
    {
        $controller = new AlertController();

        // 无效指标
        $bad = $this->authedRequest('POST', '/api/alerts/rules', ['name' => 'x', 'metric' => 'nope', 'condition' => 'gt', 'threshold' => 1]);
        $this->assertError($controller->createRule($bad), 1);

        // 缺少名称
        $noName = $this->authedRequest('POST', '/api/alerts/rules', ['metric' => 'cost', 'condition' => 'gt', 'threshold' => 1]);
        $this->assertError($controller->createRule($noName), 1);

        // 阈值缺失
        $noThreshold = $this->authedRequest('POST', '/api/alerts/rules', ['name' => 'x', 'metric' => 'cost', 'condition' => 'gt']);
        $this->assertError($controller->createRule($noThreshold), 1);

        // 负数阈值
        $negThreshold = $this->authedRequest('POST', '/api/alerts/rules', ['name' => 'x', 'metric' => 'cost', 'condition' => 'gt', 'threshold' => -5]);
        $this->assertError($controller->createRule($negThreshold), 1);

        // platform 范围缺 platform
        $noPlatform = $this->authedRequest('POST', '/api/alerts/rules', ['name' => 'x', 'metric' => 'cost', 'condition' => 'gt', 'threshold' => 1, 'scope' => 'platform']);
        $this->assertError($controller->createRule($noPlatform), 1);

        // campaign 范围缺 campaign_id
        $noCampaign = $this->authedRequest('POST', '/api/alerts/rules', ['name' => 'x', 'metric' => 'cost', 'condition' => 'gt', 'threshold' => 1, 'scope' => 'campaign']);
        $this->assertError($controller->createRule($noCampaign), 1);

        // 非法 webhook_url
        $badWebhook = $this->authedRequest('POST', '/api/alerts/rules', $this->validRule() + ['webhook_url' => 'ftp://x']);
        $this->assertError($controller->createRule($badWebhook), 1);

        $this->assertEquals(0, AlertRule::count());
    }

    public function testUpdateRule(): void
    {
        $rule = AlertRule::create($this->validRule() + ['tenant_id' => $this->tenantId]);
        $request = $this->authedRequest('PUT', "/api/alerts/rules/{$rule->id}", ['threshold' => 500, 'enabled' => 0]);

        $body = $this->assertSuccess((new AlertController())->updateRule($request, $rule->id));
        $this->assertEquals(500.0, (float) $body['data']['threshold']);
        $this->assertEquals(0, (int) $body['data']['enabled']);
    }

    public function testUpdateRuleNotFound(): void
    {
        $request = $this->authedRequest('PUT', '/api/alerts/rules/999999', ['threshold' => 1]);
        $this->assertError((new AlertController())->updateRule($request, 999999), 1);
    }

    public function testDeleteRule(): void
    {
        $rule = AlertRule::create($this->validRule() + ['tenant_id' => $this->tenantId]);
        $request = $this->authedRequest('DELETE', "/api/alerts/rules/{$rule->id}");

        $this->assertSuccess((new AlertController())->deleteRule($request, $rule->id));
        $this->assertEquals(0, AlertRule::count());
    }

    public function testDeleteRuleNotFound(): void
    {
        $request = $this->authedRequest('DELETE', '/api/alerts/rules/999999');
        $this->assertError((new AlertController())->deleteRule($request, 999999), 1);
    }

    public function testLogsEmpty(): void
    {
        $request = $this->authedRequest('GET', '/api/alerts/logs');
        $body = $this->assertSuccess((new AlertController())->logs($request));
        $this->assertEquals(0, $body['data']['pagination']['total']);
    }

    public function testAcknowledgeLog(): void
    {
        $logId = $this->seedAlertLog(['status' => 'triggered']);
        $request = $this->authedRequest('POST', "/api/alerts/logs/$logId/acknowledge");

        $body = $this->assertSuccess((new AlertController())->acknowledge($request, $logId));
        $this->assertEquals('acknowledged', $body['data']['status']);
    }

    public function testAcknowledgeNonTriggeredLog(): void
    {
        $logId = $this->seedAlertLog(['status' => 'resolved']);
        $request = $this->authedRequest('POST', "/api/alerts/logs/$logId/acknowledge");
        $this->assertError((new AlertController())->acknowledge($request, $logId), 1);
    }

    public function testAcknowledgeLogNotFound(): void
    {
        $request = $this->authedRequest('POST', '/api/alerts/logs/999999/acknowledge');
        $this->assertError((new AlertController())->acknowledge($request, 999999), 1);
    }

    public function testUnreadCount(): void
    {
        $this->seedAlertLog(['status' => 'triggered']);
        $this->seedAlertLog(['status' => 'triggered']);
        $this->seedAlertLog(['status' => 'resolved']);

        $request = $this->authedRequest('GET', '/api/alerts/unread-count');
        $body = $this->assertSuccess((new AlertController())->unreadCount($request));
        $this->assertEquals(2, $body['data']['count']);
    }

    protected function seedAlertLog(array $overrides = []): int
    {
        $id = $this->nextId();
        DB::table('erik_alert_logs')->insert(array_merge([
            'id'             => $id,
            'tenant_id'      => $this->tenantId,
            'rule_id'        => $this->nextId(),
            'rule_name'      => '测试规则',
            'metric'         => 'cost',
            'current_value'  => 200,
            'threshold'      => 100,
            'condition'      => 'gt',
            'status'         => 'triggered',
            'extra'          => '{}',
            'created_at'     => now(),
        ], $overrides));
        return $id;
    }
}
