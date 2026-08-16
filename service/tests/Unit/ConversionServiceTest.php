<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * ConversionService::validateAndNormalize 纯逻辑测试（注入固定 now，不依赖 DB）。
 */
namespace Tests\Unit;

use Carbon\Carbon;
use InvalidArgumentException;
use plugin\ads_report\service\ConversionService;
use PHPUnit\Framework\TestCase;

class ConversionServiceTest extends TestCase
{
    /** 测试固定当前时间：2026-08-16 12:00:00 */
    private function fixedNow(): Carbon
    {
        return Carbon::create(2026, 8, 16, 12, 0, 0);
    }

    private function validPayload(): array
    {
        return [
            'platform'        => 'juliang',
            'campaign_id'     => 1001,
            'order_id'        => 'ORDER-20260816-001',
            'conversion_time' => '2026-08-16 10:00:00',
            'value'           => 9800,
        ];
    }

    public function testValidateAndNormalizeFillsDefaults(): void
    {
        $data = ConversionService::validateAndNormalize($this->validPayload(), $this->fixedNow());

        $this->assertSame('juliang', $data['platform']);
        $this->assertSame(1001, $data['campaign_id']);
        $this->assertSame('ORDER-20260816-001', $data['order_id']);
        $this->assertSame('2026-08-16 10:00:00', $data['conversion_time']);
        $this->assertSame(9800.0, $data['value']);
        // 默认值
        $this->assertSame('CNY', $data['currency']);
        $this->assertSame('api', $data['channel']);
    }

    public function testValidateAndNormalizeRespectsCurrencyAndChannelOverrides(): void
    {
        $payload = $this->validPayload();
        $payload['currency'] = 'usd';
        $payload['channel'] = '  sdk  ';

        $data = ConversionService::validateAndNormalize($payload, $this->fixedNow());

        $this->assertSame('USD', $data['currency']);
        $this->assertSame('sdk', $data['channel']);
    }

    public function testValidateAndNormalizeRejectsMissingRequiredFields(): void
    {
        foreach (['platform', 'campaign_id', 'order_id', 'conversion_time', 'value'] as $field) {
            $payload = $this->validPayload();
            unset($payload[$field]);

            $thrown = false;
            try {
                ConversionService::validateAndNormalize($payload, $this->fixedNow());
            } catch (InvalidArgumentException) {
                $thrown = true;
            }
            $this->assertTrue($thrown, "expected InvalidArgumentException when '{$field}' is missing");
        }
    }

    public function testValidateAndNormalizeRejectsNegativeValue(): void
    {
        $payload = $this->validPayload();
        $payload['value'] = -1;

        $this->expectException(InvalidArgumentException::class);
        ConversionService::validateAndNormalize($payload, $this->fixedNow());
    }

    public function testValidateAndNormalizeRejectsInvalidTimeFormat(): void
    {
        foreach (['2026-08-16 10:00', '2026/08/16 10:00:00', 'garbage', '2026-13-40 25:00:00', '2026-02-30 10:00:00'] as $bad) {
            $payload = $this->validPayload();
            $payload['conversion_time'] = $bad;

            $thrown = false;
            try {
                ConversionService::validateAndNormalize($payload, $this->fixedNow());
            } catch (InvalidArgumentException) {
                $thrown = true;
            }
            $this->assertTrue($thrown, "expected InvalidArgumentException for conversion_time '{$bad}'");
        }
    }

    public function testValidateAndNormalizeRejectsTimeBeyondNowPlusOneHour(): void
    {
        $payload = $this->validPayload();
        // now(12:00) + 1h = 13:00，13:00:01 即超限
        $payload['conversion_time'] = '2026-08-16 13:00:01';

        $this->expectException(InvalidArgumentException::class);
        ConversionService::validateAndNormalize($payload, $this->fixedNow());
    }

    public function testValidateAndNormalizeAcceptsTimeWithinNowPlusOneHour(): void
    {
        $payload = $this->validPayload();
        $payload['conversion_time'] = '2026-08-16 12:59:59';

        $data = ConversionService::validateAndNormalize($payload, $this->fixedNow());
        $this->assertSame('2026-08-16 12:59:59', $data['conversion_time']);
    }
}
