<?php

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CaseNote extends Model
{
    use UsesSentinelTable;

    /**
     * Get the Sentinel table configuration key.
     */
    protected function sentinelTableKey(): string
    {
        return 'notes';
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
        // Cast the `metadata` attribute to an array for easy access and manipulation.
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the moderation case that owns the note.
     */
    public function case(): BelongsTo
    {
        // Get the configured case model from the Sentinel configuration and define the relationship.
        return $this->belongsTo(
            config('sentinel.models.case'),
            'case_id',
        );
    }

    /**
     * Get the staff member who authored the note.
     */
    public function author(): MorphTo
    {
        // Define a polymorphic relationship to the author of the note, which can be any model (e.g., User, Admin).
        return $this->morphTo();
    }
}
