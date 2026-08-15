<?php

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
    /**
     * Display a filtered, paginated report queue.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        // Get the report model from the configuration and start a query to retrieve reports, ordered by the latest.
        $reportModel = config('sentinel.models.report');
        $query = $reportModel::query()->latest();

        // Apply filters to the query based on the request parameters for status, priority, and category.
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // Apply a filter for priority if it is provided in the request, ensuring that
        // only reports with the specified priority are included in the results.
        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        // Paginate the results to show 30 reports per page and retain query string parameters for filtering.
        return view('sentinel::reports.index', [
            'reports' => $query->paginate(30)->withQueryString(),
        ]);
    }

    /**
     * Display a single report and its moderation context.
     *
     * @param  ModerationReport  $report
     * @return View
     */
    public function show(ModerationReport $report): View
    {
        // Load the report with its related models to avoid N+1 queries in the view.
        return view('sentinel::reports.show', [
            'report' => $report->load([
                'reporter',
                'reportable',
                'subject',
                'cases',
            ]),
        ]);
    }

    /**
     * Mark a new report as triaged.
     *
     * @param  Request  $request
     * @param  ModerationReport  $report
     * @param  ReportManager  $reports
     * @return RedirectResponse
     */
    public function triage(
        Request $request,
        ModerationReport $report,
        ReportManager $reports,
    ): RedirectResponse {
        // Validate the request data for triaging a report, ensuring that the priority is either null or one of the allowed values.
        $validated = $request->validate([
            'priority' => ['nullable', 'in:low,normal,high,urgent,critical'],
        ]);

        // Determine the priority from the validated data, converting it to a Priority enum instance if provided.
        $priority = isset($validated['priority'])
            ? Priority::from($validated['priority'])
            : null;

        // Triage the report using the ReportManager service, passing the current user and the determined priority.
        $reports->triage($report, $request->user(), $priority);

        // Redirect back to the previous page with a success message indicating that the report has been triaged.
        return back()->with('status', 'Report triaged.');
    }

    /**
     * Dismiss a report without opening a moderation case.
     *
     * @param  Request  $request
     * @param  ModerationReport  $report
     * @param  ReportManager  $reports
     * @return RedirectResponse
     */
    public function dismiss(
        Request $request,
        ModerationReport $report,
        ReportManager $reports,
    ): RedirectResponse {
        /// Validate the request data for dismissing a report.
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        // Dismiss the report using the ReportManager service, passing the current user and an optional reason.
        $reports->dismiss(
            $report,
            $request->user(),
            $validated['reason'] ?? null,
        );

        // Redirect back to the previous page with a success message indicating that the report has been dismissed.
        return back()->with('status', 'Report dismissed.');
    }

    /**
     * Open a moderation case from the report.
     *
     * @param  Request  $request
     * @param  ModerationReport  $report
     * @param  CaseManager  $cases
     * @return RedirectResponse
     */
    public function openCase(
        Request $request,
        ModerationReport $report,
        CaseManager $cases,
    ): RedirectResponse {
        // Validate the request data for opening a case.
        $case = $cases->fromReport($report, $request->user());

        // Redirect to the newly created case's page with a success message.
        return redirect()
            ->route('sentinel.cases.show', $case)
            ->with('status', 'Case opened.');
    }
}
