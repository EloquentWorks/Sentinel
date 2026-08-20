<?php

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CaseAssignment extends Model
{
    use UsesSentinelTable;

    /**
     * Get the Sentinel table configuration key.
     */
    protected function sentinelTableKey(): string
    {
        return 'assignments';
    }

    /**
     * The guarded attributes.
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
        // Define the attributes that should be cast to specific types.
        return [
            'active' => 'boolean',
            'assigned_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the moderation case for this assignment.
     */
    public function case(): BelongsTo
    {
        // Define a belongs-to relationship with the Case model.
        return $this->belongsTo(
            config('sentinel.models.case'),
            'case_id',
        );
    }

    /**
     * Get the assigned moderator.
     */
    public function moderator(): MorphTo
    {
        // Define a polymorphic relationship to the assigned moderator.
        return $this->morphTo();
    }

    /**
     * Get the staff member who created the assignment.
     */
    public function assignedBy(): MorphTo
    {
        // Define a polymorphic relationship to the staff member who created the assignment.
        return $this->morphTo('assigned_by');
    }
}
