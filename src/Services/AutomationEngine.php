<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Events\AutomationTriggered;
use EloquentWorks\Sentinel\Models\AutomationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

final class AutomationEngine
{
    public function __construct(
        private readonly CaseManager $cases,
        private readonly WatchlistManager $watchlist,
        private readonly EnforcementManager $enforcement,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(string $event, array $context): array
    {
        if (! config('sentinel.automation.enabled', true)) return [];
        $model = config('sentinel.models.rule');
        $rules = $model::query()->where('enabled', true)->where('event', $event)->orderByDesc('priority')->limit((int) config('sentinel.automation.max_rules_per_event', 50))->get();
        $triggered = [];
        foreach ($rules as $rule) {
            if (! $this->matches($rule->conditions ?? [], $context)) continue;
            $this->execute($rule, $context);
            $rule->forceFill(['last_triggered_at' => now(), 'trigger_count' => ((int) $rule->trigger_count) + 1])->save();
            $this->audit->log('automation.triggered', null, null, $rule, metadata: ['event' => $event]);
            event(new AutomationTriggered($rule));
            $triggered[] = $rule;
            if ($rule->stop_processing) break;
        }
        return $triggered;
    }

    private function matches(array $conditions, array $context): bool
    {
        foreach ($conditions as $condition) {
            $value = Arr::get($context, (string) ($condition['field'] ?? ''));
            $expected = $condition['value'] ?? null;
            $ok = match ($condition['operator'] ?? 'equals') {
                'equals' => $value == $expected,
                'not_equals' => $value != $expected,
                'gt' => $value > $expected,
                'gte' => $value >= $expected,
                'lt' => $value < $expected,
                'lte' => $value <= $expected,
                'in' => in_array($value, (array) $expected, true),
                'contains' => is_string($value) && str_contains(mb_strtolower($value), mb_strtolower((string) $expected)),
                'present' => $value !== null,
                default => false,
            };
            if (! $ok) return false;
        }
        return true;
    }

    private function execute(AutomationRule $rule, array $context): void
    {
        foreach ($rule->actions ?? [] as $action) {
            $type = $action['type'] ?? null;
            $subject = $context['subject'] ?? null;
            $actor = $context['actor'] ?? auth()->user();
            if ($type === 'open_case' && $subject instanceof Model) {
                $this->cases->open($subject, $action['title'] ?? 'Automated moderation case', $action['priority'] ?? 'normal', $actor, $action['queue'] ?? null, $action['tags'] ?? []);
            } elseif ($type === 'watch' && $subject instanceof Model && $actor) {
                $this->watchlist->add($subject, $actor, $action['reason'] ?? 'Automation rule', $action['severity'] ?? 'medium');
            } elseif ($type === 'warn' && $subject instanceof Model && $actor) {
                $this->enforcement->warn($subject, $actor, $action['reason'] ?? 'Automated warning', $action['severity'] ?? 'medium');
            } elseif ($type === 'strike' && $subject instanceof Model && $actor) {
                $this->enforcement->strike($subject, $actor, $action['reason'] ?? 'Automated strike', (int) ($action['points'] ?? 1), $action['category'] ?? 'other');
            } elseif ($type === 'ban' && $subject instanceof Model && $actor) {
                $this->enforcement->ban($subject, $actor, $action['reason'] ?? 'Automated ban', null, $action['category'] ?? 'other');
            }
        }
    }
}
