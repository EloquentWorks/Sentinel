<?php

namespace EloquentWorks\Sentinel\Models;

use EloquentWorks\Sentinel\Models\Concerns\UsesSentinelTable;
use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    use UsesSentinelTable;

    /**
     * The name of the "sentinel" table key for this model.
     */
    protected string $sentinelTableKey = 'rules';

    /**
     * The guarded attributes.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // Define the attribute casting for the model, including boolean, array, and immutable datetime types.
        return [
            'enabled' => 'boolean',
            'conditions' => 'array',
            'actions' => 'array',
            'stop_processing' => 'boolean',
            'last_triggered_at' => 'immutable_datetime',
        ];
    }
}
