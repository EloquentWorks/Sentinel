<?php

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Events\ContentHeld;
use EloquentWorks\Sentinel\Models\ContentHold;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class ContentHoldManager
{
    /**
     * Create a new class instance.
     *
     * @param  AuditLogger  $audit
     * @return void
     */
    public function __construct(
        private readonly AuditLogger $audit,
    ) {
        //
    }

    /**
     * Place a moderation hold on content.
     *
     * @param  Model  $reportable
     * @param  Authenticatable  $actor
     * @param  string  $reason
     * @param  mixed  $expiresAt
     * @param  array<string, mixed>  $metadata
     * @return ContentHold
     */
    public function hold(
        Model $reportable,
        Authenticatable $actor,
        string $reason,
        mixed $expiresAt = null,
        array $metadata = [],
    ): ContentHold {
        // Get the hold model class from the configuration.
        $holdModel = config('sentinel.models.hold');
        $actorModel = $actor instanceof Model
            ? $actor
            : throw new InvalidArgumentException('Actor must be an Eloquent model.');

        /** @var ContentHold $hold */
        $hold = $holdModel::query()->create([
            'reportable_type' => $reportable->getMorphClass(),
            'reportable_id' => $reportable->getKey(),
            'actor_type' => $actorModel->getMorphClass(),
            'actor_id' => $actorModel->getKey(),
            'reason' => $reason,
            'active' => true,
            'expires_at' => $expiresAt,
            'metadata' => $metadata ?: null,
        ]);

        // Log the content hold event and fire the ContentHeld event.
        $this->audit->log('content.held', $actor, $reportable, $hold);
        event(new ContentHeld($hold));

        // Return the created ContentHold instance.
        return $hold;
    }

    /**
     * Release an active content hold.
     *
     * @param  ContentHold  $hold
     * @param  Authenticatable  $actor
     * @return void
     */
    public function release(ContentHold $hold, Authenticatable $actor): void
    {
        // Update the hold to mark it as inactive and set the released_at timestamp.
        $hold->forceFill([
            'active' => false,
            'released_at' => now(),
        ])->save();

        // Log the content release event.
        $this->audit->log(
            'content.released',
            $actor,
            $hold->reportable,
            $hold,
        );
    }
}
