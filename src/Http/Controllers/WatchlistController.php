<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Enums\WatchSeverity;
use EloquentWorks\Sentinel\Services\WatchlistManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class WatchlistController extends Controller
{
    public function store(Request $request, string $user, WatchlistManager $watchlist): RedirectResponse
    {
        $d=$request->validate(['reason'=>['required','string','max:2000'],'severity'=>['required','in:low,medium,high,critical'],'days'=>['nullable','integer','min:1','max:3650']]);
        $model=config('sentinel.user_model'); $target=$model::query()->findOrFail($user);
        $watchlist->add($target,$request->user(),$d['reason'],WatchSeverity::from($d['severity']),isset($d['days'])?now()->addDays($d['days']):null);
        return back()->with('status','User added to watchlist.');
    }
}
