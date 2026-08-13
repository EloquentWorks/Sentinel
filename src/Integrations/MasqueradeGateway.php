<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Integrations;

use EloquentWorks\Masquerade\Facades\Masquerade;
use Illuminate\Contracts\Auth\Authenticatable;

final class MasqueradeGateway
{
    public function start(Authenticatable $target, Authenticatable $impersonator, string $reason, array $metadata = [], string $guard = 'web'): mixed
    {
        return Masquerade::start(target: $target, impersonator: $impersonator, guard: $guard, reason: $reason, metadata: $metadata);
    }

    public function stop(): mixed { return Masquerade::stop(); }
    public function active(): bool { return Masquerade::isMasquerading(); }
    public function context(): mixed { return Masquerade::context(); }
}
