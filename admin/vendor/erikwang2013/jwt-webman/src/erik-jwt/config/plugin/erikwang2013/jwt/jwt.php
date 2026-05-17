<?php
/*
 * JWT Webman Plugin - JWT authentication for webman framework
 * Copyright (c) 2026 erik
 * Author: erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

return [
    'enable' => true,
    'secret_key' => getenv('JWT_SECRET_KEY'),  //签名密钥
    'algorithm' => getenv('JWT_ALGORITHM'),  //签名算法：HS256, HS384, HS512, RS256等
    'issuer' => getenv('JWT_ISSUER'),   //签发者标识，用于验证令牌来源
    'audience' => getenv('JWT_AUDIENCE'),  //受众标识，用于验证令牌目标
    'leeway' => getenv('JWT_LEEWAY'),            //时间容差（秒），用于处理时钟偏差
    'default_expire' => getenv('JWT_DEFAULT_EXPIRE'), //默认令牌过期时间（秒）
    'refresh_expire' => getenv('JWT_REFRESH_EXPIRE'),  //刷新令牌过期时间（秒）
    'storage' => [
        'type' => getenv('JWT_STORAGE_TYPE'),  //存储类型：redis, database, memcached, file
        'database' => getenv('JWT_STORAGE_DATABASE'),
        'prefix' => getenv('JWT_STORAGE_PREFIX')
    ],
    'advanced' => [
        'retry_attempts' => getenv('JWT_ADVANCED_RETRY_ATTEMPTS'),   //操作失败重试次数
        'retry_delay' => getenv('JWT_ADVANCED_RETRY_DELAY'),    //重试延迟（毫秒）
        'auto_cleanup' => filter_var(getenv('JWT_ADVANCED_AUTO_CLEANUP') ?: '0', FILTER_VALIDATE_BOOLEAN),  //是否自动清理过期条目
        'cleanup_interval' => getenv('JWT_ADVANCED_CLEANUP_INTERVAL')   //自动清理间隔（秒）
    ]
];

