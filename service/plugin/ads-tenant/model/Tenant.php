<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_tenant\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;

class Tenant extends Model
{
    use SnowflakeTrait;

    protected $table = 'erik_tenants';
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
