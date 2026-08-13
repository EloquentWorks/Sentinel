<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Models\ModerationCase;
use EloquentWorks\Sentinel\Services\CaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AssignmentController extends Controller
{
    public function store(Request $request, ModerationCase $case, CaseManager $cases): RedirectResponse
    {
        $data = $request->validate(['user_id' => ['required']]);
        $userModel = config('sentinel.user_model'); $moderator = $userModel::query()->findOrFail($data['user_id']);
        $cases->assign($case, $moderator, $request->user());
        return back()->with('status','Case assigned.');
    }
}
