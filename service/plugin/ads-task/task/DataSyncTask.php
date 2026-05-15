<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_task\task;

use plugin\ads_account\model\PlatformAccount;
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\src\ReportRequest;
use erik\support\CacheService;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class DataSyncTask
{
    public function execute(): void
    {
        $accounts = PlatformAccount::query()
            ->where('status', 1)
            ->where('sync_enabled', 1)
            ->get();

        foreach ($accounts as $account) {
            echo "Syncing account {$account->id} ({$account->platform})...\n";

            try {
                $adapter = AdapterRegistry::get($account->platform);
                if (!$adapter) continue;

                // Sync campaigns
                foreach ($adapter->fetchCampaigns($account->access_token, $account->account_id_on_platform) as $row) {
                    DB::table('erik_campaigns')->updateOrInsert(
                        [
                            'platform_account_id'  => $account->id,
                            'platform_campaign_id' => $row['platform_campaign_id'],
                        ],
                        [
                            'tenant_id'   => $account->tenant_id,
                            'platform'    => $account->platform,
                            'name'        => $row['name'] ?? '',
                            'daily_budget'=> $row['daily_budget'] ?? 0,
                            'status'      => $row['status'] ?? null,
                            'extra'       => json_encode($row['extra'] ?? [], JSON_UNESCAPED_UNICODE),
                            'synced_at'   => now(),
                            'updated_at'  => now(),
                        ]
                    );
                }

                // Sync reports (last 2 days)
                $req = new ReportRequest(
                    dateStart: date('Y-m-d', strtotime('-2 days')),
                    dateEnd:   date('Y-m-d'),
                    granularity: 'daily',
                    metrics: ['cost', 'impressions', 'clicks', 'conversions', 'ctr', 'cvr', 'cpc', 'cpm', 'roi'],
                );

                foreach ($adapter->fetchReports($account->access_token, $account->account_id_on_platform, $req) as $row) {
                    $campaignId = null;
                    if (!empty($row['platform_campaign_id'])) {
                        $campaign = DB::table('erik_campaigns')
                            ->where('platform_campaign_id', $row['platform_campaign_id'])
                            ->where('platform_account_id', $account->id)
                            ->first();
                        $campaignId = $campaign->id ?? null;
                    }

                    DB::table('erik_report_metrics')->updateOrInsert(
                        [
                            'tenant_id'           => $account->tenant_id,
                            'platform'            => $account->platform,
                            'platform_account_id' => $account->id,
                            'campaign_id'         => $campaignId,
                            'date'                => $row['date'] ?? date('Y-m-d'),
                            'granularity'         => 'daily',
                        ],
                        [
                            'cost'         => $row['cost'] ?? 0,
                            'impressions'  => $row['impressions'] ?? 0,
                            'clicks'       => $row['clicks'] ?? 0,
                            'conversions'  => $row['conversions'] ?? 0,
                            'ctr'          => $row['ctr'] ?? 0,
                            'cpm'          => $row['cpm'] ?? 0,
                            'cpc'          => $row['cpc'] ?? 0,
                            'cvr'          => $row['cvr'] ?? 0,
                        ]
                    );
                }

                // Flush cached dashboard on fresh data
                CacheService::flush('cache:dashboard:');

                $account->update(['last_sync_at' => now()]);
                echo "  Done.\n";

            } catch (Throwable $e) {
                DB::table('erik_sync_errors')->insert([
                    'platform_account_id' => $account->id,
                    'platform'            => $account->platform,
                    'error_message'       => $e->getMessage(),
                    'next_retry_at'       => now()->addMinutes(5),
                    'created_at'          => now(),
                ]);
                echo "  Failed: {$e->getMessage()}\n";
            }
        }
    }

    public function executeSingleAccount(int $accountId): void
    {
        $account = PlatformAccount::query()->find($accountId);
        if (!$account) {
            throw new \RuntimeException("Account {$accountId} not found");
        }

        echo "Syncing account {$account->id} ({$account->platform})...\n";

        $adapter = AdapterRegistry::get($account->platform);
        if (!$adapter) {
            throw new \RuntimeException("No adapter for platform {$account->platform}");
        }

        // Sync campaigns
        foreach ($adapter->fetchCampaigns($account->access_token, $account->account_id_on_platform) as $row) {
            DB::table('erik_campaigns')->updateOrInsert(
                [
                    'platform_account_id'  => $account->id,
                    'platform_campaign_id' => $row['platform_campaign_id'],
                ],
                [
                    'tenant_id'   => $account->tenant_id,
                    'platform'    => $account->platform,
                    'name'        => $row['name'] ?? '',
                    'daily_budget'=> $row['daily_budget'] ?? 0,
                    'status'      => $row['status'] ?? null,
                    'extra'       => json_encode($row['extra'] ?? [], JSON_UNESCAPED_UNICODE),
                    'synced_at'   => now(),
                    'updated_at'  => now(),
                ]
            );
        }

        // Sync reports (last 2 days)
        $req = new ReportRequest(
            dateStart: date('Y-m-d', strtotime('-2 days')),
            dateEnd:   date('Y-m-d'),
            granularity: 'daily',
            metrics: ['cost', 'impressions', 'clicks', 'conversions', 'ctr', 'cvr', 'cpc', 'cpm', 'roi'],
        );

        foreach ($adapter->fetchReports($account->access_token, $account->account_id_on_platform, $req) as $row) {
            $campaignId = null;
            if (!empty($row['platform_campaign_id'])) {
                $campaign = DB::table('erik_campaigns')
                    ->where('platform_campaign_id', $row['platform_campaign_id'])
                    ->where('platform_account_id', $account->id)
                    ->first();
                $campaignId = $campaign->id ?? null;
            }

            DB::table('erik_report_metrics')->updateOrInsert(
                [
                    'tenant_id'           => $account->tenant_id,
                    'platform'            => $account->platform,
                    'platform_account_id' => $account->id,
                    'campaign_id'         => $campaignId,
                    'date'                => $row['date'] ?? date('Y-m-d'),
                    'granularity'         => 'daily',
                ],
                [
                    'cost'         => $row['cost'] ?? 0,
                    'impressions'  => $row['impressions'] ?? 0,
                    'clicks'       => $row['clicks'] ?? 0,
                    'conversions'  => $row['conversions'] ?? 0,
                    'ctr'          => $row['ctr'] ?? 0,
                    'cpm'          => $row['cpm'] ?? 0,
                    'cpc'          => $row['cpc'] ?? 0,
                    'cvr'          => $row['cvr'] ?? 0,
                ]
            );
        }

        // Flush cached dashboard on fresh data
        CacheService::flush('cache:dashboard:');

        $account->update(['last_sync_at' => now()]);
        echo "  Done.\n";
    }
}
