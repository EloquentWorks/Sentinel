<?php

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Enums\WatchSeverity;
use EloquentWorks\Sentinel\Events\WatchlistAdded;
use EloquentWorks\Sentinel\Models\WatchlistEntry;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class WatchlistManager
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
     * Add a model to Sentinel's watchlist.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function add(
        Model $subject,
        Authenticatable $actor,
        string $reason,
        WatchSeverity|string $severity = WatchSeverity::Medium,
        mixed $expiresAt = null,
        array $metadata = [],
    ): WatchlistEntry {
        // Get the watchlist model class from the configuration.
        $watchlistModel = config('sentinel.models.watchlist');
        $actorModel = $actor instanceof Model
            ? $actor
            : throw new InvalidArgumentException('Actor must be an Eloquent model.');

        // Convert the severity to a WatchSeverity enum if it's a string.
        $severity = is_string($severity)
            ? WatchSeverity::from($severity)
            : $severity;

        /** @var WatchlistEntry $entry */
        $entry = $watchlistModel::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'added_by_type' => $actorModel->getMorphClass(),
            'added_by_id' => $actorModel->getKey(),
            'reason' => $reason,
            'severity' => $severity,
            'active' => true,
            'expires_at' => $expiresAt,
            'metadata' => $metadata ?: null,
        ]);

        // Log the watchlist addition event and fire the WatchlistAdded event.
        $this->audit->log('watchlist.added', $actor, $subject, $entry);
        event(new WatchlistAdded($entry));

        // Return the created WatchlistEntry instance.
        return $entry;
    }

    /**
     * Remove an entry from the active watchlist while retaining history.
     */
    public function remove(
        WatchlistEntry $entry,
        Authenticatable $actor,
    ): void {
        // Mark the watchlist entry as inactive.
        $entry->forceFill([
            'active' => false,
        ])->save();

        // Log the watchlist removal event.
        $this->audit->log(
            'watchlist.removed',
            $actor,
            $entry->subject,
            $entry,
        );
    }
}
