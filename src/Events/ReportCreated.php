<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\ModerationReport;

final readonly class ReportCreated
{
    public function __construct(public ModerationReport $report) {}
}
