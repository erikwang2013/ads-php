<?php
namespace plugin\ads_platform\src;

class ReportRequest
{
    public function __construct(
        public string $dateStart,
        public string $dateEnd,
        public string $granularity = 'daily',
        public array  $dimensions = [],
        public array  $metrics = [],
        public int    $pageSize = 100,
        public ?string $cursor = null,
    ) {}
}
