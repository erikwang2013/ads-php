<?php
namespace plugin\ads_api\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode(['code' => 401, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE));
        }

        $token = substr($header, 7);
        try {
            $payload = JWT::decode($token, new Key(config('app.jwt.secret'), 'HS256'));
            $request->userId = $payload->uid;
            $request->tenantId = $payload->tid ?? 1;
        } catch (\Throwable $e) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode(['code' => 401, 'message' => 'Token invalid or expired'], JSON_UNESCAPED_UNICODE));
        }

        return $handler($request);
    }
}
