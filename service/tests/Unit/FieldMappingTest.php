<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace Tests\Unit;

use plugin\ads_platform\src\FieldMapping;
use PHPUnit\Framework\TestCase;

class FieldMappingTest extends TestCase
{
    public function testBasicFieldMapping(): void
    {
        $mapping = new FieldMapping([
            'campaign_id' => 'platform_campaign_id',
            'campaign_name' => 'name',
        ], []);

        $result = $mapping->map(['campaign_id' => '123', 'campaign_name' => 'Test']);
        $this->assertEquals('123', $result['platform_campaign_id']);
        $this->assertEquals('Test', $result['name']);
    }

    public function testUnknownFieldsGoToExtra(): void
    {
        $mapping = new FieldMapping(['id' => 'id'], []);
        $result = $mapping->map(['id' => '1', 'custom_field' => 'value']);
        $this->assertArrayHasKey('extra', $result);
        $this->assertEquals('value', $result['extra']['custom_field']);
    }

    public function testStatusMapping(): void
    {
        $mapping = new FieldMapping(
            ['status' => 'status'],
            ['ACTIVE' => 'enabled', 'PAUSED' => 'paused']
        );
        $result = $mapping->map(['status' => 'ACTIVE']);
        $this->assertEquals('enabled', $result['status']);
    }

    public function testValueTransformer(): void
    {
        $mapping = new FieldMapping(
            ['budget' => 'daily_budget'],
            [],
            function (array $unified): array {
                $unified['daily_budget'] = (int) ($unified['daily_budget'] * 100);
                return $unified;
            }
        );
        $result = $mapping->map(['budget' => 2.5]);
        $this->assertEquals(250, $result['daily_budget']);
    }

    public function testEmptyInput(): void
    {
        $mapping = new FieldMapping([], []);
        $result = $mapping->map([]);
        $this->assertEquals(['extra' => []], $result);
    }
}
