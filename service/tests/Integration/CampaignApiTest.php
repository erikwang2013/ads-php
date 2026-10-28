<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 广告计划只读列表：分页、筛选与花费排序（供仪表盘）。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\CampaignController;

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

    public function testCampaignIndexSecondPageReturnsSecondCampaign(): void
    {
        $firstId  = $this->seedCampaign(['id' => 900001, 'platform_campaign_id' => 'pc-page-1']);
        $secondId = $this->seedCampaign(['id' => 900002, 'platform_campaign_id' => 'pc-page-2']);

        $request = $this->authedRequest('GET', '/api/campaigns', [], [], [
            'page' => 2, 'per_page' => 1, 'sort' => 'id',
        ]);
        $body = $this->assertSuccess((new CampaignController())->index($request));

        $this->assertSame(2, $body['data']['pagination']['page']);
        $this->assertSame(1, $body['data']['pagination']['per_page']);
        $this->assertSame(2, $body['data']['pagination']['total']);
        $this->assertCount(1, $body['data']['list']);
        // sort=id 固定按 id desc：第一页是较大的 id，第二页是较小的 id
        $this->assertSame($firstId, $body['data']['list'][0]['id']);
        $this->assertNotSame($secondId, $body['data']['list'][0]['id']);
    }

    public function testCampaignIndexSortByCost(): void
    {
        $campaignId = $this->seedCampaign();
        $accountId = DB::table('ads_campaigns')->find($campaignId)->platform_account_id;
        DB::table('ads_report_metrics')->insert([
            'id'                  => $this->nextId(),
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
}
