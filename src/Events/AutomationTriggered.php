<?php

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\AutomationRule;

final readonly class AutomationTriggered
{
    /**
     * Create a new event instance.
     *
     * @param  AutomationRule  $rule
     * @return void
     */
    public function __construct(
        public AutomationRule $rule,
    ) {
        //
    }
}
