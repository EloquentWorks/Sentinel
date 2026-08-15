<?php

namespace EloquentWorks\Sentinel\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * Record a moderation event.
     *
     * @param  string  $event
     * @param  Authenticatable|null  $actor
     * @param  Model|null  $subject
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $metadata
     * @return Model|null
     */
    public function log(
        string $event,
        ?Authenticatable $actor = null,
        ?Model $subject = null,
        ?Model $auditable = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
    ): ?Model {
        // If auditing is disabled, return null without logging anything.
        if (! config('sentinel.audit.enabled', true)) {
            return null;
        }

        // Get the audit model class from the configuration.
        $auditModel = config('sentinel.models.audit');
        $request = app()->bound('request') ? request() : null;
        $actorModel = $actor instanceof Model ? $actor : null;

        // Create a new audit log entry with the provided information.
        return $auditModel::query()->create([
            'actor_type' => $actorModel?->getMorphClass(),
            'actor_id' => $actorModel?->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'event' => $event,
            'ip_address' => config('sentinel.audit.capture_ip', true)
                ? $request?->ip()
                : null,
            'user_agent' => config('sentinel.audit.capture_user_agent', true)
                ? $request?->userAgent()
                : null,
            'before' => $before ?: null,
            'after' => $after ?: null,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
