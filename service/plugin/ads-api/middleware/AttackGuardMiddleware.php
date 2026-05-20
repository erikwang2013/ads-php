<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * AttackGuardMiddleware — detects and blocks common web/API attacks.
 *
 * Detections:
 *   1. XSS — script tags, event handlers, javascript:/data: URIs
 *   2. Path traversal — ../, ..\\, null bytes in URL path
 *   3. Header injection — CR/LF in header values
 *   4. Request body size — oversized payloads
 *   5. Content-Type — rejects unexpected types on POST/PUT
 */

namespace plugin\ads_api\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class AttackGuardMiddleware implements MiddlewareInterface
{
    // XSS detection patterns
    protected const XSS_PATTERNS = [
        '/<script\b/i',
        '/<iframe\b/i',
        '/<object\b/i',
        '/<embed\b/i',
        '/javascript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/vbscript\s*:/i',
        '/on\w+\s*=/i',       // onerror=, onclick=, onload=, etc.
        '/expression\s*\(/i',  // CSS expression()
        '/eval\s*\(/i',        // eval()
        '/document\.cookie/i',
        '/<link\b[^>]*rel\s*=\s*["\']?stylesheet/i',
    ];

    // Path traversal patterns
    protected const PATH_PATTERNS = [
        '/\.\.\//',            // ../
        '/\.\.\\\\/',          // ..\\
        '/\x00/',              // null byte
        '/%00/',               // URL-encoded null byte
        '/etc\/passwd/i',
        '/\/\.env/i',          // dotenv exposure
        '/\/\.git\//i',        // git exposure
    ];

    protected int $maxBodySize = 10485760; // 10 MiB

    protected array $allowedContentTypes = [
        'application/json',
        'application/x-www-form-urlencoded',
        'multipart/form-data',
        'text/plain',
    ];

    public function process(Request $request, callable $handler): Response
    {
        // 1. Path traversal check
        if ($this->detectPathAttack($request->path())) {
            return $this->block('Path traversal detected');
        }

        // 2. Request body size check
        if ($this->bodyTooLarge($request)) {
            return $this->block('Request body too large');
        }

        // 3. Content-Type validation for state-changing methods
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            if (!$this->validContentType($request)) {
                return $this->block('Invalid Content-Type');
            }
        }

        // 4. XSS scan on all inputs
        $inputs = $this->collectInputs($request);
        foreach ($inputs as $key => $value) {
            if (is_string($value) && $this->detectXss($value)) {
                return $this->block("XSS pattern detected in: $key");
            }
        }

        // 5. Header injection check
        foreach ($request->header() ?: [] as $name => $value) {
            if (is_string($value) && $this->detectHeaderInjection($value)) {
                return $this->block("Header injection detected in: $name");
            }
        }

        return $handler($request);
    }

    protected function detectXss(string $value): bool
    {
        foreach (self::XSS_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        return false;
    }

    protected function detectPathAttack(string $path): bool
    {
        foreach (self::PATH_PATTERNS as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }
        return false;
    }

    protected function detectHeaderInjection(string $value): bool
    {
        // CRLF injection in header values
        return (bool) preg_match('/[\r\n]/', $value);
    }

    protected function bodyTooLarge(Request $request): bool
    {
        $raw = $request->rawBody();
        return $raw !== null && strlen($raw) > $this->maxBodySize;
    }

    protected function validContentType(Request $request): bool
    {
        $ct = $request->header('Content-Type', '');
        if ($ct === '') return true; // no Content-Type = OK (browser may omit for GET)

        // Strip charset suffix for matching
        $ct = strtolower(trim(explode(';', $ct)[0]));

        foreach ($this->allowedContentTypes as $allowed) {
            if ($ct === $allowed) return true;
        }
        return false;
    }

    protected function collectInputs(Request $request): array
    {
        return array_merge(
            $request->get() ?: [],
            $request->post() ?: [],
            $request->all() ?: [],
        );
    }

    protected function block(string $reason): Response
    {
        return new Response(403, ['Content-Type' => 'application/json'], json_encode([
            'code'    => 403,
            'message' => 'Forbidden: ' . $reason,
        ], JSON_UNESCAPED_UNICODE));
    }
}
