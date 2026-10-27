<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_platform\src;

class CampaignData
{
    public function __construct(
        public string $name,
        public int    $dailyBudget,    // 单位：分
        public ?int   $totalBudget = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $status = null,
        public array  $extra = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:        $data['name'] ?? '',
            dailyBudget: (int) ($data['daily_budget'] ?? 0),
            totalBudget: isset($data['total_budget']) ? (int) $data['total_budget'] : null,
            startDate:   $data['start_date'] ?? null,
            endDate:     $data['end_date'] ?? null,
            status:      $data['status'] ?? null,
            extra:       $data['extra'] ?? [],
        );
    }
}
