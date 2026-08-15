<?php

namespace EloquentWorks\Sentinel\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface ModeratorAuthorizer
{
    /**
     * Determine whether the user may perform the requested ability.
     */
    public function allows(
        Authenticatable $user,
        string $ability,
        mixed $subject = null,
    ): bool;
}
