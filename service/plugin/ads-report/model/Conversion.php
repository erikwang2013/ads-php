<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_report\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;

class Conversion extends Model
{
    use SnowflakeTrait;

    protected $table = 'erik_conversions';
    protected $guarded = ['id'];

    /**
     * erik_conversions 的时间戳由业务侧显式写入（created_at/updated_at），
     * 关闭 Eloquent 自动维护，避免与任务约定的写入方式冲突。
     */
    public $timestamps = false;

    protected $casts = [
        'value' => 'float',
    ];
}
