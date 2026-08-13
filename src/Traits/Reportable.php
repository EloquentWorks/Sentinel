<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Reportable
{
    public function sentinelReportsAsContent(): MorphMany
    {
        return $this->morphMany(config('sentinel.models.report'), 'reportable');
    }

    public function sentinelContentHolds(): MorphMany
    {
        return $this->morphMany(config('sentinel.models.hold'), 'reportable');
    }
}
