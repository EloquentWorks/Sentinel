<?php

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\ModerationReport;

final readonly class ReportCreated
{
    /**
     * Create a new event instance.
     *
     * @param  ModerationReport  $report
     * @return void
     */
    public function __construct(
        public ModerationReport $report,
    ) {
        //
    }
}
