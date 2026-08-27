<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * AttributionEngine 测试：distribute 各归因模型权重（反射调用 protected 方法，
 * 纯逻辑，不依赖 DB）；aggregate 汇总逻辑。
 */

namespace Tests\Unit;

use plugin\ads_report\service\AttributionEngine;
use PHPUnit\Framework\TestCase;

class AttributionEngineTest extends TestCase
{
    private AttributionEngine $engine;

    private function touchpoints(): array
    {
        // 4 个触点：campaign 1 最早（2026-08-01），campaign 4 最近（2026-08-14）
        return [
            ['campaign_id' => 1, 'platform' => 'a', 'date' => '2026-08-01', 'clicks' => 10],
            ['campaign_id' => 2, 'platform' => 'a', 'date' => '2026-08-05', 'clicks' => 20],
            ['campaign_id' => 3, 'platform' => 'b', 'date' => '2026-08-10', 'clicks' => 30],
            ['campaign_id' => 4, 'platform' => 'b', 'date' => '2026-08-14', 'clicks' => 40],
        ];
    }

    private function distribute(array $touchpoints, string $model, int $convTime = 1785628800): array
    {
        $method = new \ReflectionMethod(AttributionEngine::class, 'distribute');
        $method->setAccessible(true);
        return $method->invoke($this->engine, $touchpoints, $model, $convTime);
    }

    protected function setUp(): void
    {
        $this->engine = new AttributionEngine();
    }

    public function testFirstTouchGivesAllCreditToFirstTouchpoint(): void
    {
        $credits = $this->distribute($this->touchpoints(), 'first_touch');
        $this->assertSame(1.0, $credits[0]['credit']);
        $this->assertSame(1, $credits[0]['campaign_id']);
        $this->assertSame(0.0, $credits[1]['credit']);
    }

    public function testLastTouchGivesAllCreditToLastTouchpoint(): void
    {
        $credits = $this->distribute($this->touchpoints(), 'last_touch');
        $this->assertSame(4, $credits[3]['campaign_id']);
        $this->assertSame(1.0, $credits[3]['credit']);
    }

    public function testLinearSplitsEqually(): void
    {
        $credits = $this->distribute($this->touchpoints(), 'linear');
        foreach ($credits as $c) {
            $this->assertEqualsWithDelta(0.25, $c['credit'], 0.0001);
        }
    }

    public function testTimeDecayGivesRecentTouchpointsMoreCredit(): void
    {
        $convTime = strtotime('2026-08-16 12:00:00');
        $credits = $this->distribute($this->touchpoints(), 'time_decay', $convTime);

        $sum = array_sum(array_column($credits, 'credit'));
        $this->assertEqualsWithDelta(1.0, $sum, 0.0001);
        // 越接近转化时间的触点权重越大
        $this->assertGreaterThan($credits[0]['credit'], $credits[1]['credit']);
        $this->assertGreaterThan($credits[1]['credit'], $credits[2]['credit']);
        $this->assertGreaterThan($credits[2]['credit'], $credits[3]['credit']);
    }

    public function testPositionBasedWeights(): void
    {
        // n=4 → 首 40% + 末 40% + 中间均分 20%
        $credits = $this->distribute($this->touchpoints(), 'position_based');
        $this->assertEqualsWithDelta(0.4, $credits[0]['credit'], 0.0001);
        $this->assertEqualsWithDelta(0.1, $credits[1]['credit'], 0.0001);
        $this->assertEqualsWithDelta(0.1, $credits[2]['credit'], 0.0001);
        $this->assertEqualsWithDelta(0.4, $credits[3]['credit'], 0.0001);

        // n=3 → [0.4, 0.2, 0.4]
        $three = $this->distribute(array_slice($this->touchpoints(), 0, 3), 'position_based');
        $this->assertEqualsWithDelta(0.4, $three[0]['credit'], 0.0001);
        $this->assertEqualsWithDelta(0.2, $three[1]['credit'], 0.0001);
        $this->assertEqualsWithDelta(0.4, $three[2]['credit'], 0.0001);
    }

    public function testSingleTouchpointGetsFullCredit(): void
    {
        $credits = $this->distribute([$this->touchpoints()[0]], 'linear');
        $this->assertCount(1, $credits);
        $this->assertSame(1.0, $credits[0]['credit']);
    }

    public function testEmptyTouchpointsReturnEmpty(): void
    {
        $this->assertSame([], $this->distribute([], 'linear'));
    }

    public function testUnknownModelFallsBackToLastTouch(): void
    {
        $credits = $this->distribute($this->touchpoints(), 'mystery_model');
        $this->assertSame(1.0, $credits[3]['credit']);
    }

    public function testAggregateSumsCreditsByCampaign(): void
    {
        $aggregate = new \ReflectionMethod(AttributionEngine::class, 'aggregate');
        $aggregate->setAccessible(true);

        $result = $aggregate->invoke($this->engine, [
            [
                'value' => 100.0,
                'credits' => [
                    ['campaign_id' => 1, 'credit' => 0.4],
                    ['campaign_id' => 2, 'credit' => 0.6],
                ],
            ],
            [
                'value' => 50.0,
                'credits' => [
                    ['campaign_id' => 1, 'credit' => 0.6],
                    ['campaign_id' => 2, 'credit' => 0.4],
                ],
            ],
        ]);

        $this->assertSame(2, $result['total_conversions']);
        $this->assertSame(150.0, $result['total_value']);
        $byCampaign = [];
        foreach ($result['by_campaign'] as $c) {
            $byCampaign[$c['campaign_id']] = $c['credit'];
        }
        // 浮点累加（0.4+0.2=0.6000...001）需按精度比较
        $this->assertEqualsWithDelta(1.0, $byCampaign[1], 1e-9);
        $this->assertEqualsWithDelta(1.0, $byCampaign[2], 1e-9);
    }

    public function testModelConstantsDocumented(): void
    {
        foreach (['first_touch', 'last_touch', 'linear', 'time_decay', 'position_based'] as $m) {
            $this->assertArrayHasKey($m, AttributionEngine::MODELS);
        }
    }
}
