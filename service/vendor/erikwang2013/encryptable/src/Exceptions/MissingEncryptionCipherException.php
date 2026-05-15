<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Maize\Encryptable\Exceptions;

use RuntimeException;

class MissingEncryptionCipherException extends RuntimeException
{
    public function __construct(string $message = 'No encryption cipher has been specified.')
    {
        parent::__construct($message);
    }
}
