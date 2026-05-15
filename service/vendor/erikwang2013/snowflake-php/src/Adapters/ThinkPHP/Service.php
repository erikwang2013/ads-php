<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Snowflake\Adapters\ThinkPHP;

use think\Service as BaseService;

class Service extends BaseService
{
    public function register(): void
    {
        $this->app->bind('snowflake', function () {
            return \Snowflake\Snowflake::fromConfig(
                $this->app->config->get('snowflake', [])
            );
        });

        $this->app->bind(\Snowflake\Snowflake::class, function () {
            return $this->app->make('snowflake');
        });
    }
}
