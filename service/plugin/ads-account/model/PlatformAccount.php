<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_account\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;
use Erikwang2013\Encryptable\Encryptable;

class PlatformAccount extends Model
{
    use SnowflakeTrait;
    use Encryptable;

    protected array $encryptable = ['access_token', 'refresh_token'];

    protected $table = 'erik_platform_accounts';
    protected $guarded = ['id'];
    protected $casts = [
        'sync_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
        'token_expires_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(\plugin\ads_tenant\model\Tenant::class, 'tenant_id');
    }

    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) return false;
        return $this->token_expires_at->subMinutes(5)->isPast();
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
