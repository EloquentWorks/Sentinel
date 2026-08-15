<?php

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Services\RiskScorer;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class UserModerationController extends Controller
{
    /**
     * Display reports, cases, actions, watchlist entries, and a risk score.
     */
    public function show(string $user, RiskScorer $risk): View
    {
        // Get the user model from the configuration and find the target user by ID or fail if not found.
        $userModel = config('sentinel.user_model');
        $target = $userModel::query()->findOrFail($user);

        // Get the configured models for reports, cases, actions, and watchlist entries.
        $reportModel = config('sentinel.models.report');
        $caseModel = config('sentinel.models.case');
        $actionModel = config('sentinel.models.action');
        $watchlistModel = config('sentinel.models.watchlist');

        // Return a view with the target user, risk score, recent reports, cases, actions, and active watchlist entries.
        return view('sentinel::users.show', [
            'target' => $target,
            'riskScore' => $risk->score($target),
            'reports' => $reportModel::query()
                ->whereMorphedTo('subject', $target)
                ->latest()
                ->limit(20)
                ->get(),
            'cases' => $caseModel::query()
                ->whereMorphedTo('subject', $target)
                ->latest()
                ->limit(20)
                ->get(),
            'actions' => $actionModel::query()
                ->whereMorphedTo('target', $target)
                ->latest()
                ->limit(30)
                ->get(),
            'watchlist' => $watchlistModel::query()
                ->whereMorphedTo('subject', $target)
                ->where('active', true)
                ->get(),
        ]);
    }
}
