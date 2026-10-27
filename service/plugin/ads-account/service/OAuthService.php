<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_account\service;

use plugin\ads_account\model\PlatformAccount;
use plugin\ads_account\model\AuthToken;
use plugin\ads_platform\src\AdapterRegistry;
use Throwable;
use RuntimeException;
use InvalidArgumentException;

class OAuthService
{
    public function getAuthUrl(int $tenantId, string $platform, string $redirectUri): array
    {
        $adapter = AdapterRegistry::get($platform);
        if (!$adapter) {
            throw new InvalidArgumentException("Unsupported platform: $platform");
        }

        $state = bin2hex(random_bytes(16));
        AuthToken::create([
            'tenant_id'   => $tenantId,
            'platform'    => $platform,
            'state'       => $state,
            'redirect_uri'=> $redirectUri,
            'expires_at'  => now()->addMinutes(10),
        ]);

        return [
            'auth_url' => $adapter->buildAuthUrl($redirectUri, $state),
            'state'    => $state,
        ];
    }

    public function handleCallback(int $tenantId, string $platform, string $state, string $code): PlatformAccount
    {
        $authToken = AuthToken::where('state', $state)
            ->where('tenant_id', $tenantId)
            ->where('platform', $platform)
            ->first();

        if (!$authToken || $authToken->isExpired()) {
            throw new RuntimeException('Invalid or expired state');
        }

        $adapter = AdapterRegistry::get($platform);
        if (!$adapter) {
            throw new InvalidArgumentException("Unsupported platform: $platform");
        }

        $tokenData = $adapter->exchangeToken($code, $authToken->redirect_uri);

        $account = PlatformAccount::create([
            'tenant_id'               => $tenantId,
            'platform'                => $platform,
            'account_id_on_platform'  => $tokenData['advertiser_ids'][0] ?? '0',
            'access_token'            => $tokenData['access_token'],
            'refresh_token'           => $tokenData['refresh_token'] ?? '',
            'token_expires_at'        => now()->addSeconds($tokenData['expires_in'] ?? 86400),
            'status'                  => 1,
        ]);

        // fetch account name for all advertiser_ids
        if (!empty($tokenData['advertiser_ids'])) {
            try {
                $infos = $adapter->fetchAccountInfo($tokenData['access_token']);
                foreach ($infos as $info) {
                    PlatformAccount::updateOrCreate(
                        [
                            'tenant_id'              => $tenantId,
                            'platform'               => $platform,
                            'account_id_on_platform' => $info['account_id_on_platform'],
                        ],
                        [
                            'account_name'     => $info['account_name'],
                            'access_token'     => $tokenData['access_token'],
                            'refresh_token'    => $tokenData['refresh_token'] ?? '',
                            'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 86400),
                            'status'           => 1,
                        ]
                    );
                }
            } catch (Throwable $e) {
                // account name fetch failure doesn't block binding
            }
        }

        $authToken->delete();

        return $account;
    }

    public function refreshAccessToken(PlatformAccount $account): void
    {
        $adapter = AdapterRegistry::get($account->platform);
        if (!$adapter || empty($account->refresh_token)) {
            return;
        }

        $tokenData = $adapter->refreshToken($account->refresh_token);

        $account->update([
            'access_token'     => $tokenData['access_token'],
            'refresh_token'    => $tokenData['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 86400),
        ]);
    }
}
