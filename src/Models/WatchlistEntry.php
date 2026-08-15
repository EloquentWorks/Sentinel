<?php

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Enums\WatchSeverity;
use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WatchlistEntry extends Model
{
    use UsesSentinelTable;

    /**
     * The name of the "sentinel" table associated with the model.
     */
    protected string $sentinelTableKey = 'watchlist';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // Define the attribute casting for the model, including custom enum casting for severity.
        return [
            'severity' => WatchSeverity::class,
            'active' => 'boolean',
            'metadata' => 'array',
            'expires_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the subject placed on the watchlist.
     */
    public function subject(): MorphTo
    {
        // Define a polymorphic relationship to the subject of the watchlist entry.
        return $this->morphTo();
    }

    /**
     * Get the staff member who created the watchlist entry.
     */
    public function addedBy(): MorphTo
    {
        // Define a polymorphic relationship to the staff member who added the watchlist entry.
        return $this->morphTo('added_by');
    }
}
