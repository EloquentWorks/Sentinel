<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\ModerationCase;

final readonly class CaseOpened
{
    public function __construct(public ModerationCase $case) {}
}
