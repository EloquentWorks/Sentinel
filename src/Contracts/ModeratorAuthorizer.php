<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface ModeratorAuthorizer
{
    public function allows(Authenticatable $user, string $ability, mixed $subject = null): bool;
}
