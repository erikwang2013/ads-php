<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * JWT service wrapper — static facade around erikwang2013/jwt-webman.
 *
 * The underlying JWT class uses instance methods but the existing call sites
 * expect static calls (Jwt::encode / Jwt::verify). This wrapper bridges the gap.
 * Blacklist storage follows JWT_STORAGE (file single-instance, redis multi-instance).
 */

namespace erik\support;

use Erikwang2013\Jwt\JWT;
use Erikwang2013\Jwt\JWTFactory;
use Erikwang2013\Jwt\JWTException;

class JwtService
{
    private static ?JWT $instance = null;

    private static function getInstance(): JWT
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $secret = env('JWT_SECRET', '');
        if (strlen($secret) < 32) {
            throw new \RuntimeException('JWT_SECRET must be at least 32 characters');
        }

        $config = [
            'secret_key'     => $secret,
            'algorithm'      => 'HS256',
            'issuer'         => 'ads-api',
            'audience'       => 'ads-users',
            'leeway'         => 60,
            'default_expire' => (int) env('JWT_TTL', 86400),
            'refresh_expire' => 1209600,
            'storage'        => ['type' => 'file'],
            'advanced'       => [
                'retry_attempts'   => 1,
                'auto_cleanup'     => false,
            ],
        ];
        $connections = [];

        // 多实例部署时注销黑名单必须共享，否则 logout 只对签发它的容器生效
        if (env('JWT_STORAGE', 'file') === 'redis') {
            $config['storage'] = ['type' => 'redis', 'prefix' => 'jwt_blacklist:'];
            $connections['redis'] = fn() => redis();
        }

        self::$instance = JWTFactory::createFromConfig($config, null, $connections);

        return self::$instance;
    }

    public static function encode(array $payload, int $expire = 0): string
    {
        $req = request();
        if ($req) {
            $payload['_ip'] = $req->getRealIp();
            $payload['_ua'] = md5($req->header('User-Agent', ''));
        }
        return self::getInstance()->encode($payload, $expire);
    }

    public static function verify(string $token): array
    {
        return self::getInstance()->decode($token);
    }

    public static function refresh(string $token, int $expire = 0): string
    {
        return self::getInstance()->refresh($token, $expire ?: (int) env('JWT_TTL', 86400));
    }

    public static function blacklist(string $token): bool
    {
        return self::getInstance()->blacklist($token);
    }

    public static function isBlacklisted(string $token): bool
    {
        return self::getInstance()->isBlacklisted($token);
    }
}
