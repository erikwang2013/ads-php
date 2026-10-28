<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 平台与账户：platforms / oauth-url / callback / accounts CRUD / sync。
 * 外部平台调用由 ApiTestCase 注入的 mock 适配器隔离。
 */

namespace Tests\Integration;

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_api\controller\v1\PlatformController;
use plugin\ads_api\controller\v1\AccountController;

class PlatformAccountApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // OAuth redirect 白名单已收紧：未配置时只允许 APP_URL 同 host
        putenv('OAUTH_ALLOWED_REDIRECTS=https://app.example/*');
    }

    protected function tearDown(): void
    {
        putenv('OAUTH_ALLOWED_REDIRECTS');
        parent::tearDown();
    }

    public function testPlatformListContainsMockAdapter(): void
    {
        $body = $this->assertSuccess((new PlatformController())->index());
        $codes = array_column($body['data'], 'code');
        $this->assertContains('mock', $codes);
    }

    public function testOauthUrlRequiresRedirectUri(): void
    {
        $request = $this->authedRequest('GET', '/api/platforms/mock/oauth-url');
        $body = $this->json((new PlatformController())->oauthUrl($request, 'mock'));
        $this->assertEquals(1, $body['code']);
    }

    public function testOauthUrlUnsupportedPlatform(): void
    {
        $request = $this->authedRequest('GET', '/api/platforms/nope/oauth-url', [], [], [
            'redirect_uri' => 'https://app.example/callback',
        ]);
        $body = $this->json((new PlatformController())->oauthUrl($request, 'nope'));
        $this->assertEquals(1, $body['code']);
        $this->assertStringContainsString('Unsupported', $body['message']);
    }

    public function testOauthUrlSuccess(): void
    {
        $request = $this->authedRequest('GET', '/api/platforms/mock/oauth-url', [], [], [
            'redirect_uri' => 'https://app.example/callback',
        ]);
        $body = $this->assertSuccess((new PlatformController())->oauthUrl($request, 'mock'));
        $this->assertStringContainsString('state=', $body['data']['auth_url']);
        $this->assertNotEmpty($body['data']['state']);
        $this->assertEquals(1, DB::table('ads_auth_tokens')->count());
    }

    public function testCallbackWithInvalidState(): void
    {
        $request = $this->authedRequest('POST', '/api/platforms/mock/callback', [
            'state' => 'bogus-state',
            'code'  => 'auth-code-1',
        ]);
        $body = $this->json((new PlatformController())->callback($request, 'mock'));
        $this->assertEquals(1, $body['code']);
        $this->assertStringContainsString('Invalid or expired state', $body['message']);
    }

    public function testAccountsIndexEmptyAndPaginated(): void
    {
        $request = $this->authedRequest('GET', '/api/accounts');
        $body = $this->assertSuccess((new AccountController())->index($request));
        $this->assertEquals([], $body['data']['list']);
        $this->assertEquals(0, $body['data']['pagination']['total']);
        $this->assertEquals(1, $body['data']['pagination']['page']);
    }

    public function testAccountsIndexWithSeededAccount(): void
    {
        $this->seedAccount(['platform' => 'mock']);

        $request = $this->authedRequest('GET', '/api/accounts');
        $body = $this->assertSuccess((new AccountController())->index($request));
        $this->assertEquals(1, $body['data']['pagination']['total']);
        $this->assertEquals('Mock Account', $body['data']['list'][0]['account_name']);

        // 平台筛选
        $filtered = $this->authedRequest('GET', '/api/accounts', [], [], ['platform' => 'mock']);
        $body = $this->assertSuccess((new AccountController())->index($filtered));
        $this->assertEquals(1, $body['data']['pagination']['total']);
    }

    public function testAccountShow(): void
    {
        $id = $this->seedAccount();
        $body = $this->assertSuccess((new AccountController())->show($id));
        $this->assertEquals($id, $body['data']['id']);
    }

    public function testAccountResponsesDoNotLeakTokens(): void
    {
        $this->seedAccount(['access_token' => 'secret-access-token', 'refresh_token' => 'secret-refresh-token']);

        $indexRequest = $this->authedRequest('GET', '/api/accounts');
        $indexResponse = (new AccountController())->index($indexRequest);
        $indexBody = $this->assertSuccess($indexResponse);
        $this->assertNotEmpty($indexBody['data']['list']);
        foreach ($indexBody['data']['list'] as $row) {
            $this->assertArrayNotHasKey('access_token', $row);
            $this->assertArrayNotHasKey('refresh_token', $row);
        }
        $this->assertStringNotContainsString('secret-access-token', $indexResponse->rawBody());
        $this->assertStringNotContainsString('secret-refresh-token', $indexResponse->rawBody());

        $showResponse = (new AccountController())->show($this->seedAccount([
            'access_token' => 'secret-access-token',
            'refresh_token' => 'secret-refresh-token',
        ]));
        $showBody = $this->assertSuccess($showResponse);
        $this->assertArrayNotHasKey('access_token', $showBody['data']);
        $this->assertArrayNotHasKey('refresh_token', $showBody['data']);
        $this->assertStringNotContainsString('secret-access-token', $showResponse->rawBody());
        $this->assertStringNotContainsString('secret-refresh-token', $showResponse->rawBody());
    }

    public function testAccountDestroyDisablesAccount(): void
    {
        $id = $this->seedAccount();
        $this->assertSuccess((new AccountController())->destroy($id));

        $row = DB::table('ads_platform_accounts')->find($id);
        $this->assertEquals(0, $row->status);
    }

    public function testAccountSyncUpdatesLastSyncAt(): void
    {
        $id = $this->seedAccount(['last_sync_at' => null]);
        $request = $this->authedRequest('POST', "/api/accounts/$id/sync");

        $this->assertSuccess((new AccountController())->sync($request, $id));
        $this->assertNotNull(DB::table('ads_platform_accounts')->find($id)->last_sync_at);
    }

    public function testAccountSyncRejectsUnknownAccount(): void
    {
        $request = $this->authedRequest('POST', '/api/accounts/999999/sync');
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        (new AccountController())->sync($request, 999999);
    }
}
