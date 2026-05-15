<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Maize\Encryptable\Bridge\ThinkPHP;

use Maize\Encryptable\Contracts\EncryptableConfigContract;
use Maize\Encryptable\Support\PreviousKeysParser;
use think\facade\Config;

class ThinkEncryptableConfig implements EncryptableConfigContract
{
    public function getKey(): ?string
    {
        $key = Config::get('encryptable.key');

        if ($key === null || $key === '') {
            return null;
        }

        return (string) $key;
    }

    public function getCipher(): ?string
    {
        $cipher = Config::get('encryptable.cipher', 'aes-128-ecb');

        if ($cipher === null || $cipher === '') {
            return null;
        }

        return (string) $cipher;
    }

    public function getPreviousKeys(): array
    {
        return PreviousKeysParser::parse(Config::get('encryptable.previous_keys', []));
    }
}
