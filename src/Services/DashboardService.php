<?php

namespace EloquentWorks\Sentinel\Services;

final class DashboardService
{
    /**
     * Return the current moderation workload metrics.
     *
     * @return array<string, int>
     */
    public function metrics(): array
    {
        // Get the models from the Sentinel configuration
        $reportModel = config('sentinel.models.report');
        $caseModel = config('sentinel.models.case');
        $actionModel = config('sentinel.models.action');
        $watchlistModel = config('sentinel.models.watchlist');

        // Return the metrics as an associative array
        return [
            'open_reports' => $reportModel::query()
                ->whereIn('status', ['new', 'triaged', 'in_review'])
                ->count(),
            'open_cases' => $caseModel::query()
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
            'overdue_cases' => $caseModel::query()
                ->whereNotIn('status', ['resolved', 'closed'])
                ->where('due_at', '<', now())
                ->count(),
            'actions_24h' => $actionModel::query()
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'active_watchlist' => $watchlistModel::query()
                ->where('active', true)
                ->count(),
            'critical_cases' => $caseModel::query()
                ->where('priority', 'critical')
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
        ];
    }
}
