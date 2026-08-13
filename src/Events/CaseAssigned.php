<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\CaseAssignment;

final readonly class CaseAssigned
{
    public function __construct(public CaseAssignment $assignment) {}
}
