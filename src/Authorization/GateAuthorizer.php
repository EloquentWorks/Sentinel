<?php

namespace EloquentWorks\Sentinel\Authorization;

use EloquentWorks\Sentinel\Contracts\ModeratorAuthorizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class GateAuthorizer implements ModeratorAuthorizer
{
    /**
     * Determine whether the given user may perform a moderation ability.
     */
    public function allows(
        Authenticatable $user,
        string $ability,
        mixed $subject = null,
    ): bool {
        // If the subject is null, we pass an empty array to the Gate::forUser method to avoid passing null as a parameter.
        return Gate::forUser($user)->allows(
            $ability,
            $subject === null ? [] : [$subject],
        );
    }
}
