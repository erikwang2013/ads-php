<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $response = $handler($request);

        $headers = [
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'DENY',
            'X-XSS-Protection'        => '1; mode=block',
            'Referrer-Policy'         => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        // CSP for SPA (applies to HTML responses only — API responses ignore it)
        if ($request->path() === '/' || !str_starts_with($request->path(), '/api/')) {
            $headers['Content-Security-Policy'] = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'";
        }

        $proto = $request->header('X-Forwarded-Proto', '');
        if ($proto === 'https' || env('FORCE_HSTS', false)) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }

        foreach ($headers as $key => $value) {
            $response->withHeader($key, $value);
        }

        return $response;
    }
}
