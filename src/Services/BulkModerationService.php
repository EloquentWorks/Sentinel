<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class BulkModerationService
{
    public function __construct(private readonly EnforcementManager $enforcement) {}
    public function warn(iterable $targets, Authenticatable $actor, string $reason, string $severity = 'medium'): array { return $this->run($targets, fn (Model $t) => $this->enforcement->warn($t, $actor, $reason, $severity)); }
    public function strike(iterable $targets, Authenticatable $actor, string $reason, int $points = 1, string $category = 'other'): array { return $this->run($targets, fn (Model $t) => $this->enforcement->strike($t, $actor, $reason, $points, $category)); }
    public function ban(iterable $targets, Authenticatable $actor, string $reason, mixed $expiresAt = null, string $category = 'other'): array { return $this->run($targets, fn (Model $t) => $this->enforcement->ban($t, $actor, $reason, $expiresAt, $category)); }
    private function run(iterable $targets, callable $callback): array
    {
        $result = ['succeeded' => [], 'failed' => []];
        foreach ($targets as $target) {
            try { $result['succeeded'][] = $callback($target); }
            catch (Throwable $e) { $result['failed'][] = ['target' => $target, 'error' => $e->getMessage()]; }
        }
        return $result;
    }
}
