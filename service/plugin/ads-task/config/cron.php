<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

return [
    [
        'name'    => 'TokenRefresh',
        'handler' => [plugin\ads_task\task\TokenRefreshTask::class, 'execute'],
        'rule'    => '55 */1 * * *',
    ],
    [
        'name'    => 'DataSync',
        'handler' => [plugin\ads_task\task\DataSyncTask::class, 'execute'],
        'rule'    => '*/10 * * * *',
    ],
    [
        'name'    => 'AlertCheck',
        'handler' => [plugin\ads_task\task\AlertCheckTask::class, 'execute'],
        'rule'    => '*/5 * * * *',
    ],
];
