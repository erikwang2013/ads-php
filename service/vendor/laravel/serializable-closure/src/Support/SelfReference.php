<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */


namespace Laravel\SerializableClosure\Support;

class SelfReference
{
    /**
     * The unique hash representing the object.
     *
     * @var string
     */
    public $hash;

    /**
     * Creates a new self reference instance.
     *
     * @param  string  $hash
     * @return void
     */
    public function __construct($hash)
    {
        $this->hash = $hash;
    }
}
