<?php

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\ModerationCase;

final readonly class CaseOpened
{
    /**
     * Create a new event instance.
     *
     * @param  ModerationCase  $case
     * @return void
     */
    public function __construct(
        public ModerationCase $case,
    ) {
        //
    }
}
