<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * webman 静态文件配置。webman-framework 内核（App.php）检查 static.enable
 * 并自带静态文件处理，无需额外插件；生产环境可改由 nginx location 提供。
 */

return [
    'enable' => true,
    'middleware' => [],
    'dir' => public_path(),
];
