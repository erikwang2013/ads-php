<?php
/**
 * Elasticsearch / Meilisearch configuration for webman-scout.
 * Models that use the Searchable trait will auto-sync to the configured driver.
 */
return [
    'driver' => getenv('SCOUT_DRIVER', 'elasticsearch'),
    'elasticsearch' => [
        'hosts' => [getenv('ES_HOST', '127.0.0.1:9200')],
        'index' => getenv('ES_INDEX', 'ads_platform'),
    ],
];
