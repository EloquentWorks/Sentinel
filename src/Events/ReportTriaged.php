<?php

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\ModerationReport;

final readonly class ReportTriaged
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public ModerationReport $report,
    ) {
        //
    }
}
