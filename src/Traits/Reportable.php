<?php

namespace EloquentWorks\Sentinel\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Reportable
{
    /**
     * Get Sentinel reports filed against this content model.
     */
    public function sentinelReportsAsContent(): MorphMany
    {
        // Return a polymorphic relationship to the report model defined in the Sentinel configuration.
        return $this->morphMany(
            config('sentinel.models.report'),
            'reportable',
        );
    }

    /**
     * Get moderation holds placed on this content model.
     */
    public function sentinelContentHolds(): MorphMany
    {
        // Return a polymorphic relationship to the hold model defined in the Sentinel configuration.
        return $this->morphMany(
            config('sentinel.models.hold'),
            'reportable',
        );
    }
}
