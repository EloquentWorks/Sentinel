<?php

namespace EloquentWorks\Sentinel\Models\Concerns;

trait UsesSentinelTable
{
    /**
     * Get the configuration key under sentinel.tables for this model.
     */
    abstract protected function sentinelTableKey(): string;

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        // Check if the table is defined in the sentinel configuration
        $table = config(
            'sentinel.tables.'.$this->sentinelTableKey()
        );

        // If the table is defined and is a non-empty string, return it; otherwise,
        // fall back to the parent implementation
        return is_string($table) && $table !== ''
            ? $table
            : parent::getTable();
    }
}
