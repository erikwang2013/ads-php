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

    // ads_auth_tokens 无 updated_at 列（同库其他模型均为 $timestamps=false + creating 钩子），
    // 保持默认 true 会导致 AuthToken::create() 插入 updated_at 报 1054 列不存在，
    // OAuth 授权 URL 接口（POST /api/platforms/{code}/oauth-url）必然失败。
    public $timestamps = false;

    protected array $encryptable = ['redirect_uri'];

    protected $table = 'ads_auth_tokens';
    protected $guarded = ['id'];
    protected $casts = [
        'expires_at' => 'datetime',
        'redirect_uri' => Encryptable::class,
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
