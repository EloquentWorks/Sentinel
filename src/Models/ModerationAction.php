<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Enums\ActionStatus;
use EloquentWorks\Sentinel\Enums\ActionType;
use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationAction extends Model
{
    use UsesSentinelTable;
    protected string $sentinelTableKey = 'actions';
    protected $guarded = [];
    protected $casts = [
        'type' => ActionType::class,
        'status' => ActionStatus::class,
        'metadata' => 'array',
        'expires_at' => 'immutable_datetime',
        'applied_at' => 'immutable_datetime',
        'revoked_at' => 'immutable_datetime',
    ];
    public function case(): BelongsTo { return $this->belongsTo(config('sentinel.models.case'), 'case_id'); }
    public function actor(): MorphTo { return $this->morphTo(); }
    public function target(): MorphTo { return $this->morphTo(); }
    public function external(): MorphTo { return $this->morphTo(); }
}
