<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace Tests\Unit;

use plugin\ads_report\service\ReportBuilder;
use PHPUnit\Framework\TestCase;

class ReportBuilderTest extends TestCase
{
    public function testMetricColumnMapping(): void
    {
        $reflection = new \ReflectionClass(ReportBuilder::class);
        $method = $reflection->getMethod('metricColumns');
        $method->setAccessible(true);

        $builder = new ReportBuilder();
        $result = $method->invoke($builder, ['cost', 'impressions', 'clicks']);

        $this->assertArrayHasKey('cost', $result);
        $this->assertArrayHasKey('impressions', $result);
        $this->assertArrayHasKey('clicks', $result);
        $this->assertStringContainsString('SUM(cost)', $result['cost']);
        $this->assertStringContainsString('SUM(impressions)', $result['impressions']);
        $this->assertStringContainsString('SUM(clicks)', $result['clicks']);
    }

    public function testDerivedMetricFormulas(): void
    {
        $reflection = new \ReflectionClass(ReportBuilder::class);
        $method = $reflection->getMethod('metricColumns');
        $method->setAccessible(true);

        $builder = new ReportBuilder();
        $result = $method->invoke($builder, ['ctr', 'cvr', 'roi']);

        $this->assertStringContainsString('SUM(clicks)/SUM(impressions)', $result['ctr']);
        $this->assertStringContainsString('SUM(conversions)/SUM(clicks)', $result['cvr']);
        // Source uses SUM(conversions)/SUM(cost)*100; substring match covers it
        $this->assertStringContainsString('SUM(conversions)/SUM(cost)', $result['roi']);
    }

    public function testDimensionColumnFiltering(): void
    {
        $reflection = new \ReflectionClass(ReportBuilder::class);
        $method = $reflection->getMethod('dimensionColumns');
        $method->setAccessible(true);

        $builder = new ReportBuilder();
        $result = $method->invoke($builder, ['platform', 'date', 'invalid_dim', 'campaign_id']);

        $this->assertContains('platform', $result);
        $this->assertContains('date', $result);
        $this->assertContains('campaign_id', $result);
        $this->assertNotContains('invalid_dim', $result);
    }
}
