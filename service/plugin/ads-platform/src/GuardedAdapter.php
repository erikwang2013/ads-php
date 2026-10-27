<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_platform\src;

/**
 * Transparent proxy around a PlatformAdapter.
 *
 * Every call is gated by CircuitBreaker: when the circuit is OPEN a
 * CircuitBreakerOpenException is thrown before the real adapter is touched
 * (fast-fail). Results are recorded as success/failure; Throwables from the
 * real adapter are re-thrown after recording a failure.
 *
 * Generator-returning methods (fetch*) run their body during iteration, so
 * the verdict for those is recorded when the generator completes or throws.
 *
 * Wired into AdapterRegistry::get() — zero changes at call sites.
 */
class GuardedAdapter
{
    private PlatformAdapter $adapter;

    public function __construct(PlatformAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function __call(string $method, array $args)
    {
        // Local metadata — no remote call, never guarded.
        if (in_array($method, ['code', 'name', 'capabilities'], true)) {
            return $this->adapter->$method(...$args);
        }

        $code = $this->adapter->code();
        if (!CircuitBreaker::allow($code)) {
            throw new CircuitBreakerOpenException("circuit open: {$code}");
        }

        try {
            $result = $this->adapter->$method(...$args);
        } catch (\Throwable $e) {
            CircuitBreaker::failure($code);
            throw $e;
        }

        if ($result instanceof \Generator) {
            return $this->guardGenerator($result, $code);
        }
        CircuitBreaker::success($code);
        return $result;
    }

    private function guardGenerator(\Generator $gen, string $code): \Generator
    {
        try {
            foreach ($gen as $key => $value) {
                yield $key => $value;
            }
            CircuitBreaker::success($code);
        } catch (\Throwable $e) {
            CircuitBreaker::failure($code);
            throw $e;
        }
    }
}
