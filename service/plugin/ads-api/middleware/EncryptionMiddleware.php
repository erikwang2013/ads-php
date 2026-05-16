<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use Erikwang2013\Encryption\Encryption;

class EncryptionMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // Decrypt request body if encrypted
        $body = $request->rawBody();
        if (!empty($body) && $request->header('X-Encrypted')) {
            $decrypted = Encryption::decrypt($body, env('APP_ENCRYPTION_KEY', ''));
            $request->setRawBody($decrypted);
        }

        /** @var Response $response */
        $response = $handler($request);

        // Encrypt response if requested
        if ($request->header('X-Encrypted')) {
            $encrypted = Encryption::encrypt($response->rawBody(), env('APP_ENCRYPTION_KEY', ''));
            return new Response(200, ['Content-Type' => 'application/octet-stream', 'X-Encrypted' => '1'], $encrypted);
        }

        return $response;
    }
}
