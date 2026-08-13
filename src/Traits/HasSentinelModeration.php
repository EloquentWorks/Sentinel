<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSentinelModeration
{
    public function sentinelReports(): MorphMany
    {
        return $this->morphMany(config('sentinel.models.report'), 'subject');
    }

    public function sentinelCases(): MorphMany
    {
        return $this->morphMany(config('sentinel.models.case'), 'subject');
    }

    public function sentinelActions(): MorphMany
    {
        return $this->morphMany(config('sentinel.models.action'), 'target');
    }

    public function sentinelWatchlistEntries(): MorphMany
    {
        return $this->morphMany(config('sentinel.models.watchlist'), 'subject');
    }
}
