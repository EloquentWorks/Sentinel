<?php

namespace EloquentWorks\Sentinel\Integrations;

use EloquentWorks\Exile\Enums\AppealStatus;
use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Enums\WarningSeverity;
use EloquentWorks\Exile\Facades\Exile;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class ExileGateway
{
    /**
     * Issue a warning against a bannable model.
     *
     * @param  Model  $target
     * @param  string  $reason
     * @param  string  $severity
     * @param  Authenticatable|null  $moderator
     * @return mixed
     */
    public function warn(
        Model $target,
        string $reason,
        string $severity = 'medium',
        ?Authenticatable $moderator = null,
    ): mixed {
        // Map the severity string to the corresponding WarningSeverity enum value.
        $warningSeverity = match ($severity) {
            'low' => WarningSeverity::Low,
            'high' => WarningSeverity::High,
            default => WarningSeverity::Medium,
        };

        // Call the warn method on the target model with the provided parameters.
        return $target->warn(
            reason: $reason,
            severity: $warningSeverity,
            moderator: $moderator,
        );
    }

    /**
     * Add strike points to a model.
     *
     * @param  Model  $target
     * @param  string  $reason
     * @param  int  $points
     * @param  string  $category
     * @param  Authenticatable|null  $moderator
     * @return mixed
     */
    public function strike(
        Model $target,
        string $reason,
        int $points = 1,
        string $category = 'other',
        ?Authenticatable $moderator = null,
    ): mixed {
        // Call the strike method on the target model with the provided parameters.
        return $target->strike(
            reason: $reason,
            points: $points,
            category: $category,
            moderator: $moderator,
        );
    }

    /**
     * Ban a model temporarily or permanently.
     *
     * @param  Model  $target
     * @param  string  $reason
     * @param  mixed  $expiresAt
     * @param  Authenticatable|null  $moderator
     * @param  string  $category
     * @param  string|null  $internalNotes
     * @param  array  $metadata
     * @return mixed
     */
    public function ban(
        Model $target,
        string $reason,
        mixed $expiresAt = null,
        ?Authenticatable $moderator = null,
        string $category = 'other',
        ?string $internalNotes = null,
        array $metadata = [],
    ): mixed {
        // Call the ban method on the target model with the provided parameters.
        return $target->ban(
            reason: $reason,
            expiresAt: $expiresAt,
            moderator: $moderator,
            category: $category,
            internalNotes: $internalNotes,
            metadata: $metadata,
        );
    }

    /**
     * Apply a supported Exile restriction to a model.
     *
     * @param  Model  $target
     * @param  string  $type
     * @param  string  $reason
     * @param  mixed  $expiresAt
     * @param  Authenticatable|null  $moderator
     * @return mixed
     */
    public function restrict(
        Model $target,
        string $type,
        string $reason,
        mixed $expiresAt = null,
        ?Authenticatable $moderator = null,
    ): mixed {
        // Map the restriction type string to the corresponding RestrictionType enum value.
        $restrictionType = match ($type) {
            'posting' => RestrictionType::Posting,
            'read_only' => RestrictionType::ReadOnly,
            'login' => RestrictionType::Login,
            'shadow' => RestrictionType::Shadow,
            default => throw new InvalidArgumentException(
                "Unknown Exile restriction [{$type}]."
            ),
        };
        
        // Call the restrict method on the target model with the provided parameters.
        return $target->restrict(
            $restrictionType,
            reason: $reason,
            expiresAt: $expiresAt,
            moderator: $moderator,
        );
    }

    /**
     * Ban an individual IP address.
     *
     * @param  string  $ip
     * @param  string  $reason
     * @param  mixed  $expiresAt
     * @param  Authenticatable|null  $moderator
     * @return mixed
     */
    public function banIp(
        string $ip,
        string $reason,
        mixed $expiresAt = null,
        ?Authenticatable $moderator = null,
    ): mixed {
        // Call the banIp method on the Exile facade with the provided parameters.
        return Exile::banIp(
            $ip,
            reason: $reason,
            expiresAt: $expiresAt,
            moderator: $moderator,
        );
    }

    /**
     * Ban a CIDR network range.
     *
     * @param  string  $cidr
     * @param  string  $reason
     * @param  mixed  $expiresAt
     * @param  Authenticatable|null  $moderator
     * @return mixed
     */
    public function banNetwork(
        string $cidr,
        string $reason,
        mixed $expiresAt = null,
        ?Authenticatable $moderator = null,
    ): mixed {
        // Call the banNetwork method on the Exile facade with the provided parameters.
        return Exile::banNetwork(
            $cidr,
            reason: $reason,
            expiresAt: $expiresAt,
            moderator: $moderator,
        );
    }

    /**
     * Ban a device fingerprint through Exile.
     *
     * @param  string  $fingerprint
     * @param  string  $reason
     * @param  mixed  $expiresAt
     * @param  Authenticatable|null  $moderator
     * @return mixed
     */
    public function banDevice(
        string $fingerprint,
        string $reason,
        mixed $expiresAt = null,
        ?Authenticatable $moderator = null,
    ): mixed {
        // Call the banDevice method on the Exile facade with the provided parameters.
        return Exile::banDevice(
            $fingerprint,
            reason: $reason,
            expiresAt: $expiresAt,
            moderator: $moderator,
        );
    }

    /**
     * Ban an account and IP address in one moderation operation.
     *
     * @param  Model  $target
     * @param  string  $ip
     * @param  string  $reason
     * @param  mixed  $expiresAt
     * @param  Authenticatable|null  $moderator
     * @return mixed
     */
    public function banAccountAndIp(
        Model $target,
        string $ip,
        string $reason,
        mixed $expiresAt = null,
        ?Authenticatable $moderator = null,
    ): mixed {
        // Call the banAccountAndIp method on the Exile facade with the provided parameters.
        return Exile::banAccountAndIp(
            account: $target,
            ipAddress: $ip,
            reason: $reason,
            expiresAt: $expiresAt,
            moderator: $moderator,
        );
    }

    /**
     * Revoke an existing Exile ban.
     *
     * @param  Model  $ban
     * @param  Authenticatable|null  $moderator
     * @return mixed
     */
    public function revokeBan(
        Model $ban,
        ?Authenticatable $moderator = null,
    ): mixed {
        // Call the revokeBan method on the Exile facade with the provided parameters.
        return Exile::revokeBan($ban, $moderator);
    }

    /**
     * Revoke an existing Exile restriction.
     *
     * @param  Model  $restriction
     * @param  Authenticatable|null  $moderator
     * @return mixed
     */
    public function revokeRestriction(
        Model $restriction,
        ?Authenticatable $moderator = null,
    ): mixed {
        // Call the revokeRestriction method on the Exile facade with the provided parameters.
        return Exile::revokeRestriction($restriction, $moderator);
    }

    /**
     * Revoke an existing Exile strike.
     *
     * @param  Model  $strike
     * @param  Authenticatable|null  $moderator
     * @return mixed
     */
    public function revokeStrike(
        Model $strike,
        ?Authenticatable $moderator = null,
    ): mixed {
        // Call the revokeStrike method on the Exile facade with the provided parameters.
        return Exile::revokeStrike($strike, $moderator);
    }

    /**
     * Resolve an Exile appeal using a supported appeal status.
     *
     * @param  Model  $appeal
     * @param  string  $status
     * @param  Authenticatable  $reviewer
     * @param  string|null  $notes
     * @return mixed
     */
    public function resolveAppeal(
        Model $appeal,
        string $status,
        Authenticatable $reviewer,
        ?string $notes = null,
    ): mixed {
        // Map the status string to the corresponding AppealStatus enum value.
        return Exile::resolveAppeal(
            $appeal,
            AppealStatus::from($status),
            $reviewer,
            $notes,
        );
    }
}
