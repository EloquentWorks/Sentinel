<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Http\Middleware;

use Closure;
use EloquentWorks\Sentinel\Integrations\MasqueradeGateway;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BlockEnforcementWhileMasquerading
{
    public function __construct(private readonly MasqueradeGateway $masquerade) {}
    public function handle(Request $request, Closure $next): Response
    {
        if (config('sentinel.integrations.masquerade.block_enforcement_while_masquerading', true) && $this->masquerade->active()) {
            abort(403, 'Sentinel enforcement actions are blocked while masquerading.');
        }
        return $next($request);
    }
}
