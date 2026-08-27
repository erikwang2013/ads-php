<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * TokenRefreshTask 测试：SQLite + 探针 Adapter，
 * 验证仅刷新过期 token 的账号并跳过未过期/无 refresh_token 的账号。
 */

namespace Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_account\model\PlatformAccount;
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\PlatformAdapter;
use plugin\ads_platform\src\CampaignData;
use plugin\ads_platform\src\ReportRequest;
use plugin\ads_task\task\TokenRefreshTask;

class SpyRefreshAdapter implements PlatformAdapter
{
    public int $refreshCalls = 0;

    public function code(): string { return 'spyrefresh'; }
    public function name(): string { return 'Spy Refresh Adapter'; }
    public function capabilities(): array { return ['oauth']; }
    public function buildAuthUrl(string $redirectUri, string $state): string { return ''; }
    public function exchangeToken(string $code, string $redirectUri): array { return []; }
    public function refreshToken(string $refreshToken): array
    {
        $this->refreshCalls++;
        return ['access_token' => 'at-new', 'refresh_token' => 'rt-new', 'expires_in' => 86400];
    }
    public function fetchAccountInfo(string $accessToken): array { return []; }
    public function fetchCampaigns(string $accessToken, string $accountId): \Generator { yield from []; }
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator { yield from []; }
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator { yield from []; }
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator { yield from []; }
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string { return ''; }
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void {}
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void {}
}

class TokenRefreshTaskTest extends SqliteTestCase
{
    protected SpyRefreshAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new SpyRefreshAdapter();
        AdapterRegistry::register($this->adapter);

        $this->exec('CREATE TABLE erik_platform_accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INT, platform TEXT, account_name TEXT,
            account_id_on_platform TEXT, access_token TEXT, refresh_token TEXT, status INT,
            token_expires_at TEXT, last_sync_at TEXT, sync_enabled INT, created_at TEXT, updated_at TEXT)');
    }

    protected function tearDown(): void
    {
        $ref = new \ReflectionClass(AdapterRegistry::class);
        $prop = $ref->getProperty('adapters');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
        parent::tearDown();
    }

    private function seedAccount(array $overrides = []): PlatformAccount
    {
        $account = new PlatformAccount(array_merge([
            'tenant_id' => 1, 'platform' => 'spyrefresh', 'account_name' => 'acc',
            'account_id_on_platform' => 'adv-1', 'access_token' => 'old', 'refresh_token' => 'rt-old',
            'status' => 1, 'token_expires_at' => Carbon::now()->subHour(), // 已过期
        ], $overrides));
        $account->save();
        return $account;
    }

    public function testRefreshesExpiredTokenAccount(): void
    {
        $account = $this->seedAccount();

        (new TokenRefreshTask())->execute();

        $this->assertSame(1, $this->adapter->refreshCalls);
        $fresh = $account->fresh();
        $this->assertSame('at-new', $fresh->access_token);
        $this->assertSame('rt-new', $fresh->refresh_token);
        $this->assertTrue($fresh->token_expires_at->isFuture());
    }

    public function testSkipsNonExpiredTokenAccount(): void
    {
        $account = $this->seedAccount(['token_expires_at' => Carbon::now()->addDay()]);

        (new TokenRefreshTask())->execute();

        $this->assertSame(0, $this->adapter->refreshCalls);
        // access_token 经 Encryptable cast 加密落库，须经模型读取才是明文
        $this->assertSame('old', $account->fresh()->access_token);
    }

    public function testSkipsAccountWithoutRefreshToken(): void
    {
        $account = $this->seedAccount(['refresh_token' => '']);
        // 直接写库模拟存储层空 refresh_token（绕过 Encryptable cast 对空串的加密）
        DB::table('erik_platform_accounts')->where('id', $account->id)->update(['refresh_token' => '']);

        (new TokenRefreshTask())->execute();

        $this->assertSame(0, $this->adapter->refreshCalls);
    }

    public function testSkipsInactiveAccount(): void
    {
        $this->seedAccount(['status' => 0]);

        (new TokenRefreshTask())->execute();

        $this->assertSame(0, $this->adapter->refreshCalls);
    }
}
