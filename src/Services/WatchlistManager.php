<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Enums\WatchSeverity;
use EloquentWorks\Sentinel\Events\WatchlistAdded;
use EloquentWorks\Sentinel\Models\WatchlistEntry;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class WatchlistManager
{
    public function __construct(private readonly AuditLogger $audit) {}
    public function add(Model $subject, Authenticatable $actor, string $reason, WatchSeverity|string $severity = WatchSeverity::Medium, mixed $expiresAt = null, array $metadata = []): WatchlistEntry
    {
        $model = config('sentinel.models.watchlist');
        $actorModel = $actor instanceof Model ? $actor : throw new \InvalidArgumentException('Actor must be an Eloquent model.');
        $severity = is_string($severity) ? WatchSeverity::from($severity) : $severity;
        /** @var WatchlistEntry $entry */
        $entry = $model::query()->create([
            'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(),
            'added_by_type' => $actorModel->getMorphClass(), 'added_by_id' => $actorModel->getKey(),
            'reason' => $reason, 'severity' => $severity, 'active' => true, 'expires_at' => $expiresAt, 'metadata' => $metadata ?: null,
        ]);
        $this->audit->log('watchlist.added', $actor, $subject, $entry);
        event(new WatchlistAdded($entry));
        return $entry;
    }
    public function remove(WatchlistEntry $entry, Authenticatable $actor): void
    {
        $entry->forceFill(['active' => false])->save();
        $this->audit->log('watchlist.removed', $actor, $entry->subject, $entry);
    }
}
