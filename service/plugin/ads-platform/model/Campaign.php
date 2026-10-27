<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_platform\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;

class Campaign extends Model
{
    use SnowflakeTrait;

    protected $table = 'ads_campaigns';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected $casts = [
        'daily_budget' => 'integer',
        'total_budget' => 'integer',
        'extra'        => 'array',
    ];
}
