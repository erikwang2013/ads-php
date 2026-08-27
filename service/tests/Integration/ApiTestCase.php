<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * API 集成测试基类 — 直接实例化控制器 + 构造 Webman Request，
 * 与既有 Integration 测试（AuthControllerTest）模式保持一致。
 *
 * 测试数据写 ads_test 库（erik_ 表与生产同名，bootstrap 已建表），
 * 不依赖外部广告平台：通过 AdapterRegistry 注入 mock 适配器。
 */

namespace Tests\Integration;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;
use erik\support\JwtService;
use erik\support\CacheService;
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\PlatformAdapter;
use plugin\ads_platform\src\CampaignData;
use plugin\ads_platform\src\ReportRequest;

abstract class ApiTestCase extends TestCase
{
    protected int $userId = 9999;
    protected int $tenantId = 1;

    /** 被测试写入的业务表，setUp 时清空保证用例可重复 */
    protected const DIRTY_TABLES = [
        'erik_platform_accounts', 'erik_campaigns', 'erik_ad_groups', 'erik_creatives',
        'erik_alert_rules', 'erik_alert_logs', 'erik_bid_rules', 'erik_bid_logs',
        'erik_targeting_templates', 'erik_notifications', 'erik_assets', 'erik_sync_errors',
        'erik_conversions', 'erik_auth_tokens', 'erik_attribution_results', 'erik_report_metrics',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        putenv('JWT_SECRET=test-jwt-secret-at-least-16-chars-long');
        \app\support\ApiResponse::setLang('zh-CN');

        CacheService::flush('cache:');
        $this->seedUser();
        $this->seedTenant();
        $this->registerMockAdapter();
        foreach (self::DIRTY_TABLES as $table) {
            DB::table($table)->delete();
        }
    }

    protected function seedUser(): void
    {
        DB::table('admin_users')->updateOrInsert(['username' => 'testuser'], [
            'id'       => $this->userId,
            'username' => 'testuser',
            'password' => password_hash('testpass', PASSWORD_BCRYPT),
            'name'     => 'Test User',
            'role_id'  => 1,
            'status'   => 1,
        ]);
    }

    protected function seedTenant(): void
    {
        DB::table('erik_tenants')->updateOrInsert(['id' => $this->tenantId], [
            'id'     => $this->tenantId,
            'name'   => 'Test Tenant',
            'plan'   => 'enterprise',
            'status' => 1,
        ]);
    }

    /** 注入 mock 适配器，隔离外部广告平台（juliang/google/... 均不可达） */
    protected function registerMockAdapter(): void
    {
        if (AdapterRegistry::has('mock')) {
            return;
        }
        AdapterRegistry::register(new class implements PlatformAdapter {
            public function code(): string { return 'mock'; }
            public function name(): string { return 'Mock Platform'; }
            public function capabilities(): array
            {
                return ['oauth', 'campaigns', 'ad_groups', 'creatives', 'reports'];
            }
            public function buildAuthUrl(string $redirectUri, string $state): string
            {
                return 'https://mock.example/oauth?state=' . $state . '&redirect_uri=' . urlencode($redirectUri);
            }
            public function exchangeToken(string $code, string $redirectUri): array
            {
                return ['access_token' => 'mock-token', 'refresh_token' => 'mock-refresh', 'expires_in' => 3600];
            }
            public function refreshToken(string $refreshToken): array
            {
                return ['access_token' => 'mock-token-2'];
            }
            public function fetchAccountInfo(string $accessToken): array
            {
                return ['id' => 'mock-acc', 'name' => 'Mock Account'];
            }
            public function fetchCampaigns(string $accessToken, string $accountId): \Generator
            {
                return; yield;
            }
            public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator
            {
                return; yield;
            }
            public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator
            {
                return; yield;
            }
            public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator
            {
                return; yield;
            }
            public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string
            {
                return 'pc-' . bin2hex(random_bytes(4));
            }
            public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void {}
            public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void {}
            public function createAdGroup(string $accessToken, string $accountId, string $campaignId, array $data): string
            {
                return 'pc-' . bin2hex(random_bytes(4));
            }
            public function updateAdGroup(string $accessToken, string $accountId, string $campaignId, string $platformId, array $data): void {}
            public function toggleAdGroup(string $accessToken, string $accountId, string $campaignId, string $platformId, bool $enabled): void {}
        });
    }

    /** 构造原始 HTTP 请求（查询串、JSON body、自定义头） */
    protected function makeRequest(string $method, string $path, array $body = [], array $headers = [], array $query = []): Request
    {
        if ($query) {
            $path .= (str_contains($path, '?') ? '&' : '?') . http_build_query($query);
        }
        $raw = "Host: localhost\r\n";
        foreach ($headers as $k => $v) {
            $raw .= "$k: $v\r\n";
        }
        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
            $raw .= "Content-Type: application/json\r\n";
            $raw .= "Content-Length: " . strlen($jsonBody) . "\r\n";
        }
        return new Request("$method $path HTTP/1.1\r\n$raw\r\n$jsonBody");
    }

    /** 已认证请求（userId/tenantId 由中间件在真实运行时注入，此处直接赋值） */
    protected function authedRequest(string $method, string $path, array $body = [], array $headers = [], array $query = []): Request
    {
        $request = $this->makeRequest($method, $path, $body, $headers, $query);
        $request->userId = $this->userId;
        $request->tenantId = $this->tenantId;
        return $request;
    }

    protected function token(array $payload = []): string
    {
        return JwtService::encode(array_merge(['uid' => $this->userId, 'tid' => $this->tenantId], $payload));
    }

    protected function json(Response $response): array
    {
        return json_decode($response->rawBody(), true) ?? [];
    }

    /** 快捷断言：ApiResponse::success 结构（code=0, HTTP 200） */
    protected function assertSuccess(Response $response, string $message = ''): array
    {
        $this->assertEquals(200, $response->getStatusCode(), $message);
        $body = $this->json($response);
        $this->assertEquals(0, $body['code'] ?? 'no-code', $message);
        return $body;
    }

    /** 快捷断言：ApiResponse::error 结构（code!=0） */
    protected function assertError(Response $response, int $expectedCode): array
    {
        $body = $this->json($response);
        $this->assertEquals($expectedCode, $body['code'] ?? 'no-code');
        return $body;
    }

    /** 生成业务表 snowflake 风格 BIGINT 主键（业务表均无 AUTO_INCREMENT） */
    protected function nextId(): int
    {
        return (int) ((microtime(true) - 1700000000) * 1000) << 12 | random_int(0, 4095);
    }

    /** 在 erik_campaigns 中直接插入一条测试计划，返回 id */
    protected function seedCampaign(array $overrides = []): int
    {
        $id = $overrides['id'] ?? $this->nextId();
        DB::table('erik_campaigns')->insert(array_merge([
            'id'                   => $id,
            'tenant_id'            => $this->tenantId,
            'platform_account_id'  => $this->seedAccount(),
            'platform'             => 'mock',
            'platform_campaign_id' => 'pc-seed-1',
            'name'                 => '种子计划',
            'daily_budget'         => 10000,
            'total_budget'         => 0,
            'status'               => 'enabled',
            'extra'                => '{}',
            'created_at'           => now(),
            'updated_at'           => now(),
        ], $overrides));
        return $id;
    }

    /** 在 erik_platform_accounts 中插入测试账户，返回 id */
    protected function seedAccount(array $overrides = []): int
    {
        $id = $overrides['id'] ?? $this->nextId();
        DB::table('erik_platform_accounts')->insert(array_merge([
            'id'                     => $id,
            'tenant_id'              => $this->tenantId,
            'platform'               => 'mock',
            'account_id_on_platform' => 'mock-acc-' . substr(bin2hex(random_bytes(4)), 0, 8),
            'account_name'           => 'Mock Account',
            'access_token'           => 'mock-access',
            'refresh_token'          => 'mock-refresh',
            'status'                 => 1,
            'sync_enabled'           => 1,
            'created_at'             => now(),
            'updated_at'             => now(),
        ], $overrides));
        return $id;
    }
}
