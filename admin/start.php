<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createUnsafeMutable(__DIR__)->load();

// Load project helpers
require_once __DIR__ . '/app/helpers.php';

// The webman-admin plugin handles its own bootstrap
// This start.php just needs the autoloader
