<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
require_once __DIR__ . '/../vendor/autoload.php';

// Set up minimal environment for tests
putenv('APP_DEBUG=true');
putenv('JWT_SECRET=test-secret');
putenv('HASHIDS_SALT=test-salt');
putenv('DB_HOST=127.0.0.1');
putenv('DB_DATABASE=ads_test');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=' . (getenv('DB_PASSWORD') ?: 'root'));

// Initialize database capsule for tests
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => env('DB_HOST', '127.0.0.1'),
    'database'  => env('DB_DATABASE', 'ads_test'),
    'username'  => env('DB_USERNAME', 'root'),
    'password'  => env('DB_PASSWORD', ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => 'erik_',
], 'default');
$capsule->setAsGlobal();
$capsule->bootEloquent();
