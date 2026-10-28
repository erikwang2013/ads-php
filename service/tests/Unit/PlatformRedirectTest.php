<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * OAuth redirect 白名单：未配置 OAUTH_ALLOWED_REDIRECTS 时默认仅允许
 * 与 APP_URL 同 host（防开放重定向）；显式配置仍按 fnmatch 匹配。
 */

namespace Tests\Unit;

use plugin\ads_api\controller\v1\PlatformController;
use Tests\TestCase;

class PlatformRedirectTest extends TestCase
{
    private function allowed(string $uri): bool
    {
        $method = new \ReflectionMethod(PlatformController::class, 'isAllowedRedirect');
        return $method->invoke(new PlatformController(), $uri);
    }

    private function withEnv(array $values, callable $fn): void
    {
        $previous = [];
        foreach (array_keys($values) as $key) {
            $previous[$key] = getenv($key);
        }
        foreach ($values as $key => $value) {
            putenv("$key=$value");
        }
        try {
            $fn();
        } finally {
            foreach ($previous as $key => $value) {
                putenv($value === false ? $key : "$key=$value");
            }
        }
    }

    public function testEmptyAllowListFallsBackToAppUrlHost(): void
    {
        $this->withEnv(['OAUTH_ALLOWED_REDIRECTS' => '', 'APP_URL' => 'https://app.example'], function () {
            $this->assertTrue($this->allowed('https://app.example/oauth/callback'));
            $this->assertTrue($this->allowed('https://APP.example/cb'));
            $this->assertFalse($this->allowed('https://evil.example/cb'));
            $this->assertFalse($this->allowed('https://app.example.evil.example/cb'));
            $this->assertFalse($this->allowed('/relative/cb'));
        });
    }

    public function testEmptyAllowListDeniesWhenAppUrlMissing(): void
    {
        $this->withEnv(['OAUTH_ALLOWED_REDIRECTS' => '', 'APP_URL' => ''], function () {
            $this->assertFalse($this->allowed('https://app.example/cb'));
        });
    }

    public function testExplicitAllowListUsesFnmatch(): void
    {
        $this->withEnv([
            'OAUTH_ALLOWED_REDIRECTS' => 'https://cb.example/*',
            'APP_URL' => 'https://app.example',
        ], function () {
            $this->assertTrue($this->allowed('https://cb.example/cb'));
            $this->assertFalse($this->allowed('https://app.example/cb'));
        });
    }
}
