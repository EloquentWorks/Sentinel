<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Services;

final class DashboardService
{
    public function metrics(): array
    {
        $report = config('sentinel.models.report'); $case = config('sentinel.models.case'); $action = config('sentinel.models.action'); $watch = config('sentinel.models.watchlist');
        return [
            'open_reports' => $report::query()->whereIn('status', ['new','triaged','in_review'])->count(),
            'open_cases' => $case::query()->whereNotIn('status', ['resolved','closed'])->count(),
            'overdue_cases' => $case::query()->whereNotIn('status', ['resolved','closed'])->where('due_at', '<', now())->count(),
            'actions_24h' => $action::query()->where('created_at', '>=', now()->subDay())->count(),
            'active_watchlist' => $watch::query()->where('active', true)->count(),
            'critical_cases' => $case::query()->where('priority', 'critical')->whereNotIn('status', ['resolved','closed'])->count(),
        ];
    }
}
