<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Elasticsearch / Meilisearch 配置（erikwang2013/webman-scout）
 *
 * 使用 Searchable trait 的 Model 会自动同步到配置的搜索引擎。
 */

return [
    // 搜索引擎驱动：elasticsearch 或 meilisearch
    'driver' => env('SCOUT_DRIVER', 'elasticsearch'),

    'elasticsearch' => [
        // ES 集群节点（多节点用逗号分隔）
        'hosts' => [env('ES_HOST', '127.0.0.1:9200')],

        // ES 索引名前缀
        'index' => env('ES_INDEX', 'ads_platform'),
    ],
];
