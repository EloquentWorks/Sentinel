<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationAuditLog extends Model
{
    use UsesSentinelTable;
    public $timestamps = false;
    protected string $sentinelTableKey = 'audit';
    protected $guarded = [];
    protected $casts = ['before' => 'array', 'after' => 'array', 'metadata' => 'array', 'created_at' => 'immutable_datetime'];
    public function actor(): MorphTo { return $this->morphTo(); }
    public function subject(): MorphTo { return $this->morphTo(); }
    public function auditable(): MorphTo { return $this->morphTo(); }
}
