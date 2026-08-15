<?php

namespace EloquentWorks\Sentinel\Services;

use DateTimeInterface;
use EloquentWorks\Sentinel\Enums\ActionStatus;
use EloquentWorks\Sentinel\Enums\ActionType;
use EloquentWorks\Sentinel\Events\ModerationActionApplied;
use EloquentWorks\Sentinel\Integrations\ExileGateway;
use EloquentWorks\Sentinel\Integrations\MasqueradeGateway;
use EloquentWorks\Sentinel\Models\ModerationAction;
use EloquentWorks\Sentinel\Models\ModerationCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class EnforcementManager
{
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(
        private readonly ExileGateway $exile,
        private readonly MasqueradeGateway $masquerade,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Issue a warning through Exile.
     */
    public function warn(
        Model $target,
        Authenticatable $actor,
        string $reason,
        string $severity = 'medium',
        ?ModerationCase $case = null,
    ): ModerationAction {
        // Issue a warning through Exile and record the action in Sentinel.
        return $this->apply(
            type: ActionType::Warn,
            target: $target,
            actor: $actor,
            reason: $reason,
            case: $case,
            callback: fn () => $this->exile->warn(
                $target,
                $reason,
                $severity,
                $actor,
            ),
            metadata: ['severity' => $severity],
        );
    }

    /**
     * Issue strike points through Exile.
     */
    public function strike(
        Model $target,
        Authenticatable $actor,
        string $reason,
        int $points = 1,
        string $category = 'other',
        ?ModerationCase $case = null,
    ): ModerationAction {
        // Issue a strike through Exile and record the action in Sentinel.
        return $this->apply(
            type: ActionType::Strike,
            target: $target,
            actor: $actor,
            reason: $reason,
            case: $case,
            callback: fn () => $this->exile->strike(
                $target,
                $reason,
                $points,
                $category,
                $actor,
            ),
            metadata: [
                'points' => $points,
                'category' => $category,
            ],
        );
    }

    /**
     * Ban a user or other bannable model through Exile.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function ban(
        Model $target,
        Authenticatable $actor,
        string $reason,
        mixed $expiresAt = null,
        string $category = 'other',
        ?ModerationCase $case = null,
        array $metadata = [],
    ): ModerationAction {
        // Prepare metadata for the ban action, including category and expiration date.
        $actionMetadata = [
            'category' => $category,
            'expires_at' => $this->formatDate($expiresAt),
            ...$metadata,
        ];

        // Apply the ban through Exile and record the action in Sentinel.
        return $this->apply(
            type: ActionType::Ban,
            target: $target,
            actor: $actor,
            reason: $reason,
            case: $case,
            callback: fn () => $this->exile->ban(
                target: $target,
                reason: $reason,
                expiresAt: $expiresAt,
                moderator: $actor,
                category: $category,
                metadata: $metadata,
            ),
            metadata: $actionMetadata,
            expiresAt: $expiresAt,
        );
    }

    /**
     * Apply an account restriction through Exile.
     */
    public function restrict(
        Model $target,
        Authenticatable $actor,
        string $restriction,
        string $reason,
        mixed $expiresAt = null,
        ?ModerationCase $case = null,
    ): ModerationAction {
        // Map the restriction string to the corresponding ActionType enum value.
        $type = match ($restriction) {
            'posting' => ActionType::RestrictPosting,
            'read_only' => ActionType::RestrictReadOnly,
            'login' => ActionType::RestrictLogin,
            'shadow' => ActionType::RestrictShadow,
            default => throw new InvalidArgumentException(
                "Unknown restriction [{$restriction}]."
            ),
        };

        // Apply the restriction through Exile and record the action in Sentinel.
        return $this->apply(
            type: $type,
            target: $target,
            actor: $actor,
            reason: $reason,
            case: $case,
            callback: fn () => $this->exile->restrict(
                $target,
                $restriction,
                $reason,
                $expiresAt,
                $actor,
            ),
            metadata: ['restriction' => $restriction],
            expiresAt: $expiresAt,
        );
    }

    /**
     * Ban an IP address through Exile.
     */
    public function banIp(
        string $ip,
        Authenticatable $actor,
        string $reason,
        mixed $expiresAt = null,
        ?ModerationCase $case = null,
    ): ModerationAction {
        // Apply the IP ban through Exile and record the action in Sentinel.
        return $this->applyExternal(
            type: ActionType::BanIp,
            actor: $actor,
            reason: $reason,
            case: $case,
            callback: fn () => $this->exile->banIp(
                $ip,
                $reason,
                $expiresAt,
                $actor,
            ),
            metadata: ['ip_address' => $ip],
            expiresAt: $expiresAt,
        );
    }

    /**
     * Ban a CIDR network range through Exile.
     */
    public function banNetwork(
        string $cidr,
        Authenticatable $actor,
        string $reason,
        mixed $expiresAt = null,
        ?ModerationCase $case = null,
    ): ModerationAction {
        // Apply the network ban through Exile and record the action in Sentinel.
        return $this->applyExternal(
            type: ActionType::BanNetwork,
            actor: $actor,
            reason: $reason,
            case: $case,
            callback: fn () => $this->exile->banNetwork(
                $cidr,
                $reason,
                $expiresAt,
                $actor,
            ),
            metadata: ['network' => $cidr],
            expiresAt: $expiresAt,
        );
    }

    /**
     * Ban a device fingerprint through Exile.
     */
    public function banDevice(
        string $fingerprint,
        Authenticatable $actor,
        string $reason,
        mixed $expiresAt = null,
        ?ModerationCase $case = null,
    ): ModerationAction {
        // Apply the device ban through Exile and record the action in Sentinel.
        return $this->applyExternal(
            type: ActionType::BanDevice,
            actor: $actor,
            reason: $reason,
            case: $case,
            callback: fn () => $this->exile->banDevice(
                $fingerprint,
                $reason,
                $expiresAt,
                $actor,
            ),
            metadata: [
                // Store only a hash in Sentinel's local audit metadata.
                'fingerprint_hash' => hash('sha256', $fingerprint),
            ],
            expiresAt: $expiresAt,
        );
    }

    /**
     * Start a user impersonation session through Masquerade.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function masquerade(
        Authenticatable $target,
        Authenticatable $actor,
        string $reason,
        ?ModerationCase $case = null,
        array $metadata = [],
    ): ModerationAction {
        // Ensure the target is an Eloquent model, as required by the Masquerade gateway.
        $targetModel = $target instanceof Model
            ? $target
            : throw new InvalidArgumentException(
                'Masquerade target must be an Eloquent model.'
            );

        // Prepare metadata for the masquerade action, including the associated case UUID if available.
        $masqueradeMetadata = [
            'sentinel_case_uuid' => $case?->uuid,
            ...$metadata,
        ];

        // Apply the masquerade through Masquerade and record the action in Sentinel.
        return $this->apply(
            type: ActionType::Masquerade,
            target: $targetModel,
            actor: $actor,
            reason: $reason,
            case: $case,
            callback: fn () => $this->masquerade->start(
                $target,
                $actor,
                $reason,
                $masqueradeMetadata,
            ),
            metadata: $masqueradeMetadata,
        );
    }

    /**
     * Execute an action associated with an Eloquent target.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function apply(
        ActionType $type,
        Model $target,
        Authenticatable $actor,
        string $reason,
        ?ModerationCase $case,
        callable $callback,
        array $metadata = [],
        mixed $expiresAt = null,
    ): ModerationAction {
        // Record the action and execute the provided callback, handling success and failure cases.
        return $this->recordAndExecute(
            $type,
            $target,
            $actor,
            $reason,
            $case,
            $callback,
            $metadata,
            $expiresAt,
        );
    }

    /**
     * Execute an action that does not have an Eloquent target, such as an IP ban.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function applyExternal(
        ActionType $type,
        Authenticatable $actor,
        string $reason,
        ?ModerationCase $case,
        callable $callback,
        array $metadata = [],
        mixed $expiresAt = null,
    ): ModerationAction {
        // Record the action and execute the provided callback, handling success and failure
        // cases for actions without a specific Eloquent target.
        return $this->recordAndExecute(
            $type,
            null,
            $actor,
            $reason,
            $case,
            $callback,
            $metadata,
            $expiresAt,
        );
    }

    /**
     * Create an action record, run the external action, and persist the result.
     *
     * @param  array<string, mixed>  $metadata
     * @param  mixed|null  $expiresAt
     */
    private function recordAndExecute(
        ActionType $type,
        ?Model $target,
        Authenticatable $actor,
        string $reason,
        ?ModerationCase $case,
        callable $callback,
        array $metadata,
        mixed $expiresAt,
    ): ModerationAction {
        // Get the action model class from the configuration and ensure the actor is an Eloquent model.
        $actionModel = config('sentinel.models.action');
        $actorModel = $actor instanceof Model
            ? $actor
            : throw new InvalidArgumentException('Actor must be an Eloquent model.');

        /** @var ModerationAction $action */
        $action = $actionModel::query()->create([
            'uuid' => (string) Str::uuid(),
            'case_id' => $case?->getKey(),
            'actor_type' => $actorModel->getMorphClass(),
            'actor_id' => $actorModel->getKey(),
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'type' => $type,
            'status' => ActionStatus::Pending,
            'reason' => $reason,
            'source_package' => $type === ActionType::Masquerade
                ? 'masquerade'
                : 'exile',
            'expires_at' => $expiresAt,
            'metadata' => $metadata ?: null,
        ]);

        // Attempt to execute the external action and update the action record based on the outcome.
        try {
            $externalResult = $callback();
            $externalModel = $externalResult instanceof Model
                ? $externalResult
                : null;

            // Update the action record to reflect that it was successfully applied, including
            // any associated external model information.
            $action->forceFill([
                'status' => ActionStatus::Applied,
                'external_type' => $externalModel?->getMorphClass(),
                'external_id' => $externalModel?->getKey(),
                'applied_at' => now(),
            ])->save();

            // Refresh the action instance to ensure we have the latest data, including
            // any changes made during the save operation.
            $freshAction = $action->fresh();

            // Log the successful enforcement action and fire the ModerationActionApplied event.
            $this->audit->log(
                event: 'enforcement.'.$type->value,
                actor: $actor,
                subject: $target,
                auditable: $freshAction,
                metadata: $metadata,
            );

            // Fire the event to notify listeners that a moderation action has been applied.
            event(new ModerationActionApplied($freshAction));

            // Return the refreshed action instance to the caller.
            return $freshAction;
        } catch (Throwable $exception) {
            $action->forceFill([
                'status' => ActionStatus::Failed,
                'failure_reason' => $exception->getMessage(),
            ])->save();

            // Log the failed enforcement action, including the error message in the metadata.
            $this->audit->log(
                event: 'enforcement.failed',
                actor: $actor,
                subject: $target,
                auditable: $action,
                metadata: [
                    'type' => $type->value,
                    'error' => $exception->getMessage(),
                ],
            );

            // Rethrow the exception to allow higher-level error handling to take place.
            throw $exception;
        }
    }

    /**
     * Convert a date-like value to a stable string for JSON audit metadata.
     */
    private function formatDate(mixed $value): ?string
    {
        // If the value is a DateTimeInterface instance, format it as an ISO 8601 string.
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        // If the value is null, return null; otherwise, cast it to a string.
        return $value === null ? null : (string) $value;
    }
}
