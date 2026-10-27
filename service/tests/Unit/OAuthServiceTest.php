<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * OAuthService 测试：SQLite + 探针 Adapter，覆盖 getAuthUrl / handleCallback /
 * refreshAccessToken 全流程及异常路径。
 */

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager as DB;
use InvalidArgumentException;
use plugin\ads_account\model\AuthToken;
use plugin\ads_account\model\PlatformAccount;
use plugin\ads_account\service\OAuthService;
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\PlatformAdapter;
use plugin\ads_platform\src\CampaignData;
use plugin\ads_platform\src\ReportRequest;
use RuntimeException;

class SpyOAuthAdapter implements PlatformAdapter
{
    public array $exchanged = [];
    public array $refreshed = [];
    public array $fetchedAccountInfos = [];

    public function code(): string { return 'spyoauth'; }
    public function name(): string { return 'Spy OAuth Adapter'; }
    public function capabilities(): array { return ['oauth', 'report']; }
    public function buildAuthUrl(string $redirectUri, string $state): string
    {
        return 'https://platform.example/oauth?redirect=' . urlencode($redirectUri) . '&state=' . $state;
    }
    public function exchangeToken(string $code, string $redirectUri): array
    {
        $this->exchanged[] = [$code, $redirectUri];
        return [
            'access_token'    => 'at-' . $code,
            'refresh_token'   => 'rt-' . $code,
            'expires_in'      => 7200,
            'advertiser_ids'  => ['adv-1', 'adv-2'],
        ];
    }
    public function refreshToken(string $refreshToken): array
    {
        $this->refreshed[] = $refreshToken;
        return ['access_token' => 'at-new', 'refresh_token' => 'rt-new', 'expires_in' => 86400];
    }
    public function fetchAccountInfo(string $accessToken): array
    {
        $this->fetchedAccountInfos[] = $accessToken;
        return [
            ['account_id_on_platform' => 'adv-1', 'account_name' => 'Account One'],
            ['account_id_on_platform' => 'adv-2', 'account_name' => 'Account Two'],
        ];
    }
    public function fetchCampaigns(string $accessToken, string $accountId): \Generator { yield from []; }
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator { yield from []; }
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator { yield from []; }
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator { yield from []; }
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string { return ''; }
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void {}
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void {}
}

class OAuthServiceTest extends SqliteTestCase
{
    protected SpyOAuthAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new SpyOAuthAdapter();
        AdapterRegistry::register($this->adapter);

        $this->exec('CREATE TABLE ads_auth_tokens (
            id TEXT PRIMARY KEY, tenant_id INT, platform TEXT, state TEXT, redirect_uri TEXT,
            expires_at TEXT, created_at TEXT, updated_at TEXT)');
        $this->exec('CREATE TABLE ads_platform_accounts (
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

    public function testGetAuthUrlCreatesTokenAndReturnsUrl(): void
    {
        $result = (new OAuthService())->getAuthUrl(1, 'spyoauth', 'http://cb.example/cb');

        $this->assertSame(32, strlen($result['state']));
        $this->assertStringContainsString('state=' . $result['state'], $result['auth_url']);
        $this->assertSame(1, AuthToken::where('state', $result['state'])->count());
    }

    public function testGetAuthUrlThrowsForUnsupportedPlatform(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new OAuthService())->getAuthUrl(1, 'nope', 'http://cb.example/cb');
    }

    public function testHandleCallbackExchangesCodeAndCreatesAccounts(): void
    {
        $service = new OAuthService();
        $state = $service->getAuthUrl(1, 'spyoauth', 'http://cb.example/cb')['state'];

        $account = $service->handleCallback(1, 'spyoauth', $state, 'code-1');

        $this->assertSame('adv-1', $account->account_id_on_platform);
        $this->assertSame('at-code-1', $account->access_token);
        $this->assertSame(0, AuthToken::where('state', $state)->count(), 'auth token should be consumed');
        $this->assertSame(['code-1', 'http://cb.example/cb'], $this->adapter->exchanged[0] ?? null);
        // fetchAccountInfo 应为每个 advertiser 建账
        $this->assertSame('Account Two', PlatformAccount::where('account_id_on_platform', 'adv-2')->first()->account_name);
    }

    public function testHandleCallbackRejectsInvalidState(): void
    {
        $this->expectException(RuntimeException::class);
        (new OAuthService())->handleCallback(1, 'spyoauth', 'no-such-state', 'code-1');
    }

    public function testRefreshAccessTokenUpdatesAccount(): void
    {
        $account = new PlatformAccount([
            'tenant_id' => 1, 'platform' => 'spyoauth', 'account_id_on_platform' => 'adv-1',
            'access_token' => 'old', 'refresh_token' => 'rt-old', 'status' => 1,
        ]);
        $account->save();

        (new OAuthService())->refreshAccessToken($account);

        $this->assertSame(['rt-old'], $this->adapter->refreshed);
        $this->assertSame('at-new', $account->fresh()->access_token);
        $this->assertSame('rt-new', $account->fresh()->refresh_token);
    }

    public function testRefreshAccessTokenSkipsAccountWithoutRefreshToken(): void
    {
        $account = new PlatformAccount([
            'tenant_id' => 1, 'platform' => 'spyoauth', 'account_id_on_platform' => 'adv-1',
            'access_token' => 'old', 'refresh_token' => '', 'status' => 1,
        ]);
        $account->save();

        (new OAuthService())->refreshAccessToken($account);

        $this->assertSame([], $this->adapter->refreshed);
    }
}
