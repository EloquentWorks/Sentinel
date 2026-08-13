<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Enums;

enum ActionType: string
{
    case Warn = 'warn';
    case Strike = 'strike';
    case Ban = 'ban';
    case BanIp = 'ban_ip';
    case BanNetwork = 'ban_network';
    case BanDevice = 'ban_device';
    case BanAccountAndIp = 'ban_account_and_ip';
    case RestrictPosting = 'restrict_posting';
    case RestrictReadOnly = 'restrict_read_only';
    case RestrictLogin = 'restrict_login';
    case RestrictShadow = 'restrict_shadow';
    case Revoke = 'revoke';
    case Masquerade = 'masquerade';
    case HoldContent = 'hold_content';
    case ReleaseContent = 'release_content';
    case Note = 'note';
}
