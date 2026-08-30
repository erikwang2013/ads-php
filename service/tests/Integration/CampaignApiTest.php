<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 广告计划 / 广告组 / 创意：CRUD、启停、批量启停与错误路径。
 * 外部平台调用由 mock 适配器隔离（AdapterRegistry 注入）。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\CampaignController;
use plugin\ads_api\controller\v1\AdGroupController;
use plugin\ads_api\controller\v1\CreativeController;

class CampaignApiTest extends ApiTestCase
{
    public function testCampaignIndexEmpty(): void
    {
        $request = $this->authedRequest('GET', '/api/campaigns');
        $body = $this->assertSuccess((new CampaignController())->index($request));
        $this->assertEquals(0, $body['data']['pagination']['total']);
    }

    public function testCampaignIndexWithFilterAndSummary(): void
    {
        $this->seedCampaign(['name' => '促销计划']);

        $request = $this->authedRequest('GET', '/api/campaigns', [], [], ['platform' => 'mock', 'status' => 'enabled']);
        $body = $this->assertSuccess((new CampaignController())->index($request));
        $this->assertEquals(1, $body['data']['pagination']['total']);
        $this->assertEquals('促销计划', $body['data']['list'][0]['name']);
        $this->assertArrayHasKey('summary', $body['data']);
    }

    public function testCampaignIndexSortByCost(): void
    {
        $campaignId = $this->seedCampaign();
        $accountId = DB::table('ads_campaigns')->find($campaignId)->platform_account_id;
        DB::table('ads_report_metrics')->insert([
            'id'                  => $this->nextId(),
            'tenant_id'           => $this->tenantId,
            'platform_account_id' => $accountId,
            'platform'            => 'mock',
            'campaign_id'         => $campaignId,
            'date'                => date('Y-m-d'),
            'granularity'         => 'day',
            'cost'                => 100,
            'impressions'         => 1000,
            'clicks'              => 50,
            'conversions'         => 2,
            'ctr'                 => 5.0,
            'cvr'                 => 4.0,
            'created_at'          => now(),
        ]);

        $request = $this->authedRequest('GET', '/api/campaigns', [], [], ['sort' => 'cost']);
        $body = $this->assertSuccess((new CampaignController())->index($request));
        $this->assertEquals($campaignId, $body['data']['list'][0]['id']);
        $this->assertEquals(100, $body['data']['list'][0]['total_cost']);
    }

    public function testCampaignStoreSuccess(): void
    {
        $accountId = $this->seedAccount();
        $request = $this->authedRequest('POST', '/api/campaigns', [
            'platform'             => 'mock',
            'platform_account_id'  => $accountId,
            'name'                 => '新计划',
            'daily_budget'         => 20000,
        ]);

        $body = $this->assertSuccess((new CampaignController())->store($request));
        $this->assertNotEmpty($body['data']['id']);
        $this->assertStringStartsWith('pc-', $body['data']['platform_campaign_id']);

        $row = DB::table('ads_campaigns')->find($body['data']['id']);
        $this->assertEquals('新计划', $row->name);
        $this->assertEquals('enabled', $row->status);
    }

    public function testCampaignStoreUnsupportedPlatform(): void
    {
        $accountId = $this->seedAccount();
        $request = $this->authedRequest('POST', '/api/campaigns', [
            'platform'            => 'nope',
            'platform_account_id' => $accountId,
            'name'                => 'x',
        ]);

        $body = $this->json((new CampaignController())->store($request));
        $this->assertEquals(1, $body['code']);
        $this->assertStringContainsString('Unsupported', $body['message']);
    }

    public function testCampaignShow(): void
    {
        $id = $this->seedCampaign();
        $body = $this->assertSuccess((new CampaignController())->show($id));
        $this->assertEquals($id, $body['data']['campaign']['id']);
        $this->assertArrayHasKey('today', $body['data']);
    }

    public function testCampaignShowNotFound(): void
    {
        $body = $this->json((new CampaignController())->show(999999));
        $this->assertEquals(1, $body['code']);
        $this->assertStringContainsString('not found', $body['message']);
    }

    public function testCampaignUpdate(): void
    {
        $id = $this->seedCampaign();
        $request = $this->authedRequest('PUT', "/api/campaigns/$id", ['name' => '改名后']);

        $this->assertSuccess((new CampaignController())->update($request, $id));
        $this->assertEquals('改名后', DB::table('ads_campaigns')->find($id)->name);
    }

    public function testCampaignTogglePause(): void
    {
        $id = $this->seedCampaign();
        $request = $this->authedRequest('POST', "/api/campaigns/$id/toggle", ['enabled' => false]);

        $this->assertSuccess((new CampaignController())->toggle($request, $id));
        $this->assertEquals('paused', DB::table('ads_campaigns')->find($id)->status);
    }

    public function testCampaignBatchToggle(): void
    {
        $id1 = $this->seedCampaign(['name' => 'A']);
        $id2 = $this->seedCampaign(['name' => 'B']);

        $request = $this->authedRequest('POST', '/api/campaigns/batch/toggle', [
            'ids'     => [$id1, $id2, 888888],
            'enabled' => false,
        ]);

        $body = $this->assertSuccess((new CampaignController())->batchToggle($request));
        $this->assertEquals(2, $body['data']['success']);
        $this->assertEquals(1, $body['data']['failed']);
    }

    public function testCampaignBatchToggleRequiresIds(): void
    {
        $request = $this->authedRequest('POST', '/api/campaigns/batch/toggle', ['ids' => []]);
        $body = $this->json((new CampaignController())->batchToggle($request));
        $this->assertEquals(1, $body['code']);
    }

    public function testAdGroupIndexEmpty(): void
    {
        $request = $this->authedRequest('GET', '/api/ad-groups');
        $body = $this->assertSuccess((new AdGroupController())->index($request));
        $this->assertEquals(0, $body['data']['pagination']['total']);
    }

    public function testAdGroupStoreAndIndex(): void
    {
        $campaignId = $this->seedCampaign();
        $request = $this->authedRequest('POST', '/api/ad-groups', [
            'campaign_id' => $campaignId,
            'name'        => '测试组',
            'bid_amount'  => 100,
            'bid_type'    => 'cpc',
        ]);

        $body = $this->assertSuccess((new AdGroupController())->store($request));
        $this->assertStringStartsWith('pc-', $body['data']['platform_adgroup_id']);

        $list = $this->authedRequest('GET', '/api/ad-groups');
        $body = $this->assertSuccess((new AdGroupController())->index($list));
        $this->assertEquals(1, $body['data']['pagination']['total']);
        $this->assertEquals('测试组', $body['data']['list'][0]['name']);
        $this->assertEquals('mock', $body['data']['list'][0]['platform']);
    }

    public function testAdGroupStoreRequiresExistingCampaign(): void
    {
        $request = $this->authedRequest('POST', '/api/ad-groups', [
            'campaign_id' => 999999,
            'name'        => 'x',
        ]);
        $body = $this->json((new AdGroupController())->store($request));
        $this->assertEquals(1, $body['code']);
        $this->assertStringContainsString('Campaign not found', $body['message']);
    }

    public function testAdGroupUpdateAndToggle(): void
    {
        $campaignId = $this->seedCampaign();
        $groupId = $this->seedAdGroup($campaignId);

        $update = $this->authedRequest('PUT', "/api/ad-groups/$groupId", ['name' => '组改名']);
        $this->assertSuccess((new AdGroupController())->update($update, $groupId));
        $this->assertEquals('组改名', DB::table('ads_ad_groups')->find($groupId)->name);

        $toggle = $this->authedRequest('POST', "/api/ad-groups/$groupId/toggle", ['enabled' => false]);
        $this->assertSuccess((new AdGroupController())->toggle($toggle, $groupId));
        $this->assertEquals('paused', DB::table('ads_ad_groups')->find($groupId)->status);
    }

    public function testAdGroupShowNotFound(): void
    {
        $body = $this->json((new AdGroupController())->show(999999));
        $this->assertEquals(1, $body['code']);
    }

    public function testCreativeIndexWithSeed(): void
    {
        $campaignId = $this->seedCampaign();
        $groupId = $this->seedAdGroup($campaignId);
        $this->seedCreative($groupId);

        $request = $this->authedRequest('GET', '/api/creatives', [], [], ['platform' => 'mock']);
        $body = $this->assertSuccess((new CreativeController())->index($request));
        $this->assertEquals(1, $body['data']['pagination']['total']);
        $this->assertEquals('测试创意', $body['data']['list'][0]['title']);
    }

    public function testCreativeShowNotFound(): void
    {
        $body = $this->json((new CreativeController())->show(999999));
        $this->assertEquals(1, $body['code']);
        $this->assertStringContainsString('not found', $body['message']);
    }

    protected function seedAdGroup(int $campaignId, array $overrides = []): int
    {
        $id = $this->nextId();
        DB::table('ads_ad_groups')->insert(array_merge([
            'id'                  => $id,
            'campaign_id'         => $campaignId,
            'platform_adgroup_id' => 'pc-ag-1',
            'name'                => '种子组',
            'status'              => 'enabled',
            'bid_amount'          => 100,
            'bid_type'            => 'cpc',
            'targeting'           => '{}',
            'extra'               => '{}',
            'created_at'          => now(),
            'updated_at'          => now(),
        ], $overrides));
        return $id;
    }

    protected function seedCreative(int $adGroupId, array $overrides = []): int
    {
        $id = $this->nextId();
        DB::table('ads_creatives')->insert(array_merge([
            'id'                    => $id,
            'ad_group_id'           => $adGroupId,
            'platform_creative_id'  => 'pc-cr-1',
            'title'                 => '测试创意',
            'description'           => '',
            'media_type'            => 'image',
            'media_urls'            => '[]',
            'extra'                 => '{}',
            'created_at'            => now(),
            'updated_at'            => now(),
        ], $overrides));
        return $id;
    }
}
