<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\AutomationRule;

final readonly class AutomationTriggered
{
    public function __construct(public AutomationRule $rule) {}
}
