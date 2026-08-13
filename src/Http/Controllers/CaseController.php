<?php

declare(strict_types=1);

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
    public function index(Request $request): View
    {
        $model = config('sentinel.models.case'); $query = $model::query()->latest('updated_at');
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('queue')) $query->where('queue', $request->string('queue'));
        if ($request->filled('priority')) $query->where('priority', $request->string('priority'));
        return view('sentinel::cases.index', ['cases' => $query->paginate(30)->withQueryString()]);
    }
    public function show(ModerationCase $case, RiskScorer $risk): View
    {
        $case->load(['subject','reports','notes.author','assignments.moderator','actions']);
        $score = $case->subject ? $risk->score($case->subject) : $case->risk_score;
        return view('sentinel::cases.show', ['case' => $case, 'riskScore' => $score]);
    }
    public function note(Request $request, ModerationCase $case, CaseManager $cases): RedirectResponse
    {
        $data = $request->validate(['body' => ['required','string','max:5000']]); $cases->note($case, $request->user(), $data['body']); return back()->with('status','Note added.');
    }
    public function resolve(Request $request, ModerationCase $case, CaseManager $cases): RedirectResponse
    {
        $data = $request->validate(['resolution' => ['required','string','max:5000']]); $cases->resolve($case, $request->user(), $data['resolution']); return back()->with('status','Case resolved.');
    }
    public function escalate(Request $request, ModerationCase $case, CaseManager $cases): RedirectResponse
    {
        $data = $request->validate(['priority' => ['required','in:high,urgent,critical'], 'queue' => ['nullable','string','max:100']]); $cases->escalate($case, $request->user(), Priority::from($data['priority']), $data['queue'] ?? null); return back()->with('status','Case escalated.');
    }
}
