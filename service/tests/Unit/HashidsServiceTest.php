<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace Tests\Unit;

use erik\support\HashidsService;
use PHPUnit\Framework\TestCase;

class HashidsServiceTest extends TestCase
{
    protected HashidsService $service;

    protected function setUp(): void
    {
        $this->service = new HashidsService();
    }

    public function testEncodeDecodeRoundTrip(): void
    {
        $encoded = $this->service->encode(123456789);
        $decoded = $this->service->decode($encoded);
        $this->assertEquals(123456789, $decoded);
    }

    public function testEncodeProducesString(): void
    {
        $result = $this->service->encode(1);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testDifferentIdsProduceDifferentHashes(): void
    {
        $a = $this->service->encode(100);
        $b = $this->service->encode(200);
        $this->assertNotEquals($a, $b);
    }

    public function testSameIdProducesSameHash(): void
    {
        $a = $this->service->encode(500);
        $b = $this->service->encode(500);
        $this->assertEquals($a, $b);
    }

    public function testDecodeInvalidHashReturnsZero(): void
    {
        $result = $this->service->decode('!!!invalid!!!');
        $this->assertEquals(0, $result);
    }
}
