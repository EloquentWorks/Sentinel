<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationMacro extends Model
{
    use UsesSentinelTable;
    protected string $sentinelTableKey = 'macros';
    protected $guarded = [];
    protected $casts = ['enabled' => 'boolean', 'actions' => 'array', 'metadata' => 'array'];
    public function createdBy(): MorphTo { return $this->morphTo('created_by'); }
}
