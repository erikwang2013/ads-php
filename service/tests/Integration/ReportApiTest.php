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
            'tenant_id'            => $this->tenantId,
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
        $this->assertEquals(2.0, $body['data']['overview']['avg_cpc']);
        $this->assertNotEmpty($body['data']['by_platform']);
        $this->assertEquals(50, $body['data']['daily'][0]['clicks']);
        $this->assertEquals(2, $body['data']['daily'][0]['conversions']);
    }
}
