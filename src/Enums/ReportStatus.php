<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Enums;

enum ReportStatus: string
{
    case New = 'new';
    case Triaged = 'triaged';
    case InReview = 'in_review';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
    case Duplicate = 'duplicate';
}
