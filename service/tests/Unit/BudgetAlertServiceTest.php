<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * BudgetAlertService 测试：预算消耗分档阈值（≥50% yellow / ≥80% orange / ≥100% red）。
 */

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_alert\service\BudgetAlertService;

class BudgetAlertServiceTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->exec('CREATE TABLE erik_campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT, name TEXT,
            daily_budget INT, status TEXT, created_at TEXT, updated_at TEXT)');
        $this->exec('CREATE TABLE erik_report_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT,
            campaign_id INT, date TEXT, cost INT, impressions INT, clicks INT, conversions INT)');
    }

    private function seedCampaign(int $id, int $budget, int $spent): void
    {
        DB::table('erik_campaigns')->insert([
            'id' => $id, 'tenant_id' => 1, 'platform' => 'juliang', 'name' => "campaign {$id}",
            'daily_budget' => $budget, 'status' => 'enabled',
        ]);
        DB::table('erik_report_metrics')->insert([
            'tenant_id' => 1, 'platform' => 'juliang', 'campaign_id' => $id,
            'date' => date('Y-m-d'), 'cost' => $spent,
        ]);
    }

    public function testLevelThresholds(): void
    {
        // 40% → 无告警；50% → yellow；80% → orange；100% → red；150% → red
        $this->seedCampaign(1, 1000, 400);
        $this->seedCampaign(2, 1000, 500);
        $this->seedCampaign(3, 1000, 800);
        $this->seedCampaign(4, 1000, 1000);
        $this->seedCampaign(5, 1000, 1500);

        $alerts = (new BudgetAlertService())->checkAll();

        $byId = [];
        foreach ($alerts as $a) {
            $byId[$a['campaign_id']] = $a['level'];
        }

        $this->assertArrayNotHasKey(1, $byId);
        $this->assertSame('yellow', $byId[2] ?? null);
        $this->assertSame('orange', $byId[3] ?? null);
        $this->assertSame('red', $byId[4] ?? null);
        $this->assertSame('red', $byId[5] ?? null);
    }

    public function testAlertCarriesSpentBudgetAndPercentage(): void
    {
        $this->seedCampaign(7, 2000, 1000);

        $alerts = (new BudgetAlertService())->checkAll();

        $this->assertCount(1, $alerts);
        $this->assertSame(7, $alerts[0]['campaign_id']);
        $this->assertSame(1000, $alerts[0]['spent']);
        $this->assertSame(2000, $alerts[0]['budget']);
        $this->assertSame(50.0, $alerts[0]['pct']);
        $this->assertSame('yellow', $alerts[0]['level']);
    }

    public function testSkipsDisabledAndZeroBudgetCampaigns(): void
    {
        DB::table('erik_campaigns')->insert([
            ['id' => 1, 'tenant_id' => 1, 'platform' => 'juliang', 'name' => 'paused', 'daily_budget' => 1000, 'status' => 'paused'],
            ['id' => 2, 'tenant_id' => 1, 'platform' => 'juliang', 'name' => 'zero budget', 'daily_budget' => 0, 'status' => 'enabled'],
        ]);

        $this->assertSame([], (new BudgetAlertService())->checkAll());
    }
}
