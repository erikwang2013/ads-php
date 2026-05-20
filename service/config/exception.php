<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 异常处理配置
 *
 * 使用 API 专用的 ExceptionHandler，所有异常返回 JSON 格式错误响应。
 */

return [
    '' => [
        '' => erik\support\ExceptionHandler::class,
    ],
];
