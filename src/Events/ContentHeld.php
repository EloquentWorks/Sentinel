<?php

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\ContentHold;

final readonly class ContentHeld
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public ContentHold $hold,
    ) {
        //
    }
}
