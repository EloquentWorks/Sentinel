<?php

namespace EloquentWorks\Sentinel\Enums;

enum ActionStatus: string
{
    case Pending = 'pending';
    case Applied = 'applied';
    case Failed = 'failed';
    case Revoked = 'revoked';
}
