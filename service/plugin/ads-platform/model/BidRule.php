<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_platform\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;

class BidRule extends Model
{
    use SnowflakeTrait;

    protected $table = 'erik_bid_rules';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected $casts = [
        'enabled'          => 'boolean',
        'threshold'        => 'float',
        'cooldown_minutes' => 'integer',
        'adjust_step'      => 'integer',
        'budget_min'       => 'integer',
        'budget_max'       => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) { $model->created_at = now(); $model->updated_at = now(); });
        static::updating(function ($model) { $model->updated_at = now(); });
    }
}
