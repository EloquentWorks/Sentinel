<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    use UsesSentinelTable;
    protected string $sentinelTableKey = 'rules';
    protected $guarded = [];
    protected $casts = ['enabled' => 'boolean', 'conditions' => 'array', 'actions' => 'array', 'stop_processing' => 'boolean', 'last_triggered_at' => 'immutable_datetime'];
}
