<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Maize\Encryptable\Bridge\Laravel;

use Maize\Encryptable\Contracts\EncryptableConfigContract;
use Maize\Encryptable\Support\PreviousKeysParser;

class IlluminateEncryptableConfig implements EncryptableConfigContract
{
    public function getKey(): ?string
    {
        $key = config('encryptable.key');

        if ($key === null || $key === '') {
            return null;
        }

        return (string) $key;
    }

    public function getCipher(): ?string
    {
        $cipher = config('encryptable.cipher', 'aes-128-ecb');

        if ($cipher === null || $cipher === '') {
            return null;
        }

        return (string) $cipher;
    }

    public function getPreviousKeys(): array
    {
        return PreviousKeysParser::parse(config('encryptable.previous_keys', []));
    }
}
