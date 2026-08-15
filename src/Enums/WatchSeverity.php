<?php

namespace EloquentWorks\Sentinel\Enums;

enum WatchSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
