<?php

namespace EloquentWorks\Sentinel\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSentinelModeration
{
    /**
     * Get moderation reports in which this model is the subject.
     *
     * @return MorphMany
     */
    public function sentinelReports(): MorphMany
    {
        // Return a polymorphic relationship to the report model defined in the Sentinel configuration.
        return $this->morphMany(
            config('sentinel.models.report'),
            'subject',
        );
    }

    /**
     * Get moderation cases opened against this model.
     *
     * @return MorphMany
     */
    public function sentinelCases(): MorphMany
    {
        // Return a polymorphic relationship to the case model defined in the Sentinel configuration.
        return $this->morphMany(
            config('sentinel.models.case'),
            'subject',
        );
    }

    /**
     * Get moderation actions applied to this model.
     *
     * @return MorphMany
     */
    public function sentinelActions(): MorphMany
    {
        // Return a polymorphic relationship to the action model defined in the Sentinel configuration.
        return $this->morphMany(
            config('sentinel.models.action'),
            'target',
        );
    }

    /**
     * Get watchlist entries associated with this model.
     *
     * @return MorphMany
     */
    public function sentinelWatchlistEntries(): MorphMany
    {
        // Return a polymorphic relationship to the watchlist model defined in the Sentinel configuration.
        return $this->morphMany(
            config('sentinel.models.watchlist'),
            'subject',
        );
    }
}
