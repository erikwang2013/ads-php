<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Maize\Encryptable\Bridge\Laravel;

use Illuminate\Support\Facades\DB;
use Maize\Encryptable\Contracts\DbDriverDetector;

class IlluminateDbDriverDetector implements DbDriverDetector
{
    public function __construct(
        protected ?string $connection = null
    ) {
    }

    public function isPostgres(): bool
    {
        $connection = $this->connection !== null
            ? DB::connection($this->connection)
            : DB::connection();

        return $connection->getDriverName() === 'pgsql';
    }
}
