<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * versioned() 路由助手测试夹具：v1/v2 两个版本的假控制器。
 * 命名空间含 \controller\v1\ 段以匹配 versioned() 的正则替换逻辑。
 */

namespace Tests\Unit\Fixtures\controller\v1;

use Webman\Http\Request;
use Webman\Http\Response;

class FakeController
{
    public function index(Request $request): Response
    {
        return new Response(200, [], 'v1:' . ($request->apiVersion ?? 'none'));
    }

    public function noRequestArg(): Response
    {
        return new Response(200, [], 'v1-no-arg');
    }
}
