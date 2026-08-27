<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_platform\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;

class TargetingTemplate extends Model
{
    use SnowflakeTrait;

    protected $table = 'ads_targeting_templates';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected $casts = [
        'targeting' => 'array',
        'is_shared' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) { $model->created_at = now(); $model->updated_at = now(); });
        static::updating(function ($model) { $model->updated_at = now(); });
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
