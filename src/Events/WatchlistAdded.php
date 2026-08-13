<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\WatchlistEntry;

final readonly class WatchlistAdded
{
    public function __construct(public WatchlistEntry $entry) {}
}
