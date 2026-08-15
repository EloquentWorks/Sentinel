<?php

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\WatchlistEntry;

final readonly class WatchlistAdded
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public WatchlistEntry $entry,
    ) {
        //
    }
}
