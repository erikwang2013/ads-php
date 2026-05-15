<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Maize\Encryptable\Bridge\ThinkPHP;

use Maize\Encryptable\Contracts\DbDriverDetector;
use think\facade\Config;

class ThinkDbDriverDetector implements DbDriverDetector
{
    public function __construct(
        protected ?string $connection = null
    ) {
    }

    public function isPostgres(): bool
    {
        $name = $this->connection ?? (string) Config::get('database.default');
        $type = Config::get("database.connections.{$name}.type", '');

        return in_array(strtolower((string) $type), ['pgsql', 'postgres'], true);
    }
}
