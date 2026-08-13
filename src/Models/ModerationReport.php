<?php

declare(strict_types=1);

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

    protected string $sentinelTableKey = 'reports';
    protected $guarded = [];
    protected $casts = [
        'priority' => Priority::class,
        'status' => ReportStatus::class,
        'metadata' => 'array',
        'triaged_at' => 'immutable_datetime',
        'resolved_at' => 'immutable_datetime',
    ];

    public function reporter(): MorphTo { return $this->morphTo(); }
    public function reportable(): MorphTo { return $this->morphTo(); }
    public function subject(): MorphTo { return $this->morphTo(); }
    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(
            config('sentinel.models.case'),
            config('sentinel.tables.case_reports'),
            'report_id', 'case_id'
        )->withTimestamps();
    }
}
