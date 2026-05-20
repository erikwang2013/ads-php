<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * API-oriented exception handler — renders JSON errors instead of HTML.
 *
 * Registered via config/exception.php to replace the default webman handler.
 */

namespace erik\support;

use Webman\Exception\ExceptionHandler as BaseHandler;
use Webman\Http\Request;
use Webman\Http\Response;
use Throwable;

class ExceptionHandler extends BaseHandler
{
    public function render(Request $request, Throwable $e): Response
    {
        $code = $e->getCode();
        if ($code < 400 || $code >= 600) {
            $code = 500;
        }

        $body = [
            'code'    => $code,
            'message' => $this->debug ? $e->getMessage() : 'Internal Server Error',
        ];

        if ($this->debug) {
            $body['debug'] = [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ];
        }

        return new Response($code, ['Content-Type' => 'application/json'],
            json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
