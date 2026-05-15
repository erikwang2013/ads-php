<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Maize\Encryptable\Contracts;

interface DbDriverDetector
{
    public function isPostgres(): bool;
}
