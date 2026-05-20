<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    private function getAllowedOrigin(Request $request): string
    {
        // Debug mode: allow all origins (backward compatible)
        if (env('APP_DEBUG', false)) {
            return '*';
        }

        $origin = $request->header('Origin', '');
        if ($origin === '') {
            return '';
        }

        $allowed = $this->getAllowedOrigins();
        foreach ($allowed as $pattern) {
            if ($origin === $pattern || fnmatch($pattern, $origin)) {
                return $origin;
            }
        }

        return '';
    }

    private function getAllowedOrigins(): array
    {
        $origins = env('CORS_ALLOWED_ORIGINS', '');
        if ($origins === '') {
            $origins = env('APP_URL', 'http://127.0.0.1:8788');
        }
        return array_map('trim', explode(',', $origins));
    }

    public function process(Request $request, callable $handler): Response
    {
        $allowedOrigin = $this->getAllowedOrigin($request);
        $commonHeaders = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Tenant-Id, X-Encrypted, X-API-Version, X-Client-Platform',
        ];

        if ($request->method() === 'OPTIONS') {
            $headers = $commonHeaders;
            if ($allowedOrigin !== '') {
                $headers['Access-Control-Allow-Origin'] = $allowedOrigin;
                if ($allowedOrigin !== '*') {
                    $headers['Access-Control-Allow-Credentials'] = 'true';
                }
            }
            $headers['Access-Control-Max-Age'] = '86400';
            return new Response(204, $headers, '');
        }

        $response = $handler($request);
        if ($allowedOrigin !== '') {
            $headers = array_merge($commonHeaders, [
                'Access-Control-Allow-Origin' => $allowedOrigin,
            ]);
            if ($allowedOrigin !== '*') {
                $headers['Access-Control-Allow-Credentials'] = 'true';
            }
            $response->withHeaders($headers);
        }
        return $response;
    }
}
