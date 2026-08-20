<?php

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationAuditLog extends Model
{
    use UsesSentinelTable;

    /**
     * Audit logs only use a created_at timestamp.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the Sentinel table configuration key.
     */
    protected function sentinelTableKey(): string
    {
        return 'audit';
    }

    /**
     * The attributes that are not mass assignable.
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
        // Cast the before, after, and metadata attributes to arrays, and created_at to an immutable datetime.
        return [
            'before' => 'array',
            'after' => 'array',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the staff member who caused the audited event.
     */
    public function actor(): MorphTo
    {
        // Get the actor (staff member) who caused the audited event. This is a polymorphic
        // relationship, allowing for different types of actors (e.g., User, Admin).
        return $this->morphTo();
    }

    /**
     * Get the subject affected by the audited event.
     */
    public function subject(): MorphTo
    {
        // Get the subject affected by the audited event. This is a polymorphic
        // relationship, allowing for different types of subjects (e.g., User, Post).
        return $this->morphTo();
    }

    /**
     * Get the Sentinel or external model associated with the audit event.
     */
    public function auditable(): MorphTo
    {
        // Get the model associated with the audit event. This is a polymorphic
        // relationship, allowing for different types of models (e.g., User, Post).
        return $this->morphTo();
    }
}
