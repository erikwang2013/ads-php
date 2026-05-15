<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_task\task;

use plugin\ads_account\model\PlatformAccount;
use plugin\ads_platform\src\AdapterRegistry;
use Throwable;

class TokenRefreshTask
{
    public function execute(): void
    {
        $accounts = PlatformAccount::query()
            ->where('status', 1)
            ->whereNotNull('refresh_token')
            ->where('refresh_token', '!=', '')
            ->get();

        $refreshed = 0;
        foreach ($accounts as $account) {
            if (!$account->isTokenExpired()) continue;
            try {
                $adapter = AdapterRegistry::get($account->platform);
                if (!$adapter) continue;
                $tokenData = $adapter->refreshToken($account->refresh_token);
                $account->update([
                    'access_token'     => $tokenData['access_token'],
                    'refresh_token'    => $tokenData['refresh_token'] ?? $account->refresh_token,
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 86400),
                ]);
                $refreshed++;
            } catch (Throwable $e) {
                echo "Token refresh failed for account {$account->id}: {$e->getMessage()}\n";
            }
        }
        echo "Refreshed {$refreshed} tokens.\n";
    }
}
