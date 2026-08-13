<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Services;

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
use Throwable;

final class EnforcementManager
{
    public function __construct(
        private readonly ExileGateway $exile,
        private readonly MasqueradeGateway $masquerade,
        private readonly AuditLogger $audit,
    ) {}

    public function warn(Model $target, Authenticatable $actor, string $reason, string $severity = 'medium', ?ModerationCase $case = null): ModerationAction
    {
        return $this->apply(ActionType::Warn, $target, $actor, $reason, $case, fn () => $this->exile->warn($target, $reason, $severity, $actor), ['severity' => $severity]);
    }

    public function strike(Model $target, Authenticatable $actor, string $reason, int $points = 1, string $category = 'other', ?ModerationCase $case = null): ModerationAction
    {
        return $this->apply(ActionType::Strike, $target, $actor, $reason, $case, fn () => $this->exile->strike($target, $reason, $points, $category, $actor), ['points' => $points, 'category' => $category]);
    }

    public function ban(Model $target, Authenticatable $actor, string $reason, mixed $expiresAt = null, string $category = 'other', ?ModerationCase $case = null, array $metadata = []): ModerationAction
    {
        return $this->apply(ActionType::Ban, $target, $actor, $reason, $case, fn () => $this->exile->ban($target, $reason, $expiresAt, $actor, $category, metadata: $metadata), ['category' => $category, 'expires_at' => $expiresAt?->toISOString() ?? null] + $metadata, $expiresAt);
    }

    public function restrict(Model $target, Authenticatable $actor, string $restriction, string $reason, mixed $expiresAt = null, ?ModerationCase $case = null): ModerationAction
    {
        $type = match ($restriction) {
            'posting' => ActionType::RestrictPosting,
            'read_only' => ActionType::RestrictReadOnly,
            'login' => ActionType::RestrictLogin,
            'shadow' => ActionType::RestrictShadow,
            default => throw new \InvalidArgumentException("Unknown restriction [{$restriction}]."),
        };
        return $this->apply($type, $target, $actor, $reason, $case, fn () => $this->exile->restrict($target, $restriction, $reason, $expiresAt, $actor), ['restriction' => $restriction], $expiresAt);
    }

    public function banIp(string $ip, Authenticatable $actor, string $reason, mixed $expiresAt = null, ?ModerationCase $case = null): ModerationAction
    {
        return $this->applyExternal(ActionType::BanIp, $actor, $reason, $case, fn () => $this->exile->banIp($ip, $reason, $expiresAt, $actor), ['ip_address' => $ip], $expiresAt);
    }

    public function banNetwork(string $cidr, Authenticatable $actor, string $reason, mixed $expiresAt = null, ?ModerationCase $case = null): ModerationAction
    {
        return $this->applyExternal(ActionType::BanNetwork, $actor, $reason, $case, fn () => $this->exile->banNetwork($cidr, $reason, $expiresAt, $actor), ['network' => $cidr], $expiresAt);
    }

    public function banDevice(string $fingerprint, Authenticatable $actor, string $reason, mixed $expiresAt = null, ?ModerationCase $case = null): ModerationAction
    {
        return $this->applyExternal(ActionType::BanDevice, $actor, $reason, $case, fn () => $this->exile->banDevice($fingerprint, $reason, $expiresAt, $actor), ['fingerprint_hash' => hash('sha256', $fingerprint)], $expiresAt);
    }

    public function masquerade(Authenticatable $target, Authenticatable $actor, string $reason, ?ModerationCase $case = null, array $metadata = []): ModerationAction
    {
        $targetModel = $target instanceof Model ? $target : throw new \InvalidArgumentException('Masquerade target must be an Eloquent model.');
        return $this->apply(ActionType::Masquerade, $targetModel, $actor, $reason, $case, fn () => $this->masquerade->start($target, $actor, $reason, ['sentinel_case_uuid' => $case?->uuid] + $metadata), $metadata);
    }

    private function apply(ActionType $type, Model $target, Authenticatable $actor, string $reason, ?ModerationCase $case, callable $callback, array $metadata = [], mixed $expiresAt = null): ModerationAction
    {
        return $this->recordAndExecute($type, $target, $actor, $reason, $case, $callback, $metadata, $expiresAt);
    }

    private function applyExternal(ActionType $type, Authenticatable $actor, string $reason, ?ModerationCase $case, callable $callback, array $metadata = [], mixed $expiresAt = null): ModerationAction
    {
        return $this->recordAndExecute($type, null, $actor, $reason, $case, $callback, $metadata, $expiresAt);
    }

    private function recordAndExecute(ActionType $type, ?Model $target, Authenticatable $actor, string $reason, ?ModerationCase $case, callable $callback, array $metadata, mixed $expiresAt): ModerationAction
    {
        $model = config('sentinel.models.action');
        $actorModel = $actor instanceof Model ? $actor : throw new \InvalidArgumentException('Actor must be an Eloquent model.');
        /** @var ModerationAction $action */
        $action = $model::query()->create([
            'uuid' => (string) Str::uuid(),
            'case_id' => $case?->getKey(),
            'actor_type' => $actorModel->getMorphClass(), 'actor_id' => $actorModel->getKey(),
            'target_type' => $target?->getMorphClass(), 'target_id' => $target?->getKey(),
            'type' => $type, 'status' => ActionStatus::Pending, 'reason' => $reason,
            'source_package' => in_array($type, [ActionType::Masquerade], true) ? 'masquerade' : 'exile',
            'expires_at' => $expiresAt, 'metadata' => $metadata ?: null,
        ]);

        try {
            $external = $callback();
            $externalModel = $external instanceof Model ? $external : null;
            $action->forceFill([
                'status' => ActionStatus::Applied,
                'external_type' => $externalModel?->getMorphClass(),
                'external_id' => $externalModel?->getKey(),
                'applied_at' => now(),
            ])->save();
            $this->audit->log('enforcement.'.$type->value, $actor, $target, $action, metadata: $metadata);
            event(new ModerationActionApplied($action->fresh()));
            return $action->fresh();
        } catch (Throwable $e) {
            $action->forceFill(['status' => ActionStatus::Failed, 'failure_reason' => $e->getMessage()])->save();
            $this->audit->log('enforcement.failed', $actor, $target, $action, metadata: ['type' => $type->value, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
