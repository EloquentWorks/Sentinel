<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Http\Middleware;

use Closure;
use EloquentWorks\Sentinel\Contracts\ModeratorAuthorizer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSentinelPermission
{
    public function __construct(private readonly ModeratorAuthorizer $authorizer) {}
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();
        abort_unless($user && $this->authorizer->allows($user, $ability), 403);
        return $next($request);
    }
}
