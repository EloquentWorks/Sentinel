<?php

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Enums\Priority;
use Illuminate\Database\Eloquent\Model;

final class RiskScorer
{
    /**
     * Calculate the current risk score for the subject.
     *
     * @param  Model  $subject
     * @return int
     */
    public function score(Model $subject): int
    {
        // Get the model classes for reports, cases, and watchlists from the configuration.
        $reportModel = config('sentinel.models.report');
        $caseModel = config('sentinel.models.case');
        $watchlistModel = config('sentinel.models.watchlist');

        // Initialize the risk score to zero.
        $score = 0;

        // Calculate the risk score based on active reports associated with the subject.
        $activeReports = $reportModel::query()
            ->whereMorphedTo('subject', $subject)
            ->whereIn('status', ['new', 'triaged', 'in_review'])
            ->get();

        // Add the base weight for each active report to the score.
        $score += $activeReports->count()
            * (int) config('sentinel.risk.report_weight', 5);

        // Add additional weight for high-priority reports to the score.
        $score += $activeReports
            ->whereIn('priority', [
                Priority::High,
                Priority::Urgent,
                Priority::Critical,
            ])
            ->count()
            * (int) config('sentinel.risk.high_priority_report_weight', 10);

        // Calculate the risk score based on open cases associated with the subject.
        $score += $caseModel::query()
            ->whereMorphedTo('subject', $subject)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count()
            * (int) config('sentinel.risk.open_case_weight', 15);

        // Exile's Bannable trait may expose active strike points on the subject.
        if (method_exists($subject, 'activeStrikePoints')) {
            $score += (int) $subject->activeStrikePoints()
                * (int) config('sentinel.risk.strike_point_weight', 8);
        }

        // Calculate the risk score based on active watchlist entries associated with the subject.
        $watchlistEntries = $watchlistModel::query()
            ->whereMorphedTo('subject', $subject)
            ->where('active', true)
            ->get();

        // Add the weight for each watchlist entry based on its severity to the score.
        foreach ($watchlistEntries as $entry) {
            $score += (int) config(
                'sentinel.risk.watchlist_weights.'.$entry->severity->value,
                0,
            );
        }

        // Ensure the score is within the configured bounds and return it.
        return min(
            (int) config('sentinel.risk.max', 100),
            max(0, $score),
        );
    }
}
