<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    public function log(
        string $event,
        ?Authenticatable $actor = null,
        ?Model $subject = null,
        ?Model $auditable = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
    ): ?Model {
        if (! config('sentinel.audit.enabled', true)) {
            return null;
        }

        $model = config('sentinel.models.audit');
        $request = app()->bound('request') ? request() : null;

        return $model::query()->create([
            'actor_type' => $actor instanceof Model ? $actor->getMorphClass() : null,
            'actor_id' => $actor instanceof Model ? $actor->getKey() : null,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'event' => $event,
            'ip_address' => config('sentinel.audit.capture_ip') ? $request?->ip() : null,
            'user_agent' => config('sentinel.audit.capture_user_agent') ? $request?->userAgent() : null,
            'before' => $before ?: null,
            'after' => $after ?: null,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
