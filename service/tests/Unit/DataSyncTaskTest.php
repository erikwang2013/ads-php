<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * DataSyncTask 测试：SQLite + 探针 Adapter，验证 executeSingleAccount
 * 全链路 upsert（campaigns → ad_groups → creatives → report_metrics）。
 */

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_account\model\PlatformAccount;
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\PlatformAdapter;
use plugin\ads_platform\src\CampaignData;
use plugin\ads_platform\src\ReportRequest;
use plugin\ads_task\task\DataSyncTask;
use RuntimeException;

class SpySyncAdapter implements PlatformAdapter
{
    public function code(): string { return 'spysync'; }
    public function name(): string { return 'Spy Sync Adapter'; }
    public function capabilities(): array { return ['report', 'campaign']; }
    public function buildAuthUrl(string $redirectUri, string $state): string { return ''; }
    public function exchangeToken(string $code, string $redirectUri): array { return []; }
    public function refreshToken(string $refreshToken): array { return []; }
    public function fetchAccountInfo(string $accessToken): array { return []; }
    public function fetchCampaigns(string $accessToken, string $accountId): \Generator
    {
        yield ['platform_campaign_id' => 'pc-1', 'name' => 'Camp A', 'daily_budget' => 1000, 'status' => 'enabled', 'extra' => ['k' => 'v']];
        yield ['platform_campaign_id' => 'pc-2', 'name' => 'Camp B', 'daily_budget' => 2000, 'status' => 'paused', 'extra' => []];
    }
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
    {
        yield ['platform_adgroup_id' => "ag-{$campaignId}-1", 'name' => 'AG1', 'status' => 'enabled', 'bid_amount' => 150, 'bid_type' => 'cpc', 'targeting' => ['age' => '18-30'], 'extra' => []];
    }
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
    {
        yield ['platform_creative_id' => "cr-{$adGroupId}-1", 'title' => 'Creative 1', 'description' => 'desc', 'media_type' => 'video', 'media_urls' => ['https://x/v.mp4'], 'landing_url' => 'https://x/l', 'extra' => []];
    }
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
    {
        yield ['platform_campaign_id' => 'pc-1', 'date' => date('Y-m-d'), 'cost' => 100, 'impressions' => 1000, 'clicks' => 10, 'conversions' => 2];
    }
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string { return ''; }
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void {}
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void {}
}

class DataSyncTaskTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AdapterRegistry::register(new SpySyncAdapter());

        $this->exec('CREATE TABLE erik_platform_accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT, account_name TEXT,
            account_id_on_platform TEXT, access_token TEXT, refresh_token TEXT, status INT,
            token_expires_at TEXT, last_sync_at TEXT, sync_enabled INT, created_at TEXT, updated_at TEXT)');
        $this->exec('CREATE TABLE erik_campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT, name TEXT,
            platform_account_id INT, platform_campaign_id TEXT, daily_budget INT, status TEXT,
            extra TEXT, synced_at TEXT, created_at TEXT, updated_at TEXT)');
        $this->exec('CREATE TABLE erik_ad_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT, campaign_id INT, platform_adgroup_id TEXT,
            name TEXT, status TEXT, bid_amount INT, bid_type TEXT, targeting TEXT, extra TEXT,
            created_at TEXT, updated_at TEXT)');
        $this->exec('CREATE TABLE erik_creatives (
            id INTEGER PRIMARY KEY AUTOINCREMENT, ad_group_id INT, platform_creative_id TEXT,
            title TEXT, description TEXT, media_type TEXT, media_urls TEXT, landing_url TEXT,
            extra TEXT, created_at TEXT, updated_at TEXT)');
        $this->exec('CREATE TABLE erik_report_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT,
            platform_account_id INT, campaign_id INT, date TEXT, granularity TEXT,
            cost INT, impressions INT, clicks INT, conversions INT, ctr REAL, cpc REAL, cpm REAL, cvr REAL)');
        $this->exec('CREATE TABLE erik_sync_errors (
            id INTEGER PRIMARY KEY AUTOINCREMENT, platform_account_id INT, platform TEXT,
            error_message TEXT, next_retry_at TEXT, created_at TEXT)');
    }

    protected function tearDown(): void
    {
        $ref = new \ReflectionClass(AdapterRegistry::class);
        $prop = $ref->getProperty('adapters');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
        parent::tearDown();
    }

    private function seedAccount(): PlatformAccount
    {
        $account = new PlatformAccount([
            'tenant_id' => 1, 'platform' => 'spysync', 'account_name' => 'Sync Acc',
            'account_id_on_platform' => 'acc-1', 'access_token' => 'tok', 'refresh_token' => '',
            'status' => 1, 'sync_enabled' => true,
        ]);
        $account->save();
        return $account;
    }

    public function testExecuteSingleAccountSyncsAllLevels(): void
    {
        $account = $this->seedAccount();

        (new DataSyncTask())->executeSingleAccount((int) $account->id);

        // campaigns upserted
        $this->assertSame(2, DB::table('erik_campaigns')->count());
        $campA = DB::table('erik_campaigns')->where('platform_campaign_id', 'pc-1')->first();
        $this->assertSame('Camp A', $campA->name);
        $this->assertSame(1000, (int) $campA->daily_budget);

        // ad groups (1 per campaign → 2)
        $this->assertSame(2, DB::table('erik_ad_groups')->count());

        // creatives (1 per ad group → 2)
        $this->assertSame(2, DB::table('erik_creatives')->count());
        $creative = DB::table('erik_creatives')->first();
        $this->assertSame('Creative 1', $creative->title);

        // report metrics linked to campaign pc-1
        $metric = DB::table('erik_report_metrics')->first();
        $this->assertSame($campA->id, (int) $metric->campaign_id);
        $this->assertSame(100, (int) $metric->cost);

        // account last_sync_at set
        $this->assertNotNull($account->fresh()->last_sync_at);
    }

    public function testExecuteSingleAccountIsIdempotent(): void
    {
        $account = $this->seedAccount();
        $task = new DataSyncTask();

        $task->executeSingleAccount((int) $account->id);
        $task->executeSingleAccount((int) $account->id);

        $this->assertSame(2, DB::table('erik_campaigns')->count());
        $this->assertSame(2, DB::table('erik_ad_groups')->count());
        $this->assertSame(2, DB::table('erik_creatives')->count());
        $this->assertSame(1, DB::table('erik_report_metrics')->count());
    }

    public function testExecuteSingleAccountThrowsForMissingAccount(): void
    {
        $this->expectException(RuntimeException::class);
        (new DataSyncTask())->executeSingleAccount(999);
    }
}
