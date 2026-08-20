<?php

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationMacro extends Model
{
    use UsesSentinelTable;

    /**
     * Get the Sentinel table configuration key.
     */
    protected function sentinelTableKey(): string
    {
        return 'macros';
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
        // Define the attribute casting for the model, including custom enum casting for severity.
        return [
            'enabled' => 'boolean',
            'actions' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the staff member who created the macro.
     */
    public function createdBy(): MorphTo
    {
        // Define a polymorphic relationship to the user who created the macro.
        return $this->morphTo('created_by');
    }
}
