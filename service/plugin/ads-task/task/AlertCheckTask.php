<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_task\task;

use plugin\ads_alert\model\AlertRule;
use plugin\ads_alert\service\AlertEngine;
use plugin\ads_alert\service\NotificationService;

class AlertCheckTask
{
    public function execute(): void
    {
        $rules = AlertRule::where('enabled', 1)->get();
        $engine = new AlertEngine();
        $notify = new NotificationService();
        $triggered = 0;

        foreach ($rules as $rule) {
            $log = $engine->evaluate($rule);
            if ($log) {
                $notify->send($log, $rule);
                $triggered++;
            }
        }

        echo "Checked {$rules->count()} rules, {$triggered} triggered.\n";
    }
}
