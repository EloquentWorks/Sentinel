<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Services\DashboardService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): View
    {
        $case = config('sentinel.models.case'); $report = config('sentinel.models.report');
        return view('sentinel::dashboard', [
            'metrics' => $dashboard->metrics(),
            'cases' => $case::query()->latest('updated_at')->limit(10)->get(),
            'reports' => $report::query()->latest()->limit(10)->get(),
        ]);
    }
}
