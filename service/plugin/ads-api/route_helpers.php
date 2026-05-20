<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Route helper that resolves versioned controllers at request time.
 *
 * Usage:
 *   Route::get('/api/foo', versioned(FooController::class, 'index'));
 *
 * The closure reads $request->apiVersion (set by VersionMiddleware), replaces
 * the version segment after `controller\` in the class name, and invokes the
 * method with the correct arguments (Request + route params as applicable).
 */

use Webman\Http\Request;

function versioned(string $baseClass, string $method): Closure
{
    static $cache = [];

    $key = $baseClass . '::' . $method;
    if (!isset($cache[$key])) {
        $rm = new ReflectionMethod($baseClass, $method);
        $params = $rm->getParameters();
        $needsRequest = false;
        if (!empty($params)) {
            $firstType = $params[0]->getType();
            $needsRequest = $firstType instanceof ReflectionNamedType
                && is_a(Request::class, $firstType->getName(), true);
        }
        $cache[$key] = $needsRequest;
    }

    $needsRequest = $cache[$key];

    return function (Request $request) use ($baseClass, $method, $needsRequest) {
        $version = $request->apiVersion ?? 'v1';
        // Replace the version segment: \controller\v1\ → \controller\{version}\
        $class = preg_replace(
            '/\\\\controller\\\\[^\\\\]+\\\\/',
            '\\controller\\' . $version . '\\',
            $baseClass
        );

        if (!class_exists($class)) {
            return new \Webman\Http\Response(400, ['Content-Type' => 'application/json'],
                json_encode(['code' => 400, 'message' => "API version '$version' not available for this endpoint"], JSON_UNESCAPED_UNICODE));
        }

        $controller = \container()->get($class);
        $args = [];
        if ($needsRequest) {
            $args[] = $request;
        }
        if ($request->route) {
            foreach ($request->route->param() as $v) {
                $args[] = $v;
            }
        }
        return $controller->$method(...$args);
    };
}
