<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

/**
 * Generate a snowflake-style BIGINT ID.
 * Uses timestamp (milliseconds) in high bits plus a random suffix.
 */
function snowflake_id(): int
{
    $ms = (int) (microtime(true) * 1000);
    $random = random_int(0, 4194303); // 22 bits of randomness
    return ($ms << 22) | $random;
}
