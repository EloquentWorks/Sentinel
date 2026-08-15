<?php

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Models\ModerationCase;
use EloquentWorks\Sentinel\Services\EnforcementManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class EnforcementController extends Controller
{
    /**
     * Issue a warning through Exile.
     *
     * @param  Request  $request
     * @param  string  $user
     * @param  EnforcementManager  $enforcement
     * @return RedirectResponse
     */
    public function warn(
        Request $request,
        string $user,
        EnforcementManager $enforcement,
    ): RedirectResponse {
        // Validate the request data
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'severity' => ['nullable', 'in:low,medium,high'],
            'case_id' => ['nullable', 'integer'],
        ]);

        // Issue the warning through the EnforcementManager
        $enforcement->warn(
            target: $this->target($user),
            actor: $request->user(),
            reason: $validated['reason'],
            severity: $validated['severity'] ?? 'medium',
            case: $this->case($validated['case_id'] ?? null),
        );

        // Redirect back with a status message
        return back()->with('status', 'Warning issued.');
    }

    /**
     * Issue strike points through Exile.
     *
     * @param  Request  $request
     * @param  string  $user
     * @param  EnforcementManager  $enforcement
     * @return RedirectResponse
     */
    public function strike(
        Request $request,
        string $user,
        EnforcementManager $enforcement,
    ): RedirectResponse {
        // Validate the request data
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'case_id' => ['nullable', 'integer'],
        ]);

        // Issue the strike through the EnforcementManager
        $enforcement->strike(
            target: $this->target($user),
            actor: $request->user(),
            reason: $validated['reason'],
            points: (int) $validated['points'],
            category: $validated['category'] ?? 'other',
            case: $this->case($validated['case_id'] ?? null),
        );

        // Redirect back with a status message
        return back()->with('status', 'Strike issued.');
    }

    /**
     * Ban a user through Exile.
     *
     * @param  Request  $request
     * @param  string  $user
     * @param  EnforcementManager  $enforcement
     * @return RedirectResponse
     */
    public function ban(
        Request $request,
        string $user,
        EnforcementManager $enforcement,
    ): RedirectResponse {
        // Validate the request data
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'category' => ['nullable', 'string', 'max:100'],
            'case_id' => ['nullable', 'integer'],
        ]);

        // Determine the expiration date for the ban, if specified
        $expiresAt = isset($validated['days'])
            ? now()->addDays((int) $validated['days'])
            : null;

        // Issue the ban through the EnforcementManager
        $enforcement->ban(
            target: $this->target($user),
            actor: $request->user(),
            reason: $validated['reason'],
            expiresAt: $expiresAt,
            category: $validated['category'] ?? 'other',
            case: $this->case($validated['case_id'] ?? null),
        );

        // Redirect back with a status message
        return back()->with('status', 'Ban issued.');
    }

    /**
     * Apply a temporary or permanent account restriction through Exile.
     *
     * @param  Request  $request
     * @param  string  $user
     * @param  EnforcementManager  $enforcement
     * @return RedirectResponse
     */
    public function restrict(
        Request $request,
        string $user,
        EnforcementManager $enforcement,
    ): RedirectResponse {
        // Validate the request data
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'restriction' => ['required', 'in:posting,read_only,login,shadow'],
            'hours' => ['nullable', 'integer', 'min:1', 'max:87600'],
            'case_id' => ['nullable', 'integer'],
        ]);

        // Determine the expiration date for the restriction, if specified
        $expiresAt = isset($validated['hours'])
            ? now()->addHours((int) $validated['hours'])
            : null;

        // Apply the restriction through the EnforcementManager
        $enforcement->restrict(
            target: $this->target($user),
            actor: $request->user(),
            restriction: $validated['restriction'],
            reason: $validated['reason'],
            expiresAt: $expiresAt,
            case: $this->case($validated['case_id'] ?? null),
        );

        // Redirect back with a status message
        return back()->with('status', 'Restriction issued.');
    }

    /**
     * Start a support impersonation session through Masquerade.
     *
     * @param  Request  $request
     * @param  string  $user
     * @param  EnforcementManager  $enforcement
     * @return RedirectResponse
     */
    public function masquerade(
        Request $request,
        string $user,
        EnforcementManager $enforcement,
    ): RedirectResponse {
        // Validate the request data
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'case_id' => ['nullable', 'integer'],
        ]);

        // Start the masquerade session through the EnforcementManager
        $enforcement->masquerade(
            target: $this->target($user),
            actor: $request->user(),
            reason: $validated['reason'],
            case: $this->case($validated['case_id'] ?? null),
        );

        // Redirect to the home page or a specific route after starting the masquerade session
        return redirect('/');
    }

    /**
     * Resolve a user model from the host application's configured user model.
     *
     * @param  string|int  $id
     * @return Model
     */
    private function target(string|int $id): Model
    {
        // Resolve the user model class from the Sentinel configuration
        $userModel = config('sentinel.user_model');

        // Find the user by ID or throw a 404 error if not found
        return $userModel::query()->findOrFail($id);
    }

    /**
     * Resolve an optional moderation case submitted by the form.
     *
     * @param  int|string|null  $id
     * @return ModerationCase|null
     */
    private function case(int|string|null $id): ?ModerationCase
    {
        // If the case ID is null or empty, return null
        if ($id === null || $id === '') {
            return null;
        }

        // Resolve the moderation case model class from the Sentinel configuration
        $caseModel = config('sentinel.models.case');

        /** @var ModerationCase|null $case */
        $case = $caseModel::query()->find($id);

        // If the case is not found, return null
        return $case;
    }
}
