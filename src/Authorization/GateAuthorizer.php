<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Authorization;

use EloquentWorks\Sentinel\Contracts\ModeratorAuthorizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class GateAuthorizer implements ModeratorAuthorizer
{
    public function allows(Authenticatable $user, string $ability, mixed $subject = null): bool
    {
        return Gate::forUser($user)->allows($ability, $subject === null ? [] : [$subject]);
    }
}
