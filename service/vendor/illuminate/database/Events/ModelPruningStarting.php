<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */


namespace Illuminate\Database\Events;

class ModelPruningStarting
{
    /**
     * The class names of the models that will be pruned.
     *
     * @var array<class-string>
     */
    public $models;

    /**
     * Create a new event instance.
     *
     * @param  array<class-string>  $models
     * @return void
     */
    public function __construct($models)
    {
        $this->models = $models;
    }
}
