<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace erik\support;

use Hashids\Hashids;

class HashidsService
{
    protected Hashids $hashids;

    public function __construct()
    {
        $this->hashids = new Hashids(
            env('HASHIDS_SALT', 'ads-platform-salt'),
            (int) env('HASHIDS_MIN_LENGTH', 8)
        );
    }

    public function encode(int|string $id): string
    {
        return $this->hashids->encode((int) $id);
    }

    public function decode(string $hash): int
    {
        $decoded = $this->hashids->decode($hash);
        return $decoded[0] ?? 0;
    }
}
