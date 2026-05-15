<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_alert\model;

use Illuminate\Database\Eloquent\Model;

class AlertLog extends Model
{
    protected $table = 'alert_logs';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = [
        'extra'         => 'array',
        'current_value' => 'float',
        'threshold'     => 'float',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    public function rule()
    {
        return $this->belongsTo(AlertRule::class, 'rule_id');
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeTriggered($query)
    {
        return $query->where('status', 'triggered');
    }

    public function markAcknowledged(): void
    {
        $this->status = 'acknowledged';
        $this->save();
    }

    public function markResolved(): void
    {
        $this->status = 'resolved';
        $this->save();
    }
}
