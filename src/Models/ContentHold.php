<?php

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentHold extends Model
{
    use UsesSentinelTable;

    /**
     * The table associated with the model.
     */
    protected string $sentinelTableKey = 'holds';

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
        // Note: The 'immutable_datetime' cast is available in Laravel 10 and later.
        // If you're using an earlier version, you may need to use 'datetime' instead.
        return [
            'active' => 'boolean',
            'metadata' => 'array',
            'expires_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the content currently or previously held.
     */
    public function reportable(): MorphTo
    {
        // The `reportable` method defines a polymorphic relationship to the content
        // that is being held. This allows the `ContentHold` model to be associated with
        // any other model in the application, such as a `Post`, `Comment`, or any other
        // reportable entity. The `morphTo` method will automatically determine the related
        // model based on the `reportable_type` and `reportable_id` columns
        // in the `content_holds` table.
        return $this->morphTo();
    }

    /**
     * Get the moderator who placed the hold.
     */
    public function actor(): MorphTo
    {
        // The `actor` method defines a polymorphic relationship to the user or moderator
        return $this->morphTo();
    }
}
