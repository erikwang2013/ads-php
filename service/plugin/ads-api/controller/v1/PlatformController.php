<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_account\service\OAuthService;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use erik\support\CacheService;
use Erikwang2013\Season\SeasonService;
use Throwable;

class PlatformController
{
        /**
     * @Title("平台列表")
     * @Group("平台")
     * @Url("/api/v1/platforms")
     * @Method("GET")
     */
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

        /**
     * @Title("OAuth授权URL")
     * @Group("平台")
     * @Url("/api/v1/platforms/{code}/oauth-url")
     * @Method("GET")
     */
    public function oauthUrl(Request $request, string $code): \Webman\Http\Response
    {
        $redirectUri = $request->get('redirect_uri', '');
        if (!$redirectUri) {
            return ApiResponse::error('redirect_uri is required');
        }
        if (!$this->isAllowedRedirect($redirectUri)) {
            return ApiResponse::error('redirect_uri is not allowed');
        }

        try {
            $result = (new OAuthService())->getAuthUrl($code, $redirectUri);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage());
        } catch (Throwable $e) {
            // DB 等内部异常不回显 SQLSTATE/PDO 细节
            \support\Log::error('oauth url init failed', ['platform' => $code, 'exception' => $e]);
            return ApiResponse::error('授权初始化失败');
        }

        return ApiResponse::success($result);
    }

        /**
     * @Title("OAuth回调")
     * @Group("平台")
     * @Url("/api/v1/platforms/{code}/callback")
     * @Method("POST")
     */
    public function callback(Request $request, string $code): \Webman\Http\Response
    {
        $state = $request->post('state', '');
        $authCode = $request->post('code', '');

        try {
            $oauth = new OAuthService();
            $account = $oauth->handleCallback(
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
        if ($allowed === '') {
            // 未配置白名单时只允许与 APP_URL 同 host，避免开放重定向
            $appHost = parse_url((string) env('APP_URL', ''), PHP_URL_HOST);
            $uriHost = parse_url($uri, PHP_URL_HOST);
            return is_string($appHost) && $appHost !== ''
                && is_string($uriHost) && $uriHost !== ''
                && strcasecmp($appHost, $uriHost) === 0;
        }
        $patterns = array_map('trim', explode(',', $allowed));
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $uri)) return true;
        }
        return false;
    }
}
