<?php

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\CaseAssignment;

final readonly class CaseAssigned
{
    /**
     * Create a new event instance.
     *
     * @param  CaseAssignment  $assignment
     * @return void
     */
    public function __construct(
        public CaseAssignment $assignment,
    ) {
        //
    }
}
