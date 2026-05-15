<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Maize\Encryptable\Bridge\Hyperf;

use Hyperf\Contract\ConfigInterface;
use Maize\Encryptable\Contracts\EncryptableConfigContract;
use Maize\Encryptable\Support\PackagePluginPaths;
use Maize\Encryptable\Support\PreviousKeysParser;

class HyperfEncryptableConfig implements EncryptableConfigContract
{
    public function __construct(
        protected ConfigInterface $config
    ) {
    }

    public function getKey(): ?string
    {
        $prefix = PackagePluginPaths::hyperfPluginConfigDotPrefix();
        $key = $this->config->get($prefix.'.key')
            ?? $this->config->get('encryptable.key');

        if ($key === null || $key === '') {
            return null;
        }

        return (string) $key;
    }

    public function getCipher(): ?string
    {
        $prefix = PackagePluginPaths::hyperfPluginConfigDotPrefix();
        $cipher = $this->config->get($prefix.'.cipher')
            ?? $this->config->get('encryptable.cipher', 'aes-128-ecb');

        if ($cipher === null || $cipher === '') {
            return null;
        }

        return (string) $cipher;
    }

    public function getPreviousKeys(): array
    {
        $prefix = PackagePluginPaths::hyperfPluginConfigDotPrefix();
        $raw = $this->config->get($prefix.'.previous_keys')
            ?? $this->config->get('encryptable.previous_keys', []);

        return PreviousKeysParser::parse($raw);
    }
}
