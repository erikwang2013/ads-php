<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace admin\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class AttackGuardMiddleware implements MiddlewareInterface
{
    protected const XSS_PATTERNS = [
        '/<script\b/i', '/<iframe\b/i', '/<object\b/i', '/<embed\b/i',
        '/javascript\s*:/i', '/data\s*:\s*text\/html/i', '/vbscript\s*:/i',
        '/on\w+\s*=/i', '/expression\s*\(/i', '/eval\s*\(/i',
        '/document\.cookie/i', '/<link\b[^>]*rel\s*=\s*["\']?stylesheet/i',
    ];

    protected const PATH_PATTERNS = [
        '/\.\.\//', '/\.\.\\\\/', '/\x00/', '/%00/',
        '/etc\/passwd/i', '/\/\.env/i', '/\/\.git\//i',
    ];

    protected int $maxBodySize = 10485760;
    protected int $maxStrLen = 8192;

    protected array $allowedContentTypes = [
        'application/json', 'application/x-www-form-urlencoded',
        'multipart/form-data', 'text/plain',
    ];

    public function process(Request $request, callable $handler): Response
    {
        if ($this->detectPathAttack($request->path())) {
            return $this->block('Path traversal detected');
        }
        if ($this->bodyTooLarge($request)) {
            return $this->block('Request body too large');
        }
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            if (!$this->validContentType($request)) {
                return $this->block('Invalid Content-Type');
            }
        }
        foreach ($this->collectInputs($request) as $key => $value) {
            if (is_string($value) && $this->detectXss($value)) {
                return $this->block("XSS pattern detected in: $key");
            }
        }
        foreach ($request->header() ?: [] as $name => $value) {
            if (is_string($value) && preg_match('/[\r\n]/', $value)) {
                return $this->block("Header injection detected in: $name");
            }
        }
        return $handler($request);
    }

    protected function detectXss(string $value): bool
    {
        if (strlen($value) > $this->maxStrLen) return true;
        foreach (self::XSS_PATTERNS as $p) { if (preg_match($p, $value)) return true; }
        return false;
    }

    protected function detectPathAttack(string $path): bool
    {
        foreach (self::PATH_PATTERNS as $p) { if (preg_match($p, $path)) return true; }
        return false;
    }

    protected function bodyTooLarge(Request $request): bool
    {
        $raw = $request->rawBody();
        return $raw !== null && strlen($raw) > $this->maxBodySize;
    }

    protected function validContentType(Request $request): bool
    {
        $ct = $request->header('Content-Type', '');
        if ($ct === '') return true;
        $ct = strtolower(trim(explode(';', $ct)[0]));
        foreach ($this->allowedContentTypes as $a) { if ($ct === $a) return true; }
        return false;
    }

    protected function collectInputs(Request $request): array
    {
        return array_merge($request->get() ?: [], $request->post() ?: [], $request->all() ?: []);
    }

    protected function block(string $reason): Response
    {
        return new Response(403, ['Content-Type' => 'application/json'], json_encode([
            'code' => 403, 'message' => 'Forbidden: ' . $reason,
        ], JSON_UNESCAPED_UNICODE));
    }
}
