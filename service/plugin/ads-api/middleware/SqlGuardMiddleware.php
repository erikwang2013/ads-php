<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class SqlGuardMiddleware implements MiddlewareInterface
{
    protected array $patterns = [
        '/(\bUNION\b.*\bSELECT\b)/i',
        '/(\bDROP\b.*\bTABLE\b)/i',
        '/(\bALTER\b.*\bTABLE\b)/i',
        '/(\bEXEC\b.*\bxp_\b)/i',
        '/(--|\#|\/\*)/',
    ];

    public function process(Request $request, callable $handler): Response
    {
        $inputs = json_encode($request->all());
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $inputs)) {
                return new Response(403, ['Content-Type' => 'application/json'], json_encode([
                    'code' => 403,
                    'message' => 'Forbidden',
                ], JSON_UNESCAPED_UNICODE));
            }
        }
        return $handler($request);
    }
}
