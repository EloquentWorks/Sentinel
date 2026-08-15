<?php

namespace EloquentWorks\Sentinel\Facades;

use EloquentWorks\Sentinel\Sentinel as SentinelManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see SentinelManager
 */
final class Sentinel extends Facade
{
    /**
     * Get the registered component name.
     */
    protected static function getFacadeAccessor(): string
    {
        // Return the class name of the SentinelManager to be used as the facade accessor.
        return SentinelManager::class;
    }
}
