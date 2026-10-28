<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 异常处理配置
 *
 * 使用 API 专用的 ExceptionHandler，所有异常返回 JSON 格式错误响应。
 */

return [
    // '' 为应用级，'@' 为跨应用全局；插件路由（plugin.*）需要 '@' 才能命中 JSON 处理器
    '' => erik\support\ExceptionHandler::class,
    '@' => erik\support\ExceptionHandler::class,
];
