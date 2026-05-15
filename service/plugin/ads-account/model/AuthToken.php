<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_account\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;
use Erikwang2013\Encryptable\Encryptable;

class AuthToken extends Model
{
    use SnowflakeTrait;
    use Encryptable;

    protected array $encryptable = ['redirect_uri'];

    protected $table = 'erik_auth_tokens';
    protected $guarded = ['id'];
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
