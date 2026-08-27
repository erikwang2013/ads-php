<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * ValidationMiddleware 测试：sanitize 纯逻辑（trim / strip_tags /
 * 富文本豁免 / 递归数组）。process 仅在输入为空时调用（不触碰
 * 已知 bug：$request->set() 不存在，见测试报告）。
 */

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Webman\Http\Request;
use Webman\Http\Response;
use plugin\ads_api\middleware\ValidationMiddleware;

/** 暴露 protected sanitize 供纯逻辑断言 */
class ExposableValidationMiddleware extends ValidationMiddleware
{
    public function exposeSanitize(array $data): array
    {
        return $this->sanitize($data);
    }
}

class ValidationMiddlewareTest extends TestCase
{
    public function testSanitizeTrimsAndStripsTags(): void
    {
        $mw = new ExposableValidationMiddleware();
        $result = $mw->exposeSanitize([
            'name'  => '  <script>alert(1)</script>Bob  ',
            'email' => '  bob@example.com  ',
        ]);

        $this->assertSame('alert(1)Bob', $result['name']);
        $this->assertSame('bob@example.com', $result['email']);
    }

    public function testSanitizeKeepsRichTextFields(): void
    {
        $mw = new ExposableValidationMiddleware();
        $result = $mw->exposeSanitize([
            'description' => '<p>Rich <b>text</b></p>',
            'content'     => '<h2>Title</h2>',
        ]);

        $this->assertSame('<p>Rich <b>text</b></p>', $result['description']);
        $this->assertSame('<h2>Title</h2>', $result['content']);
    }

    public function testSanitizeRecursesIntoArrays(): void
    {
        $mw = new ExposableValidationMiddleware();
        $result = $mw->exposeSanitize([
            'items' => [
                ['name' => ' <script>x</script>a '],
                ' <i>plain</i> ',
            ],
        ]);

        $this->assertSame('xa', $result['items'][0]['name']);
        $this->assertSame('plain', $result['items'][1]);
    }

    public function testProcessWithEmptyInputPassesThrough(): void
    {
        $request = new Request("GET /api/x HTTP/1.1\r\nHost: localhost\r\n\r\n");

        $called = false;
        $response = (new ValidationMiddleware())->process($request, function (Request $req) use (&$called) {
            $called = true;
            return new Response(200, [], 'ok');
        });

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
