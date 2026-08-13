<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Models\Concerns;

trait UsesSentinelTable
{
    protected string $sentinelTableKey = '';

    public function getTable(): string
    {
        return config('sentinel.tables.'.$this->sentinelTableKey, parent::getTable());
    }
}
