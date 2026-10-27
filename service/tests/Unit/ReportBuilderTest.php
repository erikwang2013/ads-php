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

    public function testDerivedMetricFormulasComputedInPhpBcmath(): void
    {
        $builder = new ReportBuilder();
        $derive = new \ReflectionMethod(ReportBuilder::class, 'deriveMetric');
        $derive->setAccessible(true);

        $row = ['impressions' => 2, 'clicks' => 3, 'conversions' => 2, 'cost' => 1234];
        $this->assertSame('1.500000', $derive->invoke($builder, 'ctr', $row));    // 3/2, scale 6
        $this->assertSame('0.020000', $derive->invoke($builder, 'cvr', ['clicks' => 100, 'conversions' => 2]));
        $this->assertSame('22.04', $derive->invoke($builder, 'cpc', ['cost' => 1234, 'clicks' => 56])); // 1234/56, scale 2
        $this->assertSame('617000.00', $derive->invoke($builder, 'cpm', $row));   // 1234*1000/2
        $this->assertSame('1.43', $derive->invoke($builder, 'roi', ['cost' => 140, 'conversions' => 2])); // 2*100/140

        // 分母 ≤ 0 → scale 位 0 占位（等价原 SQL ELSE 0）
        $this->assertSame('0.000000', $derive->invoke($builder, 'ctr', ['clicks' => 0, 'impressions' => 0]));
        $this->assertSame('0.00', $derive->invoke($builder, 'cpc', ['clicks' => 0, 'cost' => 100]));

        // metricColumns 只含基表整数 SUM，派生指标不在其中（全部 PHP 侧计算）
        $metricCols = new \ReflectionMethod(ReportBuilder::class, 'metricColumns');
        $metricCols->setAccessible(true);
        $this->assertSame([], $metricCols->invoke($builder, ['ctr', 'cvr', 'roi']));
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
