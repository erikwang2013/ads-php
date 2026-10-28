<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

return [
    'files' => [
        base_path() . '/support/helpers.php',
        // 插件目录无 app.php 时 webman 不会加载其 config，需手动注册平台适配器
        base_path() . '/plugin/ads-platform/config/bootstrap.php',
    ],
];
