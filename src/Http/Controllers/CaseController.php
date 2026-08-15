<?php

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Enums\Priority;
use EloquentWorks\Sentinel\Models\ModerationCase;
use EloquentWorks\Sentinel\Services\CaseManager;
use EloquentWorks\Sentinel\Services\RiskScorer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CaseController extends Controller
{
    /**
     * Display a paginated list of moderation cases.
     */
    public function index(Request $request): View
    {
        // Get the moderation case model from the configuration and build a query to
        // retrieve cases, applying filters based on request parameters.
        $caseModel = config('sentinel.models.case');
        $query = $caseModel::query()->latest('updated_at');

        // Apply filters for status, queue, and priority if they are present in the request.
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // Apply filters for queue and priority if they are present in the request.
        if ($request->filled('queue')) {
            $query->where('queue', $request->string('queue'));
        }

        // Apply filters for priority if it is present in the request.
        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        // Return a view with the paginated list of moderation cases, including any applied filters.
        return view('sentinel::cases.index', [
            'cases' => $query->paginate(30)->withQueryString(),
        ]);
    }

    /**
     * Display a single moderation case with its related records.
     */
    public function show(ModerationCase $case, RiskScorer $risk): View
    {
        // Load the related records for the moderation case.
        $case->load([
            'subject',
            'reports',
            'notes.author',
            'assignments.moderator',
            'actions',
        ]);

        // Calculate the risk score for the case's subject if it exists, otherwise use the stored risk score.
        $riskScore = $case->subject
            ? $risk->score($case->subject)
            : (int) $case->risk_score;

        // Return a view with the moderation case and its risk score.
        return view('sentinel::cases.show', [
            'case' => $case,
            'riskScore' => $riskScore,
        ]);
    }

    /**
     * Add an internal note to a moderation case.
     */
    public function note(
        Request $request,
        ModerationCase $case,
        CaseManager $cases,
    ): RedirectResponse {
        // Validate the request input to ensure the note body is provided and meets the required criteria.
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        // Add the note to the moderation case using the CaseManager service, associating it with the current user.
        $cases->note($case, $request->user(), $validated['body']);

        // Return back to the previous page with a success status message indicating that the note was added.
        return back()->with('status', 'Note added.');
    }

    /**
     * Resolve a moderation case.
     */
    public function resolve(
        Request $request,
        ModerationCase $case,
        CaseManager $cases,
    ): RedirectResponse {
        // Validate the request input to ensure the resolution text is provided and meets the required criteria.
        $validated = $request->validate([
            'resolution' => ['required', 'string', 'max:5000'],
        ]);

        // Resolve the moderation case using the CaseManager service, associating it with the
        // current user and providing the resolution text.
        $cases->resolve($case, $request->user(), $validated['resolution']);

        // Return back to the previous page with a success status message indicating that the case was resolved.
        return back()->with('status', 'Case resolved.');
    }

    /**
     * Escalate a moderation case to a higher priority or queue.
     */
    public function escalate(
        Request $request,
        ModerationCase $case,
        CaseManager $cases,
    ): RedirectResponse {
        // Validate the request input to ensure the priority is provided and is one of the
        // allowed values, and that the queue is a valid string if provided.
        $validated = $request->validate([
            'priority' => ['required', 'in:high,urgent,critical'],
            'queue' => ['nullable', 'string', 'max:100'],
        ]);

        // Escalate the moderation case using the CaseManager service, associating it with the
        $cases->escalate(
            case: $case,
            actor: $request->user(),
            priority: Priority::from($validated['priority']),
            queue: $validated['queue'] ?? null,
        );

        // Return back to the previous page with a success status message indicating that the case was escalated.
        return back()->with('status', 'Case escalated.');
    }
}
