<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentHold extends Model
{
    use UsesSentinelTable;
    protected string $sentinelTableKey = 'holds';
    protected $guarded = [];
    protected $casts = ['active' => 'boolean', 'metadata' => 'array', 'expires_at' => 'immutable_datetime', 'released_at' => 'immutable_datetime'];
    public function reportable(): MorphTo { return $this->morphTo(); }
    public function actor(): MorphTo { return $this->morphTo(); }
}
