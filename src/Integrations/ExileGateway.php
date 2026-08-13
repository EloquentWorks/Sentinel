<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Integrations;

use EloquentWorks\Exile\Enums\AppealStatus;
use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Enums\WarningSeverity;
use EloquentWorks\Exile\Facades\Exile;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class ExileGateway
{
    public function warn(Model $target, string $reason, string $severity = 'medium', ?Authenticatable $moderator = null): mixed
    {
        $enum = match ($severity) {
            'low' => WarningSeverity::Low,
            'high' => WarningSeverity::High,
            default => WarningSeverity::Medium,
        };
        return $target->warn(reason: $reason, severity: $enum, moderator: $moderator);
    }

    public function strike(Model $target, string $reason, int $points = 1, string $category = 'other', ?Authenticatable $moderator = null): mixed
    {
        return $target->strike(reason: $reason, points: $points, category: $category, moderator: $moderator);
    }

    public function ban(Model $target, string $reason, mixed $expiresAt = null, ?Authenticatable $moderator = null, string $category = 'other', ?string $internalNotes = null, array $metadata = []): mixed
    {
        return $target->ban(reason: $reason, expiresAt: $expiresAt, moderator: $moderator, category: $category, internalNotes: $internalNotes, metadata: $metadata);
    }

    public function restrict(Model $target, string $type, string $reason, mixed $expiresAt = null, ?Authenticatable $moderator = null): mixed
    {
        $enum = match ($type) {
            'posting' => RestrictionType::Posting,
            'read_only' => RestrictionType::ReadOnly,
            'login' => RestrictionType::Login,
            'shadow' => RestrictionType::Shadow,
            default => throw new \InvalidArgumentException("Unknown Exile restriction [{$type}]."),
        };
        return $target->restrict($enum, reason: $reason, expiresAt: $expiresAt, moderator: $moderator);
    }

    public function banIp(string $ip, string $reason, mixed $expiresAt = null, ?Authenticatable $moderator = null): mixed
    {
        return Exile::banIp($ip, reason: $reason, expiresAt: $expiresAt, moderator: $moderator);
    }

    public function banNetwork(string $cidr, string $reason, mixed $expiresAt = null, ?Authenticatable $moderator = null): mixed
    {
        return Exile::banNetwork($cidr, reason: $reason, expiresAt: $expiresAt, moderator: $moderator);
    }

    public function banDevice(string $fingerprint, string $reason, mixed $expiresAt = null, ?Authenticatable $moderator = null): mixed
    {
        return Exile::banDevice($fingerprint, reason: $reason, expiresAt: $expiresAt, moderator: $moderator);
    }

    public function banAccountAndIp(Model $target, string $ip, string $reason, mixed $expiresAt = null, ?Authenticatable $moderator = null): mixed
    {
        return Exile::banAccountAndIp(account: $target, ipAddress: $ip, reason: $reason, expiresAt: $expiresAt, moderator: $moderator);
    }

    public function revokeBan(Model $ban, ?Authenticatable $moderator = null): mixed { return Exile::revokeBan($ban, $moderator); }
    public function revokeRestriction(Model $restriction, ?Authenticatable $moderator = null): mixed { return Exile::revokeRestriction($restriction, $moderator); }
    public function revokeStrike(Model $strike, ?Authenticatable $moderator = null): mixed { return Exile::revokeStrike($strike, $moderator); }

    public function resolveAppeal(Model $appeal, string $status, Authenticatable $reviewer, ?string $notes = null): mixed
    {
        return Exile::resolveAppeal($appeal, AppealStatus::from($status), $reviewer, $notes);
    }
}
