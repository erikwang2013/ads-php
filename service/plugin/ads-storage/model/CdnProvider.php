<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_storage\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;
use Erikwang2013\Encryptable\Encryptable;

class CdnProvider extends Model
{
    use SnowflakeTrait;

    protected array $encryptable = ['access_key', 'secret_key', 'cdn_token'];

    protected $table = 'ads_cdn_providers';
    protected $guarded = ['id'];
    protected $casts = [
        'enabled'    => 'boolean',
        'is_default' => 'boolean',
        'access_key' => Encryptable::class,
        'secret_key' => Encryptable::class,
        'cdn_token'  => Encryptable::class,
    ];

    public static function defaultProvider(): ?self
    {
        return static::query()->where('enabled', 1)->where('is_default', 1)->first();
    }
}
