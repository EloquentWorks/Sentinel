<?php

namespace EloquentWorks\Sentinel\Integrations;

use EloquentWorks\Masquerade\Facades\Masquerade;
use Illuminate\Contracts\Auth\Authenticatable;

final class MasqueradeGateway
{
    /**
     * Start a masquerade session for a moderator.
     *
     * @param  Authenticatable  $target  The user to be impersonated.
     * @param  Authenticatable  $impersonator  The moderator initiating the impersonation.
     * @param  string  $reason  The reason for the impersonation.
     * @param  array  $metadata  Additional metadata for the impersonation session.
     * @param  string  $guard  The authentication guard to use for the impersonation session.
     * @return mixed The result of starting the masquerade session.
     */
    public function start(
        Authenticatable $target,
        Authenticatable $impersonator,
        string $reason,
        array $metadata = [],
        string $guard = 'web',
    ): mixed {
        // Call the start method on the Masquerade facade with the provided parameters.
        return Masquerade::start(
            target: $target,
            impersonator: $impersonator,
            guard: $guard,
            reason: $reason,
            metadata: $metadata,
        );
    }

    /**
     * Stop the active masquerade session.
     *
     * @return mixed The result of stopping the masquerade session.
     */
    public function stop(): mixed
    {
        return Masquerade::stop();
    }

    /**
     * Determine whether a masquerade session is active.
     *
     * @return bool True if a masquerade session is active, false otherwise.
     */
    public function active(): bool
    {
        return Masquerade::isMasquerading();
    }

    /**
     * Return the current Masquerade session context.
     *
     * @return mixed The current Masquerade session context.
     */
    public function context(): mixed
    {
        return Masquerade::context();
    }
}
