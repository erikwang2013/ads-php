<?php
/**
 * Elasticsearch / Meilisearch configuration for webman-scout.
 * Models that use the Searchable trait will auto-sync to the configured driver.
 */
return [
    'driver' => env('SCOUT_DRIVER', 'elasticsearch'),
    'elasticsearch' => [
        'hosts' => [env('ES_HOST', '127.0.0.1:9200')],
        'index' => env('ES_INDEX', 'ads_platform'),
    ],
];
