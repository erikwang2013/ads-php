<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_tenant\model;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $table = 'tenants';
    protected $guarded = ['id'];
    protected $casts = [
        'db_config' => 'array',
    ];

    public function isActive(): bool
    {
        return (int) $this->status === 1;
    }

    public static function findByDomain(string $domain): ?self
    {
        return static::where('domain', $domain)->where('status', 1)->first();
    }
}
