<?php

declare(strict_types=1);

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

    protected string $sentinelTableKey = 'cases';
    protected $guarded = [];
    protected $casts = [
        'status' => CaseStatus::class,
        'priority' => Priority::class,
        'tags' => 'array',
        'metadata' => 'array',
        'opened_at' => 'immutable_datetime',
        'due_at' => 'immutable_datetime',
        'resolved_at' => 'immutable_datetime',
    ];

    public function subject(): MorphTo { return $this->morphTo(); }
    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(
            config('sentinel.models.report'),
            config('sentinel.tables.case_reports'),
            'case_id', 'report_id'
        )->withTimestamps();
    }
    public function notes(): HasMany { return $this->hasMany(config('sentinel.models.note'), 'case_id'); }
    public function assignments(): HasMany { return $this->hasMany(config('sentinel.models.assignment'), 'case_id'); }
    public function actions(): HasMany { return $this->hasMany(config('sentinel.models.action'), 'case_id'); }
}
