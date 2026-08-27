<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 自动出价规则（bid-rules）+ 定向模板（targeting-templates）CRUD。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\BidRuleController;
use plugin\ads_api\controller\v1\TargetingTemplateController;

class BidRuleTemplateApiTest extends ApiTestCase
{
    protected function validBidRule(): array
    {
        return [
            'name'        => '花费超限降预算',
            'metric'      => 'cost',
            'condition'   => 'gt',
            'threshold'   => 5000,
            'action_type' => 'adjust_budget',
            'adjust_step' => -2000,
        ];
    }

    public function testBidRuleListEmpty(): void
    {
        $request = $this->authedRequest('GET', '/api/bid-rules');
        $body = $this->assertSuccess((new BidRuleController())->index($request));
        $this->assertEquals(0, $body['data']['pagination']['total']);
    }

    public function testBidRuleCreateAndList(): void
    {
        $request = $this->authedRequest('POST', '/api/bid-rules', $this->validBidRule());
        $body = $this->assertSuccess((new BidRuleController())->store($request));
        $this->assertEquals('花费超限降预算', $body['data']['name']);
        $this->assertEquals(5000.0, (float) $body['data']['threshold']);

        $list = $this->authedRequest('GET', '/api/bid-rules', [], [], ['enabled' => 1]);
        $body = $this->assertSuccess((new BidRuleController())->index($list));
        $this->assertEquals(1, $body['data']['pagination']['total']);
    }

    public function testBidRuleUpdate(): void
    {
        $created = (new BidRuleController())->store($this->authedRequest('POST', '/api/bid-rules', $this->validBidRule()));
        $id = $this->json($created)['data']['id'];

        $request = $this->authedRequest('PUT', "/api/bid-rules/$id", ['threshold' => 999, 'enabled' => 0]);
        $body = $this->assertSuccess((new BidRuleController())->update($request, $id));
        $this->assertEquals(999.0, (float) $body['data']['threshold']);
    }

    public function testBidRuleUpdateNotFound(): void
    {
        $request = $this->authedRequest('PUT', '/api/bid-rules/999999', ['threshold' => 1]);
        $this->assertError((new BidRuleController())->update($request, 999999), 1);
    }

    public function testBidRuleDelete(): void
    {
        $created = (new BidRuleController())->store($this->authedRequest('POST', '/api/bid-rules', $this->validBidRule()));
        $id = $this->json($created)['data']['id'];

        $this->assertSuccess((new BidRuleController())->destroy($id));
        $this->assertEquals(0, DB::table('ads_bid_rules')->count());
    }

    public function testBidRuleLogsEmpty(): void
    {
        $request = $this->authedRequest('GET', '/api/bid-rules/logs');
        $body = $this->assertSuccess((new BidRuleController())->logs($request));
        $this->assertEquals(0, $body['data']['pagination']['total']);
    }

    public function testTemplateCreateShowUpdateDelete(): void
    {
        $controller = new TargetingTemplateController();

        $store = $this->authedRequest('POST', '/api/targeting-templates', [
            'name'      => '核心受众',
            'platform'  => 'mock',
            'targeting' => ['age' => ['min' => 18, 'max' => 45]],
        ]);
        $body = $this->assertSuccess($controller->store($store));
        $id = $body['data']['id'];
        $this->assertEquals('核心受众', $body['data']['name']);

        // 详情
        $show = $this->assertSuccess($controller->show($id));
        $this->assertEquals(['age' => ['min' => 18, 'max' => 45]], $show['data']['targeting']);

        // 列表
        $list = $this->authedRequest('GET', '/api/targeting-templates', [], [], ['platform' => 'mock']);
        $body = $this->assertSuccess($controller->index($list));
        $this->assertEquals(1, $body['data']['pagination']['total']);

        // 更新
        $update = $this->authedRequest('PUT', "/api/targeting-templates/$id", ['name' => '新受众']);
        $body = $this->assertSuccess($controller->update($update, $id));
        $this->assertEquals('新受众', $body['data']['name']);

        // 删除
        $this->assertSuccess($controller->destroy($id));
        $this->assertEquals(0, DB::table('ads_targeting_templates')->count());
    }

    public function testTemplateShowNotFound(): void
    {
        $this->assertError((new TargetingTemplateController())->show(999999), 1);
    }

    public function testTemplateDeleteNotFound(): void
    {
        $this->assertError((new TargetingTemplateController())->destroy(999999), 1);
    }
}
