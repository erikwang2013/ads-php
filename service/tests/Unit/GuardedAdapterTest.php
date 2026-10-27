<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace Tests\Unit;

use plugin\ads_platform\src\CampaignData;
use plugin\ads_platform\src\CircuitBreaker;
use plugin\ads_platform\src\CircuitBreakerOpenException;
use plugin\ads_platform\src\GuardedAdapter;
use plugin\ads_platform\src\PlatformAdapter;
use plugin\ads_platform\src\ReportRequest;
use PHPUnit\Framework\TestCase;

/**
 * Scriptable adapter stub: each method dispatches to $script[method] if set.
 */
class ScriptedAdapter implements PlatformAdapter
{
    public array $script = [];

    public function code(): string { return 'stub'; }
    public function name(): string { return 'Stub'; }
    public function capabilities(): array { return ['report']; }
    public function buildAuthUrl(string $redirectUri, string $state): string { return $this->run(__FUNCTION__, func_get_args()); }
    public function exchangeToken(string $code, string $redirectUri): array { return $this->run(__FUNCTION__, func_get_args()); }
    public function refreshToken(string $refreshToken): array { return $this->run(__FUNCTION__, func_get_args()); }
    public function fetchAccountInfo(string $accessToken): array { return $this->run(__FUNCTION__, func_get_args()); }
    public function fetchCampaigns(string $accessToken, string $accountId): \Generator { return $this->run(__FUNCTION__, func_get_args()); }
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator { return $this->run(__FUNCTION__, func_get_args()); }
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator { return $this->run(__FUNCTION__, func_get_args()); }
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator { return $this->run(__FUNCTION__, func_get_args()); }
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string { return $this->run(__FUNCTION__, func_get_args()); }
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void { $this->run(__FUNCTION__, func_get_args()); }
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void { $this->run(__FUNCTION__, func_get_args()); }

    private function run(string $method, array $args)
    {
        return ($this->script[$method] ?? fn () => null)(...$args);
    }
}

class GuardedAdapterTest extends TestCase
{
    private ScriptedAdapter $adapter;
    private GuardedAdapter $proxy;

    protected function setUp(): void
    {
        $ref = new \ReflectionClass(CircuitBreaker::class);
        $prop = $ref->getProperty('state');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
        CircuitBreaker::$failureThreshold = 5;
        CircuitBreaker::$cooldownSeconds = 30;
        $this->adapter = new ScriptedAdapter();
        $this->proxy = new GuardedAdapter($this->adapter);
    }

    public function testPassthroughAndSuccessRecorded(): void
    {
        $this->adapter->script['exchangeToken'] = fn () => ['access_token' => 'tok'];
        $this->assertSame(['access_token' => 'tok'], $this->proxy->exchangeToken('c', 'r'));
        $this->assertSame(CircuitBreaker::CLOSED, CircuitBreaker::state('stub'));
    }

    public function testMetadataMethodsNotGuardedWhenOpen(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::failure('stub'); // -> OPEN
        $this->assertSame('stub', $this->proxy->code());
        $this->assertSame(['report'], $this->proxy->capabilities());
    }

    public function testFailureCountsAndRethrows(): void
    {
        CircuitBreaker::$failureThreshold = 2;
        $this->adapter->script['exchangeToken'] = fn () => throw new \RuntimeException('boom');
        try {
            $this->proxy->exchangeToken('c', 'r');
            $this->fail('expected exception');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }
        $this->assertSame(CircuitBreaker::CLOSED, CircuitBreaker::state('stub'), 'one failure below threshold');

        try {
            $this->proxy->exchangeToken('c', 'r');
            $this->fail('expected exception');
        } catch (\RuntimeException $e) {
        }
        $this->assertSame(CircuitBreaker::OPEN, CircuitBreaker::state('stub'));
    }

    public function testOpenCircuitFastFailsWithoutTouchingAdapter(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::failure('stub'); // -> OPEN
        $this->adapter->script['exchangeToken'] = fn () => ['access_token' => 'should-not-run'];

        try {
            $this->proxy->exchangeToken('c', 'r');
            $this->fail('expected CircuitBreakerOpenException');
        } catch (CircuitBreakerOpenException $e) {
            $this->assertStringContainsString('circuit open: stub', $e->getMessage());
        }
        $this->assertSame(CircuitBreaker::OPEN, CircuitBreaker::state('stub'), 'fast-fail is not a recorded failure');
    }

    public function testHalfOpenProbeSuccessRecovers(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::$cooldownSeconds = 0;
        CircuitBreaker::failure('stub'); // -> OPEN
        $this->assertTrue(CircuitBreaker::allow('stub')); // -> HALF_OPEN probe

        $this->adapter->script['exchangeToken'] = fn () => ['access_token' => 'tok'];
        $this->assertSame(['access_token' => 'tok'], $this->proxy->exchangeToken('c', 'r'));
        $this->assertSame(CircuitBreaker::CLOSED, CircuitBreaker::state('stub'));
    }

    public function testGeneratorSuccessRecordedAfterIteration(): void
    {
        $this->adapter->script['fetchCampaigns'] = function () {
            yield ['platform_campaign_id' => 'c1'];
            yield ['platform_campaign_id' => 'c2'];
        };
        $rows = [];
        foreach ($this->proxy->fetchCampaigns('t', 'a') as $row) {
            $rows[] = $row;
        }
        $this->assertCount(2, $rows);
        $this->assertSame(CircuitBreaker::CLOSED, CircuitBreaker::state('stub'));
    }

    public function testGeneratorFailureCountedAndPropagated(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        $this->adapter->script['fetchCampaigns'] = function () {
            yield ['platform_campaign_id' => 'c1'];
            throw new \RuntimeException('mid-iteration boom');
        };
        try {
            foreach ($this->proxy->fetchCampaigns('t', 'a') as $row) {
            }
            $this->fail('expected exception from generator');
        } catch (\RuntimeException $e) {
            $this->assertSame('mid-iteration boom', $e->getMessage());
        }
        $this->assertSame(CircuitBreaker::OPEN, CircuitBreaker::state('stub'), 'generator failure trips the breaker');
    }

    public function testOpenCircuitFastFailsOnGeneratorMethodsAtCallTime(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::failure('stub'); // -> OPEN
        $this->adapter->script['fetchCampaigns'] = function () {
            yield ['platform_campaign_id' => 'should-not-run'];
        };

        try {
            $gen = $this->proxy->fetchCampaigns('t', 'a');
            $this->fail('expected CircuitBreakerOpenException at call time, before any iteration');
        } catch (CircuitBreakerOpenException $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e, 'must be a RuntimeException so task catch(Throwable) paths degrade cleanly');
            $this->assertStringContainsString('circuit open', $e->getMessage());
        }
        $this->assertSame(CircuitBreaker::OPEN, CircuitBreaker::state('stub'));
    }

    public function testFastFailRepeatsWithoutTouchingAdapter(): void
    {
        CircuitBreaker::$failureThreshold = 1;
        CircuitBreaker::failure('stub'); // -> OPEN
        $touched = 0;
        $this->adapter->script['exchangeToken'] = function () use (&$touched) {
            $touched++;
            return ['access_token' => 'tok'];
        };
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->proxy->exchangeToken('c', 'r');
                $this->fail("iteration {$i} expected CircuitBreakerOpenException");
            } catch (CircuitBreakerOpenException $e) {
            }
        }
        $this->assertSame(0, $touched, 'adapter must never be reached while circuit is open');
    }

    public function testMetadataCallsDoNotResetFailureCount(): void
    {
        CircuitBreaker::$failureThreshold = 5;
        for ($i = 0; $i < 4; $i++) {
            CircuitBreaker::failure('stub');
        }
        $this->assertSame('stub', $this->proxy->code());
        $this->assertSame('Stub', $this->proxy->name());
        $this->assertSame(['report'], $this->proxy->capabilities());

        CircuitBreaker::failure('stub'); // 5th failure
        $this->assertSame(CircuitBreaker::OPEN, CircuitBreaker::state('stub'), 'metadata passthrough must not count as success');
    }

    public function testVoidReturningMethodsRecordSuccess(): void
    {
        CircuitBreaker::$failureThreshold = 3;
        CircuitBreaker::failure('stub');
        CircuitBreaker::failure('stub');
        $this->adapter->script['toggleCampaign'] = fn () => null;
        $this->proxy->toggleCampaign('t', 'a', 'pid', true);
        CircuitBreaker::failure('stub');
        CircuitBreaker::failure('stub');
        $this->assertSame(
            CircuitBreaker::CLOSED,
            CircuitBreaker::state('stub'),
            'void success must reset the failure streak (would be OPEN if recorded as failure)'
        );
    }
}
