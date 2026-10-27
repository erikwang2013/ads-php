<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace Tests\Unit;

use plugin\ads_platform\src\CircuitBreaker;
use PHPUnit\Framework\TestCase;

class CircuitBreakerTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset static state + config so tests are order-independent
        $ref = new \ReflectionClass(CircuitBreaker::class);
        $prop = $ref->getProperty('state');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
        CircuitBreaker::$failureThreshold = 5;
        CircuitBreaker::$cooldownSeconds = 30;
    }

    public function testInitiallyClosedAndAllowed(): void
    {
        $this->assertSame(CircuitBreaker::CLOSED, CircuitBreaker::state('p1'));
        $this->assertFalse(CircuitBreaker::isOpen('p1'));
        $this->assertTrue(CircuitBreaker::allow('p1'));
    }

    public function testOpensAfterFailureThreshold(): void
    {
        CircuitBreaker::$failureThreshold = 2;
        CircuitBreaker::failure('p1');
        $this->assertSame(CircuitBreaker::CLOSED, CircuitBreaker::state('p1'), 'below threshold stays closed');
        CircuitBreaker::failure('p1');
        $this->assertSame(CircuitBreaker::OPEN, CircuitBreaker::state('p1'));
        $this->assertTrue(CircuitBreaker::isOpen('p1'));
        $this->assertFalse(CircuitBreaker::allow('p1'), 'open blocks calls');
    }

    public function testSuccessResetsFailureCount(): void
    {
        CircuitBreaker::$failureThreshold = 3;
        CircuitBreaker::failure('p1');
        CircuitBreaker::failure('p1');
        CircuitBreaker::success('p1');
        CircuitBreaker::failure('p1');
        CircuitBreaker::failure('p1');
        $this->assertSame(CircuitBreaker::CLOSED, CircuitBreaker::state('p1'), 'success between failures resets the streak');
    }

    public function testHalfOpenProbeAfterCooldown(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::failure('p1');
        $this->assertSame(CircuitBreaker::OPEN, CircuitBreaker::state('p1'));

        // Before cooldown elapses: still blocked
        CircuitBreaker::$cooldownSeconds = 30;
        $this->assertFalse(CircuitBreaker::allow('p1'));

        // Cooldown elapsed -> probe allowed, state HALF_OPEN
        CircuitBreaker::$cooldownSeconds = 0;
        $this->assertTrue(CircuitBreaker::allow('p1'));
        $this->assertSame(CircuitBreaker::HALF_OPEN, CircuitBreaker::state('p1'));
    }

    public function testHalfOpenSuccessRecoversClosed(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::$cooldownSeconds = 0;
        CircuitBreaker::failure('p1');
        $this->assertTrue(CircuitBreaker::allow('p1')); // -> HALF_OPEN probe
        CircuitBreaker::success('p1');
        $this->assertSame(CircuitBreaker::CLOSED, CircuitBreaker::state('p1'));
        $this->assertTrue(CircuitBreaker::allow('p1'));
    }

    public function testHalfOpenFailureReopens(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::$cooldownSeconds = 0;
        CircuitBreaker::failure('p1');
        $this->assertTrue(CircuitBreaker::allow('p1')); // -> HALF_OPEN probe
        CircuitBreaker::failure('p1');
        $this->assertSame(CircuitBreaker::OPEN, CircuitBreaker::state('p1'), 'failed probe goes straight back to OPEN');
        CircuitBreaker::$cooldownSeconds = 30;
        $this->assertFalse(CircuitBreaker::allow('p1'), 're-opened breaker blocks until cooldown elapses');
    }

    public function testFailuresArePerPlatform(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::failure('pA');
        $this->assertTrue(CircuitBreaker::isOpen('pA'));
        $this->assertFalse(CircuitBreaker::isOpen('pB'), 'other platforms unaffected');
        $this->assertTrue(CircuitBreaker::allow('pB'));
    }

    public function testResetClearsState(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::failure('p1');
        $this->assertTrue(CircuitBreaker::isOpen('p1'));
        CircuitBreaker::reset('p1');
        $this->assertSame(CircuitBreaker::CLOSED, CircuitBreaker::state('p1'));
        $this->assertTrue(CircuitBreaker::allow('p1'));
    }

    public function testFailureWhileOpenIsNoop(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::failure('p1'); // -> OPEN
        $ref = new \ReflectionClass(CircuitBreaker::class);
        $prop = $ref->getProperty('state');
        $prop->setAccessible(true);
        $stateBefore = $prop->getValue(null)['p1'];
        $this->assertSame(CircuitBreaker::OPEN, $stateBefore['state']);

        CircuitBreaker::failure('p1'); // repeated failures during OPEN
        $stateAfter = $prop->getValue(null)['p1'];
        $this->assertSame($stateBefore, $stateAfter, 'failure() while OPEN must not mutate state or extend cooldown');
        $this->assertFalse(CircuitBreaker::allow('p1'), 'still blocked after repeated failures');
    }

    public function testAllCallsBlockedWhileOpen(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::failure('p1');
        for ($i = 0; $i < 3; $i++) {
            $this->assertFalse(CircuitBreaker::allow('p1'), "iteration {$i} must stay blocked");
        }
        $this->assertSame(CircuitBreaker::OPEN, CircuitBreaker::state('p1'));
    }
}
