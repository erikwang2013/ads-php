<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ValidationMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 按来源写回：webman Request 无 set()，提供 setGet()/setPost()
        foreach ($this->sanitize($request->get()) as $key => $value) {
            $request->setGet($key, $value);
        }
        foreach ($this->sanitize($request->post()) as $key => $value) {
            $request->setPost($key, $value);
        }
        return $handler($request);
    }

    protected function sanitize(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            // 密码/密钥必须原样保留：trim/strip_tags 会破坏合法凭据
            if (is_string($value) && !$this->isSecretKey((string) $key)) {
                $value = trim($value);
                // Don't strip tags from rich text fields
                if (!in_array($key, ['description', 'content', 'extra'])) {
                    $value = strip_tags($value);
                }
            } elseif (is_array($value)) {
                $value = $this->sanitize($value);
            }
            $result[$key] = $value;
        }
        return $result;
    }

    protected function isSecretKey(string $key): bool
    {
        $key = strtolower($key);
        return str_contains($key, 'password') || str_ends_with($key, '_secret');
    }
}
