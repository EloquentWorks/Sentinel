<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Enums\Priority;
use EloquentWorks\Sentinel\Models\ModerationReport;
use EloquentWorks\Sentinel\Services\CaseManager;
use EloquentWorks\Sentinel\Services\ReportManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $model = config('sentinel.models.report');
        $query = $model::query()->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('priority')) $query->where('priority', $request->string('priority'));
        if ($request->filled('category')) $query->where('category', $request->string('category'));
        return view('sentinel::reports.index', ['reports' => $query->paginate(30)->withQueryString()]);
    }
    public function show(ModerationReport $report): View { return view('sentinel::reports.show', ['report' => $report->load(['reporter','reportable','subject','cases'])]); }
    public function triage(Request $request, ModerationReport $report, ReportManager $reports): RedirectResponse
    {
        $data = $request->validate(['priority' => ['nullable','in:low,normal,high,urgent,critical']]);
        $reports->triage($report, $request->user(), isset($data['priority']) ? Priority::from($data['priority']) : null);
        return back()->with('status', 'Report triaged.');
    }
    public function dismiss(Request $request, ModerationReport $report, ReportManager $reports): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable','string','max:1000']]);
        $reports->dismiss($report, $request->user(), $data['reason'] ?? null);
        return back()->with('status', 'Report dismissed.');
    }
    public function openCase(Request $request, ModerationReport $report, CaseManager $cases): RedirectResponse
    {
        $case = $cases->fromReport($report, $request->user());
        return redirect()->route('sentinel.cases.show', $case)->with('status', 'Case opened.');
    }
}
