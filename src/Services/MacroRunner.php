<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Models\ModerationCase;
use EloquentWorks\Sentinel\Models\ModerationMacro;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class MacroRunner
{
    public function __construct(private readonly EnforcementManager $enforcement, private readonly CaseManager $cases) {}
    public function run(ModerationMacro $macro, Model $target, Authenticatable $actor, ?ModerationCase $case = null): array
    {
        if (! $macro->enabled) throw new \LogicException('Moderation macro is disabled.');
        $results = [];
        foreach ($macro->actions ?? [] as $action) {
            $results[] = match ($action['type'] ?? null) {
                'warn' => $this->enforcement->warn($target, $actor, $action['reason'] ?? $macro->name, $action['severity'] ?? 'medium', $case),
                'strike' => $this->enforcement->strike($target, $actor, $action['reason'] ?? $macro->name, (int) ($action['points'] ?? 1), $action['category'] ?? 'other', $case),
                'ban' => $this->enforcement->ban($target, $actor, $action['reason'] ?? $macro->name, null, $action['category'] ?? 'other', $case),
                'restrict' => $this->enforcement->restrict($target, $actor, $action['restriction'] ?? 'posting', $action['reason'] ?? $macro->name, null, $case),
                'note' => $case ? $this->cases->note($case, $actor, $action['body'] ?? $macro->name) : null,
                default => null,
            };
        }
        return array_values(array_filter($results, fn ($v) => $v !== null));
    }
}
