<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */


declare(strict_types=1);

namespace Dotenv\Parser;

interface ParserInterface
{
    /**
     * Parse content into an entry array.
     *
     * @param string $content
     *
     * @throws Dotenv\Exception\InvalidFileException
     *
     * @return Dotenv\Parser\Entry[]
     */
    public function parse(string $content);
}
