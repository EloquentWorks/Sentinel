<?php

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Events\AutomationTriggered;
use EloquentWorks\Sentinel\Models\AutomationRule;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

final class AutomationEngine
{
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(
        private readonly CaseManager $cases,
        private readonly WatchlistManager $watchlist,
        private readonly EnforcementManager $enforcement,
        private readonly AuditLogger $audit,
    ) {
        //
    }

    /**
     * Process all enabled rules matching the supplied event name.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, AutomationRule>
     */
    public function handle(string $event, array $context): array
    {
        // Check if automation is enabled in the configuration. If not, return an empty array.
        if (! config('sentinel.automation.enabled', true)) {
            return [];
        }

        // Get the rule model class from the configuration and query for enabled rules matching the event.
        $ruleModel = config('sentinel.models.rule');

        // Limit the number of rules processed to prevent performance issues.
        $rules = $ruleModel::query()
            ->where('enabled', true)
            ->where('event', $event)
            ->orderByDesc('priority')
            ->limit((int) config('sentinel.automation.max_rules_per_event', 50))
            ->get();

        // Initialize an array to hold the triggered rules.
        $triggered = [];

        // Loop through each rule and check if it matches the event context. If it does,
        // execute the actions and log the trigger.
        foreach ($rules as $rule) {

            // Check if the rule's conditions match the event context. If not, skip to the next rule.
            if (! $this->matches($rule->conditions ?? [], $context)) {
                continue;
            }

            // Execute the actions configured on the matching rule.
            $this->execute($rule, $context);

            // Update the rule's last triggered timestamp and increment the trigger count.
            $rule->forceFill([
                'last_triggered_at' => now(),
                'trigger_count' => ((int) $rule->trigger_count) + 1,
            ])->save();

            // Log the automation trigger event for auditing purposes.
            $this->audit->log(
                event: 'automation.triggered',
                auditable: $rule,
                metadata: ['event' => $event],
            );

            // Dispatch an event to notify other parts of the system that the automation rule has been triggered.
            event(new AutomationTriggered($rule));
            $triggered[] = $rule;

            // If the rule is configured to stop further processing, break out of the loop.
            if ($rule->stop_processing) {
                break;
            }
        }

        // Return the array of triggered rules.
        return $triggered;
    }

    /**
     * Determine whether every configured condition matches the event context.
     *
     * @param  array<int, array<string, mixed>>  $conditions
     * @param  array<string, mixed>  $context
     */
    private function matches(array $conditions, array $context): bool
    {
        // Loop through each condition and check if it matches the corresponding value in the event context.
        foreach ($conditions as $condition) {

            // Get the value from the context based on the condition's field and compare it to the expected value.
            $value = Arr::get($context, (string) ($condition['field'] ?? ''));
            $expected = $condition['value'] ?? null;

            // Use a match expression to determine if the condition is satisfied based
            // on the operator specified in the condition.
            $matches = match ($condition['operator'] ?? 'equals') {
                'equals' => $value == $expected,
                'not_equals' => $value != $expected,
                'gt' => $value > $expected,
                'gte' => $value >= $expected,
                'lt' => $value < $expected,
                'lte' => $value <= $expected,
                'in' => in_array($value, (array) $expected, true),
                'contains' => is_string($value)
                    && str_contains(
                        mb_strtolower($value),
                        mb_strtolower((string) $expected),
                    ),
                'present' => $value !== null,
                default => false,
            };

            // If any condition does not match, return false to indicate that the rule does not apply.
            if (! $matches) {
                return false;
            }
        }

        // If all conditions match, return true to indicate that the rule applies.
        return true;
    }

    /**
     * Execute the actions configured on a matching automation rule.
     *
     * @param  array<string, mixed>  $context
     */
    private function execute(AutomationRule $rule, array $context): void
    {
        // Get the subject and actor from the context, defaulting to null and the authenticated user, respectively.
        $subject = $context['subject'] ?? null;
        $actor = $context['actor'] ?? auth()->user();

        // Loop through each action configured on the rule and execute it based on its type.
        foreach ($rule->actions ?? [] as $action) {
            $type = $action['type'] ?? null;

            // If the action type is 'open_case' and the subject is a model, open a new moderation
            // case with the specified parameters.
            if ($type === 'open_case' && $subject instanceof Model) {
                $this->cases->open(
                    subject: $subject,
                    title: $action['title'] ?? 'Automated moderation case',
                    priority: $action['priority'] ?? 'normal',
                    openedBy: $actor instanceof Authenticatable ? $actor : null,
                    queue: $action['queue'] ?? null,
                    tags: $action['tags'] ?? [],
                );

                // If the action type is not recognized or the subject/actor are not valid, skip to the next action.
                continue;
            }

            // If the subject is not a model or the actor is not an authenticatable user, skip to the next action.
            if (! $subject instanceof Model || ! $actor instanceof Authenticatable) {
                continue;
            }

            // Execute the action based on its type.
            match ($type) {
                'watch' => $this->watchlist->add(
                    $subject,
                    $actor,
                    $action['reason'] ?? 'Automation rule',
                    $action['severity'] ?? 'medium',
                ),
                'warn' => $this->enforcement->warn(
                    $subject,
                    $actor,
                    $action['reason'] ?? 'Automated warning',
                    $action['severity'] ?? 'medium',
                ),
                'strike' => $this->enforcement->strike(
                    $subject,
                    $actor,
                    $action['reason'] ?? 'Automated strike',
                    (int) ($action['points'] ?? 1),
                    $action['category'] ?? 'other',
                ),
                'ban' => $this->enforcement->ban(
                    $subject,
                    $actor,
                    $action['reason'] ?? 'Automated ban',
                    null,
                    $action['category'] ?? 'other',
                ),
                default => null,
            };
        }
    }
}
