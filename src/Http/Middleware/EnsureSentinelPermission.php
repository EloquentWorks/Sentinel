<?php

namespace EloquentWorks\Sentinel\Http\Middleware;

use Closure;
use EloquentWorks\Sentinel\Contracts\ModeratorAuthorizer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSentinelPermission
{
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(
        private readonly ModeratorAuthorizer $authorizer,
    ) {
        //
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        // Get the authenticated user from the request
        $user = $request->user();

        // Check if the user is authenticated and has the required ability
        abort_unless(
            $user && $this->authorizer->allows($user, $ability),
            403,
        );

        // If the user has the required ability, continue processing the request
        return $next($request);
    }
}
