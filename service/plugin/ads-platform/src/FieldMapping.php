<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_platform\src;

class FieldMapping
{
    public function __construct(
        protected array $fieldMap,
        protected array $statusMap,
        protected mixed $valueTransformer = null,
    ) {}

    public function map(array $raw): array
    {
        $unified = ['extra' => []];

        foreach ($raw as $platformField => $value) {
            if (isset($this->fieldMap[$platformField])) {
                $unifiedField = $this->fieldMap[$platformField];
                $unified[$unifiedField] = $value;
            } else {
                $unified['extra'][$platformField] = $value;
            }
        }

        if (isset($unified['status']) && isset($this->statusMap[$unified['status']])) {
            $unified['status'] = $this->statusMap[$unified['status']];
        }

        if ($this->valueTransformer) {
            $unified = ($this->valueTransformer)($unified);
        }

        return $unified;
    }
}
