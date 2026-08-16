<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * QuotaService 纯逻辑测试（不依赖 DB）。
 */
namespace Tests\Unit;

use plugin\ads_tenant\service\QuotaService;
use PHPUnit\Framework\TestCase;

class QuotaServiceTest extends TestCase
{
    public function testResolveTierMapsTenantPlans(): void
    {
        $this->assertSame('lite', QuotaService::resolveTier('free'));
        $this->assertSame('standard', QuotaService::resolveTier('pro'));
        $this->assertSame('full', QuotaService::resolveTier('enterprise'));
    }

    public function testResolveTierDefaultsToFullForMissingOrUnknownPlan(): void
    {
        $this->assertSame('full', QuotaService::resolveTier(null));
        $this->assertSame('full', QuotaService::resolveTier(''));
        $this->assertSame('full', QuotaService::resolveTier('platinum'));
    }

    public function testLimitsForEachTier(): void
    {
        $this->assertSame(
            ['account_limit' => 5, 'campaign_limit' => 50, 'sync_daily' => 100],
            QuotaService::limitsFor('lite')
        );
        $this->assertSame(
            ['account_limit' => 20, 'campaign_limit' => 500, 'sync_daily' => 1000],
            QuotaService::limitsFor('standard')
        );
        $this->assertSame(
            ['account_limit' => 100, 'campaign_limit' => 5000, 'sync_daily' => 10000],
            QuotaService::limitsFor('full')
        );
    }

    public function testLimitsForUnknownTierFallsBackToFull(): void
    {
        $this->assertSame(QuotaService::limitsFor('full'), QuotaService::limitsFor('gold'));
    }

    public function testCheckQuotaBoundary(): void
    {
        $below = QuotaService::checkQuota(4, 5);
        $this->assertFalse($below['exceeded']);
        $this->assertSame(4, $below['used']);
        $this->assertSame(5, $below['limit']);
        $this->assertSame(1, $below['remaining']);

        // 已满即视为超限
        $atLimit = QuotaService::checkQuota(5, 5);
        $this->assertTrue($atLimit['exceeded']);
        $this->assertSame(0, $atLimit['remaining']);

        $over = QuotaService::checkQuota(7, 5);
        $this->assertTrue($over['exceeded']);
        $this->assertSame(0, $over['remaining']);
    }
}
