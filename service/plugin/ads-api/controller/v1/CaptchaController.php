<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 验证码接口
 *   GET  /api/captcha/generate — 生成验证码
 *   POST /api/captcha/verify   — 验证（供敏感操作前置调用）
 */
namespace plugin\ads_api\controller\v1;

use erik\support\CaptchaService;
use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use Throwable;

class CaptchaController
{
    public function generate(): \Webman\Http\Response
    {
        try {
            $service = new CaptchaService();
            return ApiResponse::success($service->generate());
        } catch (Throwable $e) {
            return ApiResponse::error('Captcha generation failed: ' . $e->getMessage());
        }
    }

    public function verify(Request $request): \Webman\Http\Response
    {
        $token   = $request->post('token', '');
        $offsetX = (int) $request->post('offset_x', 0);

        if (empty($token)) {
            return ApiResponse::error('Token is required');
        }

        $service = new CaptchaService();
        $result = $service->verify($token, $offsetX);

        return ApiResponse::success(['valid' => $result], $result ? '验证通过' : '验证失败');
    }
}
