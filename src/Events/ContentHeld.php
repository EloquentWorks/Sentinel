<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Events;

use EloquentWorks\Sentinel\Models\ContentHold;

final readonly class ContentHeld
{
    public function __construct(public ContentHold $hold) {}
}
