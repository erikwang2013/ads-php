<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * BidEngine 测试：compare 条件语义、METRIC_SQL 覆盖、未知指标短路；
 * SQLite 上验证 evaluate → adjust_budget / toggle_pause 全流程与冷却期抑制。
 */

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_platform\model\BidLog;
use plugin\ads_platform\model\BidRule;
use plugin\ads_platform\service\BidEngine;
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\PlatformAdapter;
use plugin\ads_platform\src\CampaignData;
use plugin\ads_platform\src\ReportRequest;

class SpyBidAdapter implements PlatformAdapter
{
    public array $updates = [];
    public array $toggles = [];

    public function code(): string { return 'spybid'; }
    public function name(): string { return 'Spy Bid Adapter'; }
    public function capabilities(): array { return ['campaign']; }
    public function buildAuthUrl(string $redirectUri, string $state): string { return ''; }
    public function exchangeToken(string $code, string $redirectUri): array { return []; }
    public function refreshToken(string $refreshToken): array { return []; }
    public function fetchAccountInfo(string $accessToken): array { return []; }
    public function fetchCampaigns(string $accessToken, string $accountId): \Generator { yield from []; }
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator { yield from []; }
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator { yield from []; }
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator { yield from []; }
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string { return ''; }
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void
    {
        $this->updates[] = [$platformId, $data->dailyBudget];
    }
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void
    {
        $this->toggles[] = [$platformId, $enabled];
    }
}

class BidEngineTest extends SqliteTestCase
{
    protected SpyBidAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new SpyBidAdapter();
        AdapterRegistry::register($this->adapter);

        $this->exec('CREATE TABLE erik_bid_rules (
            id TEXT PRIMARY KEY, tenant_id INT, metric TEXT, threshold REAL, condition TEXT,
            scope TEXT, platform TEXT, campaign_id INT, cooldown_minutes INT, action_type TEXT,
            adjust_step INT, budget_min INT, budget_max INT, enabled INT, created_at TEXT, updated_at TEXT)');
        $this->exec('CREATE TABLE erik_bid_logs (
            id TEXT PRIMARY KEY, rule_id TEXT, tenant_id INT, campaign_id INT, metric_value REAL,
            action_type TEXT, old_budget INT, new_budget INT, old_status TEXT, new_status TEXT,
            created_at TEXT)');
        $this->exec('CREATE TABLE erik_campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT, name TEXT,
            platform_account_id INT, platform_campaign_id TEXT, daily_budget INT, status TEXT,
            created_at TEXT, updated_at TEXT)');
        $this->exec('CREATE TABLE erik_platform_accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT, account_name TEXT,
            account_id_on_platform TEXT, access_token TEXT, refresh_token TEXT, status INT,
            token_expires_at TEXT, created_at TEXT, updated_at TEXT)');
        $this->exec('CREATE TABLE erik_report_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT,
            campaign_id INT, date TEXT, cost INT, impressions INT, clicks INT, conversions INT)');
    }

    protected function tearDown(): void
    {
        $ref = new \ReflectionClass(AdapterRegistry::class);
        $prop = $ref->getProperty('adapters');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
        parent::tearDown();
    }

    private function seedBaseData(): void
    {
        DB::table('erik_platform_accounts')->insert([
            'id' => 1, 'tenant_id' => 1, 'platform' => 'spybid', 'account_name' => 'a',
            'account_id_on_platform' => 'acc-1', 'access_token' => 'tok', 'refresh_token' => 'rt',
            'status' => 1,
        ]);
        DB::table('erik_campaigns')->insert([
            'id' => 1, 'tenant_id' => 1, 'platform' => 'spybid', 'name' => 'c1',
            'platform_account_id' => 1, 'platform_campaign_id' => 'pc-1', 'daily_budget' => 100,
            'status' => 'enabled',
        ]);
        DB::table('erik_report_metrics')->insert([
            'tenant_id' => 1, 'platform' => 'spybid', 'campaign_id' => 1,
            'date' => date('Y-m-d'), 'cost' => 200,
        ]);
    }

    private function rule(array $overrides = []): BidRule
    {
        return new BidRule(array_merge([
            'id'               => 'br1',
            'tenant_id'        => 1,
            'metric'           => 'cost',
            'threshold'        => 100,
            'condition'        => 'gt',
            'scope'            => 'campaign',
            'platform'         => null,
            'campaign_id'      => 1,
            'cooldown_minutes' => 30,
            'action_type'      => 'adjust_budget',
            'adjust_step'      => 50,
            'budget_min'       => 0,
            'budget_max'       => 0,
            'enabled'          => true,
        ], $overrides));
    }

    public function testMetricSqlCoversAllMetrics(): void
    {
        $const = new \ReflectionClassConstant(BidEngine::class, 'METRIC_SQL');
        $sql = $const->getValue();

        foreach (['cost', 'impressions', 'clicks', 'conversions', 'ctr', 'cvr', 'roi'] as $m) {
            $this->assertArrayHasKey($m, $sql, "missing METRIC_SQL entry for {$m}");
        }
    }

    public function testCompareConditionSemantics(): void
    {
        $engine = new BidEngine();
        $compare = new \ReflectionMethod(BidEngine::class, 'compare');
        $compare->setAccessible(true);

        $this->assertTrue($compare->invoke($engine, 200.0, 100.0, 'gt'));
        $this->assertFalse($compare->invoke($engine, 100.0, 100.0, 'gt'));
        $this->assertTrue($compare->invoke($engine, 100.0, 100.0, 'gte'));
        $this->assertTrue($compare->invoke($engine, 90.0, 100.0, 'lt'));
        $this->assertFalse($compare->invoke($engine, 90.0, 100.0, 'gt'));
    }

    public function testEvaluateReturnsNullForUnknownMetric(): void
    {
        $engine = new BidEngine();
        $this->assertNull($engine->evaluate($this->rule(['metric' => 'unknown'])));
    }

    public function testEvaluateAdjustsBudgetWithinMinMax(): void
    {
        $this->seedBaseData();

        $rule = $this->rule([
            'action_type' => 'adjust_budget', 'adjust_step' => 50, 'budget_min' => 200, 'budget_max' => 150,
        ]);
        $log = (new BidEngine())->evaluate($rule);

        $this->assertInstanceOf(BidLog::class, $log);
        $this->assertSame(100, $log->old_budget);
        // max(100+50, 200)=200，再 min(200,150)=150
        $this->assertSame(150, $log->new_budget);
        $this->assertSame('adjust_budget', $log->action_type);

        $campaign = DB::table('erik_campaigns')->find(1);
        $this->assertSame(150, (int) $campaign->daily_budget);
        $this->assertSame(['pc-1', 150], $this->adapter->updates[0] ?? null);
    }

    public function testEvaluateTogglePause(): void
    {
        $this->seedBaseData();

        $log = (new BidEngine())->evaluate($this->rule(['action_type' => 'toggle_pause']));

        $this->assertInstanceOf(BidLog::class, $log);
        $this->assertSame('enabled', $log->old_status);
        $this->assertSame('paused', $log->new_status);
        $this->assertSame(['pc-1', false], $this->adapter->toggles[0] ?? null);
        $this->assertSame('paused', DB::table('erik_campaigns')->find(1)->status);
    }

    public function testEvaluateRespectsCooldown(): void
    {
        $this->seedBaseData();

        $engine = new BidEngine();
        $rule = $this->rule();

        $this->assertInstanceOf(BidLog::class, $engine->evaluate($rule));
        // 冷却期内已有 BidLog → 抑制
        $this->assertNull($engine->evaluate($rule));
    }

    public function testEvaluateDoesNotTriggerWhenConditionNotMet(): void
    {
        $this->seedBaseData();

        $this->assertNull((new BidEngine())->evaluate($this->rule(['condition' => 'lt'])));
    }
}
