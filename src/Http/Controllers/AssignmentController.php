<?php

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Models\ModerationCase;
use EloquentWorks\Sentinel\Services\CaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AssignmentController extends Controller
{
    /**
     * Assign a moderator to the given moderation case.
     *
     * @param  Request  $request
     * @param  ModerationCase  $case
     * @param  CaseManager  $cases
     * @return RedirectResponse
     */
    public function store(
        Request $request,
        ModerationCase $case,
        CaseManager $cases,
    ): RedirectResponse {
        // Validate the request data
        $validated = $request->validate([
            'user_id' => ['required'],
        ]);

        // Assign the moderator to the case
        $userModel = config('sentinel.user_model');
        $moderator = $userModel::query()->findOrFail($validated['user_id']);

        // Assign the moderator to the case
        $cases->assign(
            case: $case,
            moderator: $moderator,
            assignedBy: $request->user(),
        );

        // Return back with a success message
        return back()->with('status', 'Case assigned.');
    }
}
