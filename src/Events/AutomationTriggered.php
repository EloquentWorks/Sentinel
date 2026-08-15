<?php

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\AutomationRule;

final readonly class AutomationTriggered
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public AutomationRule $rule,
    ) {
        //
    }
}
