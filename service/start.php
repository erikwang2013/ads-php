<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\adapter\Juliang;

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    Dotenv\Dotenv::createUnsafeMutable(__DIR__)->load();
}

// Initialize Database
$capsule = new DB;
$connections = require __DIR__ . '/config/database.php';
$capsule->addConnection($connections['connections']['shared'], 'shared');
$capsule->setAsGlobal();
$capsule->getDatabaseManager()->setDefaultConnection('shared');
$capsule->bootEloquent();

// Configure paginator
\Illuminate\Pagination\Paginator::currentPageResolver(function ($pageName = 'page') {
    return (int) ($_GET[$pageName] ?? 1);
});

// Initialize Redis
$redisConfig = require __DIR__ . '/config/redis.php';

// Register platform adapters
AdapterRegistry::register(new Juliang());

// Start webman (loads configs, routes, and runs workers)
support\App::run();
