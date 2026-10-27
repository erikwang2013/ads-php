<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_platform\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;

class BidLog extends Model
{
    use SnowflakeTrait;

    protected $table = 'ads_bid_logs';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected $casts = [
        'metric_value' => 'float',
        'old_budget'   => 'integer',
        'new_budget'   => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) { $model->created_at = now(); });
    }
}
