<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    Dotenv\Dotenv::createUnsafeMutable(__DIR__)->load();
}

// 升级兼容：旧安装只有 APP_ENCRYPTION_KEY；Encryptable cast 只读 ENCRYPTION_KEY
$legacyEncryptionKey = (string) env('APP_ENCRYPTION_KEY', '');
if (!env('ENCRYPTION_KEY') && $legacyEncryptionKey !== '') {
    // EnvEncryptableConfig 优先读 $_ENV/$_SERVER，.env 里的空值会遮蔽 putenv
    $_ENV['ENCRYPTION_KEY'] = $_SERVER['ENCRYPTION_KEY'] = $legacyEncryptionKey;
    putenv('ENCRYPTION_KEY=' . $legacyEncryptionKey);
}

// Initialize Database
$capsule = new DB;
$connections = require __DIR__ . '/config/database.php';
$capsule->addConnection($connections['connections']['shared'], 'shared');
$capsule->setAsGlobal();
$capsule->getDatabaseManager()->setDefaultConnection('shared');
$capsule->bootEloquent();

// Start webman (loads configs, routes, and runs workers)
support\App::run();
