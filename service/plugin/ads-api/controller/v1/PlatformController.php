<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_account\model\AuthToken;
use plugin\ads_account\service\OAuthService;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use erik\support\CacheService;
use Erikwang2013\Season\SeasonService;

class PlatformController
{
    public function index(): Response
    {
        $platforms = CacheService::remember('cache:platforms', 3600, function () {
            $season = new SeasonService();
            return array_map(function ($p) use ($season) {
                $p['flag'] = $season->getCountryFlagEmoji($p['country'] ?? 'CN');
                return $p;
            }, AdapterRegistry::all());
        });
        return ApiResponse::success($platforms);
    }

    public function oauthUrl(Request $request, string $code): \Webman\Http\Response
    {
        $redirectUri = $request->get('redirect_uri', '');
        if (!$redirectUri) {
            return ApiResponse::error('redirect_uri is required');
        }
        if (!$this->isAllowedRedirect($redirectUri)) {
            return ApiResponse::error('redirect_uri is not allowed');
        }

        $adapter = AdapterRegistry::get($code);
        if (!$adapter) {
            return ApiResponse::error("Unsupported platform: $code");
        }

        $state = bin2hex(random_bytes(16));
        $url = $adapter->buildAuthUrl($redirectUri, $state);

        AuthToken::create([
            'tenant_id'   => $request->tenantId ?? 1,
            'platform'    => $code,
            'state'       => $state,
            'redirect_uri'=> $redirectUri,
            'expires_at'  => now()->addMinutes(10),
        ]);

        return ApiResponse::success(['auth_url' => $url, 'state' => $state]);
    }

    public function callback(Request $request, string $code): \Webman\Http\Response
    {
        $state = $request->post('state', '');
        $authCode = $request->post('code', '');

        try {
            $oauth = new OAuthService();
            $account = $oauth->handleCallback(
                $request->tenantId ?? 1,
                $code,
                $state,
                $authCode
            );
            return ApiResponse::success(['account_id' => $account->id]);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    protected function isAllowedRedirect(string $uri): bool
    {
        $allowed = env('OAUTH_ALLOWED_REDIRECTS', '');
        if ($allowed === '') return true; // not configured, allow all
        $patterns = array_map('trim', explode(',', $allowed));
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $uri)) return true;
        }
        return false;
    }
}
