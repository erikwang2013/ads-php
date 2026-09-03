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
        $campaigns = DB::table('ads_campaigns')
            ->where('status', 'enabled')
            ->where('daily_budget', '>', 0)
            ->get();

        foreach ($campaigns as $campaign) {
            $spent = (int) DB::table('ads_report_metrics')
                ->where('campaign_id', $campaign->id)
                ->where('date', date('Y-m-d'))
                ->sum('cost');

            $budget = (int) $campaign->daily_budget;
            if ($budget <= 0) continue;

            // 消耗占比：bcmath 计算避免浮点误差；先四舍五入到 1 位小数再判级（与 float 版语义一致）
            $pct = (float) bc_round(bcdiv(bcmul((string) $spent, '100'), (string) $budget, 4), 1);

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
