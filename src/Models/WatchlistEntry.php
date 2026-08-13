<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Enums\WatchSeverity;
use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WatchlistEntry extends Model
{
    use UsesSentinelTable;
    protected string $sentinelTableKey = 'watchlist';
    protected $guarded = [];
    protected $casts = ['severity' => WatchSeverity::class, 'active' => 'boolean', 'metadata' => 'array', 'expires_at' => 'immutable_datetime'];
    public function subject(): MorphTo { return $this->morphTo(); }
    public function addedBy(): MorphTo { return $this->morphTo('added_by'); }
}
