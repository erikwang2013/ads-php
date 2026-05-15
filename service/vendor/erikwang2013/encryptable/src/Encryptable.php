<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Maize\Encryptable;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Encryptable implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): mixed
    {
        return Encryption::php()->decrypt($value);
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        return Encryption::php()->encrypt($value);
    }
}
