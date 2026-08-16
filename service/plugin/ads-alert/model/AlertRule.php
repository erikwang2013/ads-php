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
    // 除 id 外全部字段可批量赋值（含 channels / webhook_url）
    protected $guarded = ['id'];
    protected $casts = [
        'channels'    => 'array',
        'enabled'     => 'boolean',
        'threshold'   => 'float',
        // Webhook 回调地址（webhook 渠道目标），新增列见 migration/create_alert_webhook_url.sql
        'webhook_url' => 'string',
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
