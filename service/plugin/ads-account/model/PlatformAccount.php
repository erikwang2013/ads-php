<?php
namespace plugin\ads_account\model;

use Illuminate\Database\Eloquent\Model;

class PlatformAccount extends Model
{
    protected $table = 'platform_accounts';
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
