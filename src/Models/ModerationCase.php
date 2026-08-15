<?php

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Enums\CaseStatus;
use EloquentWorks\Sentinel\Enums\Priority;
use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationCase extends Model
{
    use UsesSentinelTable;

    /**
     * The name of the "sentinel" table associated with the model.
     */
    protected string $sentinelTableKey = 'cases';

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
        // Define the attributes that should be cast to specific types.
        return [
            'status' => CaseStatus::class,
            'priority' => Priority::class,
            'tags' => 'array',
            'metadata' => 'array',
            'opened_at' => 'immutable_datetime',
            'due_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the user or model being investigated.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get reports attached to this case.
     */
    public function reports(): BelongsToMany
    {
        // Get the reports associated with this case using the configured report model and pivot table.
        return $this->belongsToMany(
            config('sentinel.models.report'),
            config('sentinel.tables.case_reports'),
            'case_id',
            'report_id',
        )->withTimestamps();
    }

    /**
     * Get notes attached to this case.
     */
    public function notes(): HasMany
    {
        // Get the notes associated with this case using the configured note model and foreign key.
        return $this->hasMany(
            config('sentinel.models.note'),
            'case_id',
        );
    }

    /**
     * Get moderator assignments for this case.
     */
    public function assignments(): HasMany
    {
        // Get the assignments associated with this case using the configured assignment model and foreign key.
        return $this->hasMany(
            config('sentinel.models.assignment'),
            'case_id',
        );
    }

    /**
     * Get enforcement actions associated with this case.
     */
    public function actions(): HasMany
    {
        // Get the actions associated with this case using the configured action model and foreign key.
        return $this->hasMany(
            config('sentinel.models.action'),
            'case_id',
        );
    }
}
