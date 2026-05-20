<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_platform\model;

use Illuminate\Database\Eloquent\Model;
use erik\support\SnowflakeTrait;
use Erikwang2013\WebmanScout\Searchable;

class Campaign extends Model
{
    use SnowflakeTrait;
    use Searchable;

    protected $table = 'erik_campaigns';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected $casts = [
        'daily_budget' => 'integer',
        'total_budget' => 'integer',
        'extra'        => 'array',
    ];

    public function searchableAs(): string
    {
        return config('scout.elasticsearch.index', 'ads_platform') . '_campaigns';
    }

    public function toSearchableArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'platform'   => $this->platform,
            'status'     => $this->status,
            'tenant_id'  => $this->tenant_id,
            'created_at' => $this->created_at,
        ];
    }
}
