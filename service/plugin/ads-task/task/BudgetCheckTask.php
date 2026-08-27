<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_task\task;

use plugin\ads_alert\service\BudgetAlertService;
use Illuminate\Database\Capsule\Manager as DB;

class BudgetCheckTask
{
    public function execute(): void
    {
        $service = new BudgetAlertService();
        $alerts = $service->checkAll();
        $notified = 0;

        foreach ($alerts as $alert) {
            // Skip if already notified for this campaign today at same level
            $exists = DB::table('ads_notifications')
                ->where('tenant_id', $alert['tenant_id'])
                ->where('title', 'like', "%预算{$alert['level']}%")
                ->where('created_at', '>=', date('Y-m-d 00:00:00'))
                ->where('created_at', '<=', date('Y-m-d 23:59:59'))
                ->exists();

            if ($exists) continue;

            $labels = ['yellow' => '⚠️', 'orange' => '🔶', 'red' => '🔴'];
            $emoji = $labels[$alert['level']] ?? '⚠️';

            DB::table('ads_notifications')->insert([
                'id'         => snowflake_id(),
                'tenant_id'  => $alert['tenant_id'],
                'type'       => 'alert',
                'title'      => "{$emoji} 预算{$alert['level']}预警: {$alert['campaign_name']}",
                'content'    => "{$alert['platform']} | 已消耗 ¥" . number_format($alert['spent'] / 100, 2) . " / 日预算 ¥" . number_format($alert['budget'] / 100, 2) . " ({$alert['pct']}%)",
                'created_at' => now(),
            ]);

            $notified++;
        }

        echo "Checked " . count($alerts) . " budget alerts, {$notified} notified.\n";
    }
}
