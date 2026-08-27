<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_platform\src;

/**
 * Thrown by GuardedAdapter when the circuit for a platform is OPEN.
 * Task layers already catch Throwable, so this doubles as the fast-fail
 * degradation path: the platform is skipped until the breaker recovers.
 */
class CircuitBreakerOpenException extends \RuntimeException
{
}
