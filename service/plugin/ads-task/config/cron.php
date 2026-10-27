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
        'name'    => 'RetrySync',
        'handler' => [plugin\ads_task\task\RetrySyncTask::class, 'execute'],
        'rule'    => '*/3 * * * *',
    ],
];
