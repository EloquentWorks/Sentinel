<?php

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Enums\Priority;
use EloquentWorks\Sentinel\Enums\ReportStatus;
use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationReport extends Model
{
    use UsesSentinelTable;

    /**
     * The name of the "sentinel" table associated with the model.
     *
     * @var string
     */
    protected string $sentinelTableKey = 'reports';

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
        // Cast the priority and status attributes to their respective enum classes,
        // and cast metadata to an array, and triaged_at and resolved_at to immutable datetime.
        return [
            'priority' => Priority::class,
            'status' => ReportStatus::class,
            'metadata' => 'array',
            'triaged_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the user or model that submitted the report.
     *
     * @return MorphTo
     */
    public function reporter(): MorphTo
    {
        // Get the user or model that submitted the report.
        return $this->morphTo();
    }

    /**
     * Get the content that was reported.
     *
     * @return MorphTo
     */
    public function reportable(): MorphTo
    {
        // Get the content that was reported.
        return $this->morphTo();
    }

    /**
     * Get the user or model accused by the report, if applicable.
     *
     * @return MorphTo
     */
    public function subject(): MorphTo
    {
        // Get the user or model accused by the report, if applicable.
        return $this->morphTo();
    }

    /**
     * Get moderation cases connected to this report.
     *
     * @return BelongsToMany
     */
    public function cases(): BelongsToMany
    {
        // Get moderation cases connected to this report.
        return $this->belongsToMany(
            config('sentinel.models.case'),
            config('sentinel.tables.case_reports'),
            'report_id',
            'case_id',
        )->withTimestamps();
    }
}
