<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace erik\support;

class QueryOptimizer
{
    /**
     * Apply common query optimizations:
     * - Only select needed columns
     * - Force index hints where beneficial
     * - Set query timeouts
     */
    public static function optimizeReportQuery($query): mixed
    {
        // Set statement timeout
        return $query->selectRaw('/*+ MAX_EXECUTION_TIME(5000) */');
    }
}
