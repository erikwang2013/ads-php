<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_alert\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;

class AlertRule extends Model
{
    use SnowflakeTrait;

    protected $table = 'erik_alert_rules';
    protected $guarded = ['id'];
    protected $casts = [
        'channels' => 'array',
        'enabled'  => 'boolean',
        'threshold' => 'float',
    ];

    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', 1);
    }
}
