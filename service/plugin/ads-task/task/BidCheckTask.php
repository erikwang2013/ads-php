<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_task\task;

use plugin\ads_platform\model\BidRule;
use plugin\ads_platform\service\BidEngine;

class BidCheckTask
{
    public function execute(): void
    {
        $rules = BidRule::where('enabled', 1)->get();
        $engine = new BidEngine();
        $actions = 0;

        foreach ($rules as $rule) {
            $log = $engine->evaluate($rule);
            if ($log) $actions++;
        }

        echo "Checked {$rules->count()} bid rules, {$actions} actions taken.\n";
    }
}
