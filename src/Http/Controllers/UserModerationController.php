<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Services\RiskScorer;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class UserModerationController extends Controller
{
    public function show(string $user, RiskScorer $risk): View
    {
        $userModel = config('sentinel.user_model'); $target = $userModel::query()->findOrFail($user);
        $report = config('sentinel.models.report'); $case = config('sentinel.models.case'); $action = config('sentinel.models.action'); $watch = config('sentinel.models.watchlist');
        return view('sentinel::users.show', [
            'target' => $target,
            'riskScore' => $risk->score($target),
            'reports' => $report::query()->whereMorphedTo('subject',$target)->latest()->limit(20)->get(),
            'cases' => $case::query()->whereMorphedTo('subject',$target)->latest()->limit(20)->get(),
            'actions' => $action::query()->whereMorphedTo('target',$target)->latest()->limit(30)->get(),
            'watchlist' => $watch::query()->whereMorphedTo('subject',$target)->where('active',true)->get(),
        ]);
    }
}
