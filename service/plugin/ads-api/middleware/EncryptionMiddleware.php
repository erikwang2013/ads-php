<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use Erikwang2013\Encryption\EncryptionManager;
use Erikwang2013\Encryption\EncryptionManagerFactory;

class EncryptionMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // Decrypt request body if encrypted
        $body = $request->rawBody();
        if (!empty($body) && $request->header('X-Encrypted')) {
            $decrypted = $this->manager()->decrypt($body);
            $this->writeBody($request, $decrypted);
        }

        /** @var Response $response */
        $response = $handler($request);

        // Encrypt response if requested
        if ($request->header('X-Encrypted')) {
            $encrypted = $this->manager()->encrypt($response->rawBody());
            return new Response(200, ['Content-Type' => 'application/octet-stream', 'X-Encrypted' => '1'], $encrypted);
        }

        return $response;
    }

    protected function manager(): EncryptionManager
    {
        $key = env('APP_ENCRYPTION_KEY', '');
        // fromMasterKey 要求 32 字节主密钥；任意长度 key 用 sha256 派生，保证部署可用
        $master = strlen($key) === 32 ? $key : hash('sha256', $key, true);
        return EncryptionManagerFactory::fromMasterKey($master);
    }

    /**
     * Request 无 setRawBody()，按 workerman parsePost 相同规则把解密后的
     * body 解析进 data['post']，控制器经 post()/input()/all() 读取。
     */
    protected function writeBody(Request $request, string $decrypted): void
    {
        $contentType = $request->header('content-type', '');
        if (str_contains($contentType, 'json')) {
            $request->setPost((array)json_decode($decrypted, true));
        } else {
            parse_str($decrypted, $parsed);
            $request->setPost($parsed);
        }
    }
}
