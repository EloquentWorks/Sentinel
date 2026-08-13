<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\ModerationAction;

final readonly class ModerationActionApplied
{
    public function __construct(public ModerationAction $action) {}
}
