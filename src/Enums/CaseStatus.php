<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Enums;

enum CaseStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Waiting = 'waiting';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Closed = 'closed';
}
