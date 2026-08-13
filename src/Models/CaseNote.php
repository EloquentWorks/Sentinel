<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CaseNote extends Model
{
    use UsesSentinelTable;
    protected string $sentinelTableKey = 'notes';
    protected $guarded = [];
    protected $casts = ['metadata' => 'array'];
    public function case(): BelongsTo { return $this->belongsTo(config('sentinel.models.case'), 'case_id'); }
    public function author(): MorphTo { return $this->morphTo(); }
}
