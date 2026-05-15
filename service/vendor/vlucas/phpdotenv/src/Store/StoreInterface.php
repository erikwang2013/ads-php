<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */


declare(strict_types=1);

namespace Dotenv\Store;

interface StoreInterface
{
    /**
     * Read the content of the environment file(s).
     *
     * @throws Dotenv\Exception\InvalidEncodingException|Dotenv\Exception\InvalidPathException
     *
     * @return string
     */
    public function read();
}
