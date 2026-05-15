<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Maize\Encryptable\Exceptions;

use RuntimeException;

class SerializationException extends RuntimeException
{
    public function __construct(string $message = 'The given value cannot be serialized.')
    {
        parent::__construct($message);
    }
}
