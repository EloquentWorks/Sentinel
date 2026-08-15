<?php

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\ModerationAction;

final readonly class ModerationActionApplied
{
    /**
     * Create a new event instance.
     *
     * @param  ModerationAction  $action
     * @return void
     */
    public function __construct(
        public ModerationAction $action,
    ) {
        //
    }
}
