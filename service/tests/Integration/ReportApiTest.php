<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz} — https://erik.xyz
 *
 * 报表：汇总 / 自定义报表 / 归因 / 日历 / 预算告警 / 导出。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\DashboardController;
use plugin\ads_api\controller\v1\ReportController;
use plugin\ads_api\controller\v1\ExportController;

class ReportApiTest extends ApiTestCase
{
    protected function seedMetrics(array $overrides = []): void
    {
        $campaignId = $this->seedCampaign();
        $accountId = DB::table('erik_campaigns')->find($campaignId)->platform_account_id;
        DB::table('erik_report_metrics')->insert(array_merge([
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
        $this->assertNotEmpty($body['data']['by_platform']);
    }

    public function testCustomReport(): void
    {
        $this->seedMetrics();
        $request = $this->authedRequest('GET', '/api/reports/custom', [], [], [
            'date_start' => date('Y-m-01'),
            'date_end'   => date('Y-m-d'),
        ]);
        $body = $this->assertSuccess((new ReportController())->custom($request));
        $this->assertArrayHasKey('data', $body);
    }

    public function testAttributionModels(): void
    {
        $body = $this->assertSuccess((new DashboardController())->attributionModels());
        $this->assertNotEmpty($body['data']);
    }

    public function testAttributionCompute(): void
    {
        $this->seedMetrics();
        $this->seedConversion();

        $request = $this->authedRequest('GET', '/api/reports/attribution', [], [], [
            'date_start' => date('Y-m-01'),
            'date_end'   => date('Y-m-d'),
        ]);
        $body = $this->assertSuccess((new DashboardController())->attribution($request));
        $this->assertArrayHasKey('data', $body);
    }

    public function testCalendar(): void
    {
        $this->seedCampaign(['start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d')]);
        $request = $this->authedRequest('GET', '/api/reports/calendar');
        $body = $this->assertSuccess((new DashboardController())->calendar($request));
        $this->assertArrayHasKey('data', $body);
    }

    public function testBudgetAlerts(): void
    {
        $this->seedCampaign();
        $request = $this->authedRequest('GET', '/api/reports/budget-alerts');
        $body = $this->assertSuccess((new DashboardController())->budgetAlerts($request));
        $this->assertArrayHasKey('data', $body);
    }

    public function testExportCsv(): void
    {
        $this->seedMetrics();
        $request = $this->authedRequest('GET', '/api/reports/export', [], [], ['format' => 'csv']);

        // Response::file() 依赖 App::request()（If-Modified-Since 判断）
        \Workerman\Coroutine\Context::set(\Webman\Http\Request::class, $request);

        $response = (new ExportController())->export($request);

        // 文件响应经 withFile 流式发送：文件生成成功返回 200，失败返回 404 body
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function testExportDashboardUnsupportedFormat(): void
    {
        $request = $this->authedRequest('GET', '/api/reports/export-dashboard', [], [], ['format' => 'jpg']);
        $body = $this->json((new ExportController())->exportDashboard($request));
        $this->assertEquals(1, $body['code']);
    }

    protected function seedConversion(array $overrides = []): void
    {
        DB::table('erik_conversions')->insert(array_merge([
            'id'              => $this->nextId(),
            'tenant_id'       => $this->tenantId,
            'platform'        => 'mock',
            'order_id'        => 'ord-' . $this->nextId(),
            'conversion_time' => date('Y-m-d H:i:s'),
            'value'           => 990,
            'currency'        => 'CNY',
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $overrides));
    }
}
