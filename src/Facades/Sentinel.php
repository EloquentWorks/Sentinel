<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Facades;

use Illuminate\Support\Facades\Facade;

final class Sentinel extends Facade
{
    protected static function getFacadeAccessor(): string { return \EloquentWorks\Sentinel\Sentinel::class; }
}
