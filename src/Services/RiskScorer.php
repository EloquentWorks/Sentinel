<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Enums\Priority;
use Illuminate\Database\Eloquent\Model;

final class RiskScorer
{
    public function score(Model $subject): int
    {
        $reportModel = config('sentinel.models.report');
        $caseModel = config('sentinel.models.case');
        $watchModel = config('sentinel.models.watchlist');
        $score = 0;

        $reports = $reportModel::query()->whereMorphedTo('subject', $subject)->whereIn('status', ['new','triaged','in_review'])->get();
        $score += $reports->count() * (int) config('sentinel.risk.report_weight', 5);
        $score += $reports->whereIn('priority', [Priority::High, Priority::Urgent, Priority::Critical])->count() * (int) config('sentinel.risk.high_priority_report_weight', 10);
        $score += $caseModel::query()->whereMorphedTo('subject', $subject)->whereNotIn('status', ['resolved','closed'])->count() * (int) config('sentinel.risk.open_case_weight', 15);

        if (method_exists($subject, 'activeStrikePoints')) {
            $score += (int) $subject->activeStrikePoints() * (int) config('sentinel.risk.strike_point_weight', 8);
        }

        foreach ($watchModel::query()->whereMorphedTo('subject', $subject)->where('active', true)->get() as $entry) {
            $score += (int) config('sentinel.risk.watchlist_weights.'.$entry->severity->value, 0);
        }

        return min((int) config('sentinel.risk.max', 100), max(0, $score));
    }
}
