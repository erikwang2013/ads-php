<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 点击验证码服务 — 基于 erikwang2013/poster-php
 *
 * 流程：
 *   generate() → 返回 puzzle 图片 + token（前端渲染滑块）
 *   verify()   → 验证滑块偏移量是否在容差范围内
 */
namespace erik\support;

use Erikwang2013\PosterPhp\Poster;

class CaptchaService
{
    protected Poster $poster;

    public function __construct()
    {
        $this->poster = new Poster();
    }

    /**
     * 生成验证码 — 返回背景图、拼图块、以及加密 token
     */
    public function generate(): array
    {
        $captcha = $this->poster->generate([
            'width'    => 300,
            'height'   => 150,
            'accuracy' => 5,  // 容差（像素）
        ]);

        // token 包含正确答案，服务端加密存储，防止前端篡改
        $payload = [
            'x'      => $captcha['x'],
            'y'      => $captcha['y'],
            'expire' => time() + 300,  // 5 分钟有效
        ];
        $token = $this->encode($payload);

        return [
            'bg_image' => $captcha['bg'],      // 背景图 base64
            'pz_image' => $captcha['puzzle'],  // 拼图块 base64
            'token'    => $token,              // 加密 token
        ];
    }

    /**
     * 验证滑块偏移量
     */
    public function verify(string $token, int $offsetX): bool
    {
        $payload = $this->decode($token);
        if (!$payload || $payload['expire'] < time()) {
            return false;
        }
        return abs($offsetX - $payload['x']) <= 5; // 5px 容差
    }

    protected function encode(array $data): string
    {
        $json = json_encode($data);
        $key  = env('APP_ENCRYPTION_KEY', 'poster-key');
        $iv   = substr(md5($key), 0, 16);
        return base64_encode(openssl_encrypt($json, 'AES-128-CBC', $key, 0, $iv));
    }

    protected function decode(string $token): ?array
    {
        $key = env('APP_ENCRYPTION_KEY', 'poster-key');
        $iv  = substr(md5($key), 0, 16);
        $json = openssl_decrypt(base64_decode($token), 'AES-128-CBC', $key, 0, $iv);
        return $json ? json_decode($json, true) : null;
    }
}
