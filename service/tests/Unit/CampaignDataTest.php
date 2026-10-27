<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace Tests\Unit;

use plugin\ads_platform\src\CampaignData;
use PHPUnit\Framework\TestCase;

class CampaignDataTest extends TestCase
{
    public function testFromArray(): void
    {
        $data = CampaignData::fromArray([
            'name' => 'Test Campaign',
            'daily_budget' => 20000,
            'total_budget' => 100000,
        ]);

        $this->assertEquals('Test Campaign', $data->name);
        $this->assertEquals(20000, $data->dailyBudget);
        $this->assertEquals(100000, $data->totalBudget);
    }

    public function testFromArrayDefaults(): void
    {
        $data = CampaignData::fromArray(['name' => 'Minimal']);
        $this->assertEquals(0, $data->dailyBudget);
        $this->assertNull($data->totalBudget);
        $this->assertNull($data->startDate);
        $this->assertNull($data->endDate);
    }

    public function testExtraFields(): void
    {
        $data = CampaignData::fromArray([
            'name' => 'Test',
            'extra' => ['custom_key' => 'value'],
        ]);
        $this->assertEquals('value', $data->extra['custom_key']);
    }
}
