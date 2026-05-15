<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Maize\Encryptable\Bridge\Hyperf;

use Maize\Encryptable\Contracts\DbDriverDetector;
use Maize\Encryptable\Contracts\EncryptableConfigContract;
use Maize\Encryptable\DBEncrypter;
use Maize\Encryptable\PHPEncrypter;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                EncryptableConfigContract::class => HyperfEncryptableConfig::class,
                DbDriverDetector::class => HyperfDbDriverDetector::class,
                PHPEncrypter::class => PHPEncrypter::class,
                DBEncrypter::class => DBEncrypter::class,
            ],
        ];
    }
}
