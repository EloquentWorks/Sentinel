<?php

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Services\DashboardService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    /**
     * Display dashboard metrics and the most recently updated records.
     */
    public function __invoke(DashboardService $dashboard): View
    {
        // Get the models from the Sentinel configuration
        $caseModel = config('sentinel.models.case');
        $reportModel = config('sentinel.models.report');

        // Return the dashboard view with metrics and the latest cases and reports
        return view('sentinel::dashboard', [
            'metrics' => $dashboard->metrics(),
            'cases' => $caseModel::query()
                ->latest('updated_at')
                ->limit(10)
                ->get(),
            'reports' => $reportModel::query()
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
