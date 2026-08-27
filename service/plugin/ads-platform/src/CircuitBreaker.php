<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_platform\src;

/**
 * Per-platform circuit breaker.
 *
 * CLOSED -> OPEN (failureThreshold consecutive failures)
 * OPEN   -> HALF_OPEN (after cooldownSeconds, next call is a probe)
 * HALF_OPEN -> CLOSED (probe succeeds) | OPEN (probe fails)
 *
 * ponytail: static in-memory state — fine for the current single-node
 * deployment; move state to Redis when going multi-node.
 */
class CircuitBreaker
{
    public const CLOSED    = 'closed';
    public const OPEN      = 'open';
    public const HALF_OPEN = 'half_open';

    /** Consecutive failures before the breaker opens. */
    public static int $failureThreshold = 5;
    /** Seconds before an OPEN breaker lets a probe request through. */
    public static int $cooldownSeconds = 30;

    /** @var array<string, array{state:string, failures:int, opened_at:int}> */
    private static array $state = [];

    public static function allow(string $code): bool
    {
        $s = self::stateOf($code);
        if ($s['state'] !== self::OPEN) {
            return true; // CLOSED or HALF_OPEN (probe)
        }
        if (time() - $s['opened_at'] >= self::$cooldownSeconds) {
            self::$state[$code]['state'] = self::HALF_OPEN;
            return true; // probe request
        }
        return false;
    }

    public static function success(string $code): void
    {
        self::$state[$code] = ['state' => self::CLOSED, 'failures' => 0, 'opened_at' => 0];
    }

    public static function failure(string $code): void
    {
        $s = self::stateOf($code);
        if ($s['state'] === self::OPEN) {
            return;
        }
        if ($s['state'] === self::HALF_OPEN) {
            // Probe failed -> straight back to OPEN
            self::$state[$code] = ['state' => self::OPEN, 'failures' => $s['failures'] + 1, 'opened_at' => time()];
            return;
        }
        $failures = $s['failures'] + 1;
        if ($failures >= self::$failureThreshold) {
            self::$state[$code] = ['state' => self::OPEN, 'failures' => $failures, 'opened_at' => time()];
        } else {
            self::$state[$code] = ['state' => self::CLOSED, 'failures' => $failures, 'opened_at' => 0];
        }
    }

    public static function state(string $code): string
    {
        return self::stateOf($code)['state'];
    }

    public static function reset(string $code): void
    {
        unset(self::$state[$code]);
    }

    public static function isOpen(string $code): bool
    {
        return self::state($code) === self::OPEN;
    }

    /** @return array{state:string, failures:int, opened_at:int} */
    private static function stateOf(string $code): array
    {
        return self::$state[$code] ?? ['state' => self::CLOSED, 'failures' => 0, 'opened_at' => 0];
    }
}
