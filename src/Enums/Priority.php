<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Enums;

enum Priority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';
    case Critical = 'critical';
}
