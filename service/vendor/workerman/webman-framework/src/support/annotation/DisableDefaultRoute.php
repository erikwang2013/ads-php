<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */


namespace support\annotation;

use Attribute;

/**
 * @deprecated Use support\annotation\route\DisableDefaultRoute instead.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class DisableDefaultRoute extends \support\annotation\route\DisableDefaultRoute
{
}
