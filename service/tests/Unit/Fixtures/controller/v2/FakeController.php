<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Tests\Unit\Fixtures\controller\v2;

use Webman\Http\Request;
use Webman\Http\Response;

class FakeController
{
    public function index(Request $request): Response
    {
        return new Response(200, [], 'v2:' . ($request->apiVersion ?? 'none'));
    }
}
