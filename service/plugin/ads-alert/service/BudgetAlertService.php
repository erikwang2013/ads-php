<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * BudgetAlertService — monitors campaign daily budget consumption.
 * 3 alert levels: yellow (≥50%), orange (≥80%), red (≥100%).
 */

namespace plugin\ads_alert\service;

use Illuminate\Database\Capsule\Manager as DB;

class BudgetAlertService
{
    public function checkAll(): array
    {
        $alerts = [];
        $campaigns = DB::table('erik_campaigns')
            ->where('status', 'enabled')
            ->where('daily_budget', '>', 0)
            ->get();

        foreach ($campaigns as $campaign) {
            $spent = (int) DB::table('erik_report_metrics')
                ->where('campaign_id', $campaign->id)
                ->where('date', date('Y-m-d'))
                ->sum('cost');

            $budget = (int) $campaign->daily_budget;
            if ($budget <= 0) continue;

            $pct = round($spent / $budget * 100, 1);

            $level = null;
            if ($pct >= 100) $level = 'red';
            elseif ($pct >= 80) $level = 'orange';
            elseif ($pct >= 50) $level = 'yellow';

            if ($level) {
                $alerts[] = [
                    'campaign_id'   => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'platform'      => $campaign->platform,
                    'spent'         => $spent,
                    'budget'        => $budget,
                    'pct'           => $pct,
                    'level'         => $level,
                    'tenant_id'     => $campaign->tenant_id,
                ];
            }
        }

        return $alerts;
    }
}
