<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */


declare(strict_types=1);

namespace Doctrine\Inflector;

interface WordInflector
{
    public function inflect(string $word): string;
}
