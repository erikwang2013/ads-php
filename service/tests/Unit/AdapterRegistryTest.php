<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace Tests\Unit;

use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\PlatformAdapter;
use plugin\ads_platform\src\CampaignData;
use plugin\ads_platform\src\ReportRequest;
use PHPUnit\Framework\TestCase;

// Mock adapter for testing
class MockAdapter implements PlatformAdapter
{
    public function code(): string { return 'mock'; }
    public function name(): string { return 'Mock Adapter'; }
    public function capabilities(): array { return ['report']; }
    public function buildAuthUrl(string $redirectUri, string $state): string { return ''; }
    public function exchangeToken(string $code, string $redirectUri): array { return []; }
    public function refreshToken(string $refreshToken): array { return []; }
    public function fetchAccountInfo(string $accessToken): array { return []; }
    public function fetchCampaigns(string $accessToken, string $accountId): \Generator { yield from []; }
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator { yield from []; }
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator { yield from []; }
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator { yield from []; }
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string { return ''; }
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void {}
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void {}
}

class AdapterRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset static state
        $ref = new \ReflectionClass(AdapterRegistry::class);
        $prop = $ref->getProperty('adapters');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    public function testRegisterAndGet(): void
    {
        $adapter = new MockAdapter();
        AdapterRegistry::register($adapter);
        $this->assertSame($adapter, AdapterRegistry::get('mock'));
    }

    public function testHas(): void
    {
        $this->assertFalse(AdapterRegistry::has('nonexistent'));
        AdapterRegistry::register(new MockAdapter());
        $this->assertTrue(AdapterRegistry::has('mock'));
    }

    public function testAll(): void
    {
        AdapterRegistry::register(new MockAdapter());
        $all = AdapterRegistry::all();
        $this->assertCount(1, $all);
        $this->assertEquals('mock', $all[0]['code']);
        $this->assertEquals('Mock Adapter', $all[0]['name']);
    }

    public function testGetNonexistentReturnsNull(): void
    {
        $this->assertNull(AdapterRegistry::get('nonexistent'));
    }
}
