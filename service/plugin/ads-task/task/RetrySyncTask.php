<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_task\task;

use Illuminate\Database\Capsule\Manager as DB;

class RetrySyncTask
{
    public function execute(): void
    {
        $failures = DB::table('erik_sync_errors')
            ->where('retry_count', '<', 3)
            ->where('next_retry_at', '<=', now())
            ->orderBy('next_retry_at')
            ->limit(20)
            ->get();

        foreach ($failures as $failure) {
            echo "Retrying sync for account {$failure->platform_account_id} (attempt {$failure->retry_count})...\n";
            try {
                // Re-run the sync via DataSyncTask logic for this single account
                $task = new DataSyncTask();
                $task->executeSingleAccount($failure->platform_account_id);

                DB::table('erik_sync_errors')->where('id', $failure->id)->delete();
                echo "  Success.\n";
            } catch (\Throwable $e) {
                DB::table('erik_sync_errors')->where('id', $failure->id)->update([
                    'retry_count'  => $failure->retry_count + 1,
                    'last_error'   => $e->getMessage(),
                    'next_retry_at'=> now()->addMinutes(pow(5, $failure->retry_count + 1)),
                ]);
                echo "  Still failing: {$e->getMessage()}\n";
            }
        }
    }
}
