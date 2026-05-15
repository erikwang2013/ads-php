<?php
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
];
