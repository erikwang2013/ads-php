<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * JWT service wrapper — static facade around erikwang2013/jwt-webman.
 *
 * The underlying JWT class uses instance methods but the existing call sites
 * expect static calls (Jwt::encode / Jwt::verify). This wrapper bridges the gap.
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
        if (strlen($secret) < 16) {
            throw new \RuntimeException('JWT_SECRET must be at least 16 characters');
        }

        self::$instance = JWTFactory::createFromConfig([
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
        ]);

        return self::$instance;
    }

    public static function encode(array $payload, int $expire = 0): string
    {
        return self::getInstance()->encode($payload, $expire);
    }

    public static function verify(string $token): array
    {
        return self::getInstance()->decode($token);
    }

    public static function refresh(string $token): string
    {
        return self::getInstance()->refresh($token);
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
