<?php

namespace EloquentWorks\Sentinel\Models\Concerns;

trait UsesSentinelTable
{
    /**
     * Configuration key under sentinel.tables for the model.
     */
    protected string $sentinelTableKey = '';

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        // If the sentinelTableKey is not set, fall back to the default table name.
        return config(
            'sentinel.tables.'.$this->sentinelTableKey,
            parent::getTable(),
        );
    }
}
