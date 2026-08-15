<?php

namespace EloquentWorks\Sentinel\Listeners;

use EloquentWorks\Sentinel\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

final class ExternalModerationEventRecorder
{
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(
        private readonly AuditLogger $audit,
    ) {
        //
    }

    /**
     * Record a package event without coupling Sentinel to every event class.
     */
    public function handle(object $event): void
    {
        // Record the event class name for later use in the audit log.
        $metadata = [
            'event_class' => $event::class,
        ];

        // Capture scalar fields directly and reduce Eloquent models to morph IDs.
        foreach (get_object_vars($event) as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $metadata[$key] = $value;

                // Skip to the next iteration of the loop since we don't need to process scalar values further.
                continue;
            }

            // If the value is an Eloquent model, store its morph class and primary key in the metadata.
            if ($value instanceof Model) {
                $metadata[$key] = [
                    'type' => $value->getMorphClass(),
                    'id' => $value->getKey(),
                ];
            }
        }

        // Log the event with the constructed metadata.
        $this->audit->log(
            event: 'external.'.class_basename($event),
            metadata: $metadata,
        );
    }
}
