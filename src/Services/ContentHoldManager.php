<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Events\ContentHeld;
use EloquentWorks\Sentinel\Models\ContentHold;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class ContentHoldManager
{
    public function __construct(private readonly AuditLogger $audit) {}
    public function hold(Model $reportable, Authenticatable $actor, string $reason, mixed $expiresAt = null, array $metadata = []): ContentHold
    {
        $model = config('sentinel.models.hold');
        $actorModel = $actor instanceof Model ? $actor : throw new \InvalidArgumentException('Actor must be an Eloquent model.');
        /** @var ContentHold $hold */
        $hold = $model::query()->create([
            'reportable_type' => $reportable->getMorphClass(), 'reportable_id' => $reportable->getKey(),
            'actor_type' => $actorModel->getMorphClass(), 'actor_id' => $actorModel->getKey(),
            'reason' => $reason, 'active' => true, 'expires_at' => $expiresAt, 'metadata' => $metadata ?: null,
        ]);
        $this->audit->log('content.held', $actor, $reportable, $hold);
        event(new ContentHeld($hold));
        return $hold;
    }
    public function release(ContentHold $hold, Authenticatable $actor): void
    {
        $hold->forceFill(['active' => false, 'released_at' => now()])->save();
        $this->audit->log('content.released', $actor, $hold->reportable, $hold);
    }
}
