<?php

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Models\ModerationCase;
use EloquentWorks\Sentinel\Models\ModerationMacro;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class MacroRunner
{
    /**
     * Create a new class instance.
     *
     * @param  EnforcementManager  $enforcement
     * @param  CaseManager  $cases
     * @return void
     */
    public function __construct(
        private readonly EnforcementManager $enforcement,
        private readonly CaseManager $cases,
    ) {
        //
    }

    /**
     * Execute every configured action in a moderation macro.
     *
     * @param  ModerationMacro  $macro
     * @param  Model  $target
     * @param  Authenticatable  $actor
     * @param  ModerationCase|null  $case
     * @return array<int, mixed>
     */
    public function run(
        ModerationMacro $macro,
        Model $target,
        Authenticatable $actor,
        ?ModerationCase $case = null,
    ): array {
        // Check if the macro is enabled before executing its actions.
        if (! $macro->enabled) {
            throw new LogicException('Moderation macro is disabled.');
        }

        // Initialize an array to hold the results of each action.
        $results = [];

        // Loop through each action defined in the macro and execute it based on its type.
        foreach ($macro->actions ?? [] as $action) {
            $result = match ($action['type'] ?? null) {
                'warn' => $this->enforcement->warn(
                    $target,
                    $actor,
                    $action['reason'] ?? $macro->name,
                    $action['severity'] ?? 'medium',
                    $case,
                ),
                'strike' => $this->enforcement->strike(
                    $target,
                    $actor,
                    $action['reason'] ?? $macro->name,
                    (int) ($action['points'] ?? 1),
                    $action['category'] ?? 'other',
                    $case,
                ),
                'ban' => $this->enforcement->ban(
                    $target,
                    $actor,
                    $action['reason'] ?? $macro->name,
                    null,
                    $action['category'] ?? 'other',
                    $case,
                ),
                'restrict' => $this->enforcement->restrict(
                    $target,
                    $actor,
                    $action['restriction'] ?? 'posting',
                    $action['reason'] ?? $macro->name,
                    null,
                    $case,
                ),
                'note' => $case
                    ? $this->cases->note(
                        $case,
                        $actor,
                        $action['body'] ?? $macro->name,
                    )
                    : null,
                default => null,
            };

            if ($result !== null) {
                // Add the result of the action to the results array.
                $results[] = $result;
            }
        }

        // Return the array of results from executing the macro's actions.
        return $results;
    }
}
