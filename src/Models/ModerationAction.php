<?php

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Enums\ActionStatus;
use EloquentWorks\Sentinel\Enums\ActionType;
use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationAction extends Model
{
    use UsesSentinelTable;

    /**
     * The name of the table associated with the model.
     */
    protected string $sentinelTableKey = 'actions';

    /**
     * The attributes that are guarded from mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // Define the casts for the model attributes.
        return [
            'type' => ActionType::class,
            'status' => ActionStatus::class,
            'metadata' => 'array',
            'expires_at' => 'immutable_datetime',
            'applied_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the moderation case associated with the action.
     */
    public function case(): BelongsTo
    {
        // Define the relationship to the moderation case model.
        return $this->belongsTo(
            config('sentinel.models.case'),
            'case_id',
        );
    }

    /**
     * Get the moderator who initiated the action.
     */
    public function actor(): MorphTo
    {
        // Define the polymorphic relationship to the actor (moderator) model.
        return $this->morphTo();
    }

    /**
     * Get the user or model targeted by the action.
     */
    public function target(): MorphTo
    {
        // Define the polymorphic relationship to the target model.
        return $this->morphTo();
    }

    /**
     * Get the external Exile or Masquerade model created by the action.
     */
    public function external(): MorphTo
    {
        // Define the polymorphic relationship to the external model (Exile or Masquerade).
        return $this->morphTo();
    }
}
