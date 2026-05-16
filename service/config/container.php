<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * 依赖注入容器配置（PSR-11 兼容）
 *
 * 在此将接口绑定到具体实现类，或注册需要在应用生命周期内共享的单例服务。
 *
 * 使用示例：
 *   $container->add(LoggerInterface::class, MonologLogger::class);
 *   $container->addSingleton(Database::class, fn() => new Database($config));
 *
 * @see https://webman.workerman.net/doc/en/container.html
 */

$container = new Webman\Container();

// ============================================================================
// 服务绑定
// ============================================================================
// 在下面注册常用服务，之后可通过 app()->get(服务名::class) 获取实例，
// 或通过构造函数自动注入。

return $container;
