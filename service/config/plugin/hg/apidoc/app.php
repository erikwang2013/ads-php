<?php
return [
    'enable' => true,
    'apidoc' => [
        'title' => 'Ads Platform API 文档',
        'desc'  => '多平台广告管理系统 — 业务 API + 管理后台 API',
        'apps'  => [
            ['title' => 'Service API', 'path' => 'plugin\ads_api\controller\v1', 'key' => 'service'],
            ['title' => 'Admin API',  'path' => 'admin\controller',            'key' => 'admin'],
        ],
        'definitions' => "app\common\controller\Definitions",
        'auto_url' => ['letter_rule' => 'lcfirst', 'prefix' => ''],
        'auto_register_routes' => false,
        'cache' => ['enable' => false],
        'auth'  => ['enable' => false],
        'params' => ['header' => [
            ['name' => 'X-Client-Platform', 'type' => 'string', 'require' => true,  'desc' => '来源端: web/ios/android/macos/windows/linux/harmonyos'],
            ['name' => 'Authorization',     'type' => 'string', 'require' => false, 'desc' => 'Bearer <JWT Token>'],
        ]],
        'responses' => [
            'success' => [
                ['name' => 'code',    'desc' => '业务代码(0=成功)', 'type' => 'int',    'require' => 1],
                ['name' => 'message', 'desc' => '业务信息',        'type' => 'string', 'require' => 1],
                ['name' => 'data',    'desc' => '业务数据',        'main' => true,     'type' => 'object', 'require' => 1],
            ],
        ],
        'default_author' => 'erik <erik@erik.xyz>',
        'default_method' => 'GET',
        'ignored_annitation' => ['api', 'apiGroup', 'apiName', 'apiParam', 'apiSuccess', 'apiHeader'],
        'ignored_methods' => ['__construct', 'init', 'validateRule', 'getCurrentUser', 'isAllowedRedirect'],
    ]
];
