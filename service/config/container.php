<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Dependency Injection container configuration (PSR-11 compatible).
 *
 * Bind interfaces to concrete implementations, or register singleton
 * services that should be shared across the application lifecycle.
 *
 * Example usage:
 *   $container->add(LoggerInterface::class, MonologLogger::class);
 *   $container->addSingleton(Database::class, fn() => new Database($config));
 *
 * @see https://webman.workerman.net/doc/en/container.html
 */

$container = new Webman\Container();

// --- Service Bindings ---
// Register commonly used services below for constructor injection
// and app-level singleton access via app()->get(SomeService::class).

return $container;
