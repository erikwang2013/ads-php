<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace erik\support;

use Erikwang2013\Snowflake\Snowflake;

trait SnowflakeTrait
{
    protected static ?Snowflake $snowflakeGenerator = null;

    public static function bootSnowflakeTrait(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = static::snowflakeId();
            }
        });
    }

    /**
     * Generate a distributed Snowflake ID (BIGINT).
     *
     * 修复：原实现 use 了不存在的 Erikwang2013\SnowflakePhp\Snowflake 并静态调用
     * 不存在的 generate()，任何使用本 trait 的模型创建都会触发 fatal。vendor 中
     * 真实类为 Erikwang2013\Snowflake\Snowflake，提供实例方法 id()。
     */
    public static function snowflakeId(): int
    {
        if (static::$snowflakeGenerator === null) {
            static::$snowflakeGenerator = new Snowflake();
        }
        return static::$snowflakeGenerator->id();
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
