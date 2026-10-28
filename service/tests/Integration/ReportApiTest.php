<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz} — https://erik.xyz
 *
 * 仪表盘汇总。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\DashboardController;

class ReportApiTest extends ApiTestCase
{
    protected function seedMetrics(array $overrides = []): void
    {
        $campaignId = $this->seedCampaign();
        $accountId = DB::table('ads_campaigns')->find($campaignId)->platform_account_id;
        DB::table('ads_report_metrics')->insert(array_merge([
            'id'                   => $this->nextId(),
            'platform_account_id'  => $accountId,
            'platform'             => 'mock',
            'campaign_id'          => $campaignId,
            'date'         => date('Y-m-d'),
            'granularity'  => 'day',
            'cost'         => 100,
            'impressions'  => 1000,
            'clicks'       => 50,
            'conversions'  => 2,
            'ctr'          => 5.0,
            'cvr'          => 4.0,
            'created_at'   => now(),
        ], $overrides));
    }

    public function testSummaryEmpty(): void
    {
        $request = $this->authedRequest('GET', '/api/reports/summary');
        $body = $this->assertSuccess((new DashboardController())->summary($request));
        $this->assertEquals(0, $body['data']['overview']['total_cost']);
        $this->assertEquals([], $body['data']['daily']);
    }

    public function testSummaryWithMetrics(): void
    {
        $this->seedMetrics();

        $request = $this->authedRequest('GET', '/api/reports/summary');
        $body = $this->assertSuccess((new DashboardController())->summary($request));
        $this->assertEquals(100, $body['data']['overview']['total_cost']);
        $this->assertEquals(50, $body['data']['overview']['total_clicks']);
        $this->assertEquals(2, $body['data']['overview']['total_conversions']);
        $this->assertNotEmpty($body['data']['by_platform']);
        $this->assertEquals(50, $body['data']['daily'][0]['clicks']);
        $this->assertEquals(2, $body['data']['daily'][0]['conversions']);

        // 派生比率由 PHP 侧 bc_div/bcmul 计算，返回值必须是数值（bcmath 字符串或数字）
        foreach (['avg_ctr', 'avg_cvr', 'avg_cpc', 'avg_cpa'] as $key) {
            $this->assertArrayHasKey($key, $body['data']['overview']);
            $this->assertTrue(is_numeric($body['data']['overview'][$key]), "$key should be numeric");
        }
        $platform = $body['data']['by_platform'][0];
        foreach (['ctr', 'cvr', 'cpc'] as $key) {
            $this->assertArrayHasKey($key, $platform);
            $this->assertTrue(is_numeric($platform[$key]), "by_platform.$key should be numeric");
        }
    }
}
