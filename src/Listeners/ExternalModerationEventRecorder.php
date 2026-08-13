<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Listeners;

use EloquentWorks\Sentinel\Services\AuditLogger;

final class ExternalModerationEventRecorder
{
    public function __construct(private readonly AuditLogger $audit) {}
    public function handle(object $event): void
    {
        $metadata = ['event_class' => $event::class];
        foreach (get_object_vars($event) as $key => $value) {
            if (is_scalar($value) || $value === null) $metadata[$key] = $value;
            elseif ($value instanceof \Illuminate\Database\Eloquent\Model) $metadata[$key] = ['type'=>$value->getMorphClass(),'id'=>$value->getKey()];
        }
        $this->audit->log('external.'.class_basename($event), metadata: $metadata);
    }
}
