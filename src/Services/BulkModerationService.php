<?php

namespace EloquentWorks\Sentinel\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class BulkModerationService
{
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(
        private readonly EnforcementManager $enforcement,
    ) {
        //
    }

    /**
     * Warn each supplied target.
     *
     * @param  iterable<Model>  $targets
     * @return array{succeeded: array<int, mixed>, failed: array<int, array<string, mixed>>}
     */
    public function warn(
        iterable $targets,
        Authenticatable $actor,
        string $reason,
        string $severity = 'medium',
    ): array {
        return $this->run(
            $targets,
            fn (Model $target) => $this->enforcement->warn(
                $target,
                $actor,
                $reason,
                $severity,
            ),
        );
    }

    /**
     * Strike each supplied target.
     *
     * @param  iterable<Model>  $targets
     * @return array{succeeded: array<int, mixed>, failed: array<int, array<string, mixed>>}
     */
    public function strike(
        iterable $targets,
        Authenticatable $actor,
        string $reason,
        int $points = 1,
        string $category = 'other',
    ): array {
        // We use a callback to avoid aborting the batch if one of the targets fails to be moderated.
        return $this->run(
            $targets,
            fn (Model $target) => $this->enforcement->strike(
                $target,
                $actor,
                $reason,
                $points,
                $category,
            ),
        );
    }

    /**
     * Ban each supplied target.
     *
     * @param  iterable<Model>  $targets
     * @return array{succeeded: array<int, mixed>, failed: array<int, array<string, mixed>>}
     */
    public function ban(
        iterable $targets,
        Authenticatable $actor,
        string $reason,
        mixed $expiresAt = null,
        string $category = 'other',
    ): array {
        // We use a callback to avoid aborting the batch if one of the targets fails to be moderated.
        return $this->run(
            $targets,
            fn (Model $target) => $this->enforcement->ban(
                $target,
                $actor,
                $reason,
                $expiresAt,
                $category,
            ),
        );
    }

    /**
     * Run a moderation callback against every target without aborting the batch.
     *
     * @param  iterable<Model>  $targets
     * @param  callable(Model): mixed  $callback
     * @return array{succeeded: array<int, mixed>, failed: array<int, array<string, mixed>>}
     */
    private function run(iterable $targets, callable $callback): array
    {
        // Initialize the result array with succeeded and failed keys.
        $result = [
            'succeeded' => [],
            'failed' => [],
        ];

        // Iterate over each target and attempt to execute the callback.
        foreach ($targets as $target) {
            try {
                $result['succeeded'][] = $callback($target);
            } catch (Throwable $exception) {
                $result['failed'][] = [
                    'target' => $target,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        // Return the result array containing succeeded and failed targets.
        return $result;
    }
}
