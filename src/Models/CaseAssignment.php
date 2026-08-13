<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CaseAssignment extends Model
{
    use UsesSentinelTable;
    protected string $sentinelTableKey = 'assignments';
    protected $guarded = [];
    protected $casts = ['active' => 'boolean', 'assigned_at' => 'immutable_datetime', 'released_at' => 'immutable_datetime'];
    public function case(): BelongsTo { return $this->belongsTo(config('sentinel.models.case'), 'case_id'); }
    public function moderator(): MorphTo { return $this->morphTo(); }
    public function assignedBy(): MorphTo { return $this->morphTo('assigned_by'); }
}
