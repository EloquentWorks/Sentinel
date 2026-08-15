<?php

namespace EloquentWorks\Sentinel\Http\Middleware;

use Closure;
use EloquentWorks\Sentinel\Integrations\MasqueradeGateway;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BlockEnforcementWhileMasquerading
{
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(
        private readonly MasqueradeGateway $masquerade,
    ) {
        //
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if enforcement actions should be blocked while masquerading
        $shouldBlock = config(
            'sentinel.integrations.masquerade.block_enforcement_while_masquerading',
            true,
        );

        // If enforcement actions should be blocked and the user is currently
        // masquerading, abort the request with a 403 status code
        if ($shouldBlock && $this->masquerade->active()) {
            abort(403, 'Sentinel enforcement actions are blocked while masquerading.');
        }

        // If enforcement actions should not be blocked or the user is not
        // masquerading, continue processing the request
        return $next($request);
    }
}
