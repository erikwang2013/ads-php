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

use Erikwang2013\Poster\Captcha\CaptchaManager;
use Erikwang2013\Poster\Drivers\DriverFactory;
use Erikwang2013\Poster\Storage\StorageFactory;

class CaptchaService
{
    protected CaptchaManager $manager;

    public function __construct()
    {
        // 固定 GD 驱动：imagick 自动探测在该环境下 clone() 未初始化资源会崩
        $this->manager = new CaptchaManager(DriverFactory::create('gd'), StorageFactory::create());
    }

    /**
     * 生成验证码 — 返回背景图、拼图块、以及 token（答案存于服务端存储）
     */
    public function generate(): array
    {
        $captcha = $this->manager->create('slider')->generate();

        // 包 output() 返回 'data:image/png;base64,...'，前端自行拼前缀，这里剥离为纯 base64
        $stripPrefix = fn(string $uri): string => substr($uri, strpos($uri, ',') + 1);

        return [
            'bg_image' => $stripPrefix($captcha['image']),            // 背景图 base64
            'pz_image' => $stripPrefix($captcha['extra']['puzzle']),  // 拼图块 base64
            'token'    => $captcha['key'],                            // 校验 key
        ];
    }

    /**
     * 验证滑块偏移量（容差取包配置 captcha.tolerance.slider，默认 4px）
     */
    public function verify(string $token, int $offsetX): bool
    {
        return $this->manager->verify($token, ['type' => 'slider', 'data' => $offsetX]);
    }
}
