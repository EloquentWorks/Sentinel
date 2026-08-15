<?php

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Enums\WatchSeverity;
use EloquentWorks\Sentinel\Services\WatchlistManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class WatchlistController extends Controller
{
    /**
     * Add a user to the moderation watchlist.
     *
     * @param  Request  $request
     * @param  string  $user
     * @param  WatchlistManager  $watchlist
     * @return RedirectResponse
     */
    public function store(
        Request $request,
        string $user,
        WatchlistManager $watchlist,
    ): RedirectResponse {
        // Validate the request data
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        // Find the user model based on the provided user ID
        $userModel = config('sentinel.user_model');
        $target = $userModel::query()->findOrFail($user);

        // Determine the expiration date for the watchlist entry, if provided
        $expiresAt = isset($validated['days'])
            ? now()->addDays((int) $validated['days'])
            : null;
        
        // Add the user to the watchlist with the provided details
        $watchlist->add(
            subject: $target,
            actor: $request->user(),
            reason: $validated['reason'],
            severity: WatchSeverity::from($validated['severity']),
            expiresAt: $expiresAt,
        );
        
        // Redirect back with a success message
        return back()->with('status', 'User added to watchlist.');
    }
}
