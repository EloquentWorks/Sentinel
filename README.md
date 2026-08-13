# 🛡️ Laravel Sentinel

A comprehensive moderation and administration suite for Laravel, built to orchestrate **Eloquent Works Exile** and **Eloquent Works Masquerade** instead of duplicating their enforcement and impersonation engines.

Sentinel adds the staff-facing layer: reports, investigation cases, assignments, internal notes, queues, priorities, SLAs, risk scoring, watchlists, content holds, macros, automation rules, bulk moderation, audit history, dashboards, and a built-in moderation UI.

## Why Sentinel + Exile + Masquerade?

- **Exile** remains the source of truth for account/IP/network/device bans, warnings, strikes, restrictions, appeals, evidence, and revocation.
- **Masquerade** remains the source of truth for secure user impersonation and its session audit trail.
- **Sentinel** connects those systems to cases, reports, queues, staff workflows, risk, automation, and admin tooling.

## Install

```bash
composer require eloquent-works/sentinel
php artisan sentinel:install --migrate
```

If you use Spatie Permission:

```bash
composer require spatie/laravel-permission
php artisan sentinel:permissions
```

Then assign permissions such as `sentinel.access`, `sentinel.enforcement.ban`, and `sentinel.masquerade` to the correct staff roles.

Your user model should use Exile and Masquerade as their packages document, and may add Sentinel's convenience trait:

```php
use EloquentWorks\Exile\Traits\Bannable;
use EloquentWorks\Masquerade\Traits\HasMasquerade;
use EloquentWorks\Sentinel\Traits\HasSentinelModeration;

class User extends Authenticatable
{
    use Bannable;
    use HasMasquerade;
    use HasSentinelModeration;
}
```

## Feature map

### Intake and triage
- Polymorphic reports for users, games, chat messages, profiles, forum posts, or any Eloquent model
- Reporter + reportable + subject relationships
- Categories, reason, description, source, IP, user-agent and metadata
- Priority and status workflows
- Duplicate linking
- Open an investigation case directly from a report

### Cases
- UUID case IDs
- Open / investigating / waiting / escalated / resolved / closed states
- Low / normal / high / urgent / critical priority
- Queues and configurable SLA deadlines
- Assign/reassign moderators
- Internal notes
- Tags and metadata
- Link multiple reports to one case
- Action history
- Resolution and escalation

### Exile enforcement orchestration

```php
use EloquentWorks\Sentinel\Facades\Sentinel;

Sentinel::enforcement()->warn($user, $moderator, 'Unsporting chat');
Sentinel::enforcement()->strike($user, $moderator, 'Cheating', 3, 'cheating');
Sentinel::enforcement()->ban($user, $moderator, 'Repeated cheating', now()->addDays(30), 'cheating');
Sentinel::enforcement()->restrict($user, $moderator, 'posting', 'Chat cooldown', now()->addHours(24));
Sentinel::enforcement()->banIp('203.0.113.4', $moderator, 'Ban evasion');
Sentinel::enforcement()->banNetwork('203.0.113.0/24', $moderator, 'Abusive network');
Sentinel::enforcement()->banDevice($fingerprint, $moderator, 'Ban evasion');
```

Every action is mirrored into `sentinel_actions` with the Sentinel case, actor, target, reason, status, Exile record reference, timestamps and metadata.

### Masquerade support

```php
Sentinel::enforcement()->masquerade(
    target: $user,
    actor: $supportAgent,
    reason: 'Investigating support ticket CHESS-1042',
    case: $case,
);
```

Sentinel adds the case UUID into Masquerade metadata. Built-in enforcement routes are blocked while a Masquerade session is active so an impersonated account cannot accidentally perform moderator enforcement actions.

### Risk scoring

```php
$score = app(EloquentWorks\Sentinel\Services\RiskScorer::class)->score($user);
```

Risk can include open reports, high-priority reports, open cases, Exile strike points, and Sentinel watchlist severity.

### Watchlists

```php
Sentinel::watchlist()->add(
    subject: $user,
    actor: $moderator,
    reason: 'Multiple suspicious accounts',
    severity: 'high',
    expiresAt: now()->addDays(30),
);
```

### Content holds

Use `Reportable` on content models and quarantine/review content without deleting it:

```php
$hold = Sentinel::holds()->hold($chatMessage, $moderator, 'Awaiting review');
```

### Moderation macros

A macro stores a reusable list of staff actions:

```json
[
  {"type":"warn","reason":"Tournament conduct warning","severity":"high"},
  {"type":"strike","reason":"Tournament conduct violation","points":2,"category":"abuse"},
  {"type":"note","body":"Tournament conduct macro applied."}
]
```

### Automation rules

Rules subscribe to an event such as `report.created`, match JSON conditions, then execute actions.

Example conditions:

```json
[
  {"field":"report.category","operator":"equals","value":"cheating"},
  {"field":"report.priority.value","operator":"in","value":["high","urgent","critical"]}
]
```

Example actions:

```json
[
  {"type":"open_case","title":"High-risk cheating report","priority":"urgent","queue":"anti-cheat","tags":["cheating"]},
  {"type":"watch","reason":"High-risk cheating report","severity":"high"}
]
```

Supported rule operators: `equals`, `not_equals`, `gt`, `gte`, `lt`, `lte`, `in`, `contains`, `present`.

### Bulk moderation

```php
$result = app(EloquentWorks\Sentinel\Services\BulkModerationService::class)
    ->strike($users, $moderator, 'Coordinated abuse', 2, 'abuse');
```

Each target is processed independently and failures are returned without discarding successful actions.

## Dashboard

Sentinel ships an authenticated moderation dashboard at `/sentinel` with reports, cases, moderation profiles and enforcement forms. Access is guarded by `sentinel.access` through Laravel Gate, which works naturally with `spatie/laravel-permission`.

Use your app layout / Bootswatch theme:

```php
// config/sentinel.php
'views' => [
    'layout' => 'layouts.app',
],
```

Your layout needs `@yield('content')`.

## Permissions

`php artisan sentinel:permissions` creates:

```text
sentinel.access
sentinel.reports.view
sentinel.reports.create
sentinel.reports.manage
sentinel.cases.view
sentinel.cases.manage
sentinel.cases.assign
sentinel.cases.resolve
sentinel.enforcement.warn
sentinel.enforcement.strike
sentinel.enforcement.ban
sentinel.enforcement.restrict
sentinel.enforcement.revoke
sentinel.masquerade
sentinel.appeals.review
sentinel.audit.view
sentinel.watchlist.manage
sentinel.automation.manage
sentinel.macros.manage
sentinel.bulk
```

## Commands

```bash
php artisan sentinel:install --migrate --views
php artisan sentinel:permissions
php artisan sentinel:expire
php artisan sentinel:prune
php artisan sentinel:prune --days=730
```

Suggested schedule:

```php
Schedule::command('sentinel:expire')->hourly();
Schedule::command('sentinel:prune')->daily();
```

## Security decisions

- Enforcement remains in Exile instead of reimplementing ban logic.
- Impersonation remains in Masquerade instead of changing authentication directly.
- Sentinel logs who performed each staff action and can cross-reference external records.
- Built-in enforcement routes are blocked during active masquerading.
- Admin routes require an explicit Gate/permission.
- Device fingerprints are not stored raw in Sentinel action metadata.
- Sensitive moderation reasons and notes belong in your protected staff interface, not public profile responses.

## Existing repo fixes included

The old repository had two naming inconsistencies: `eloquent-works/laravel-sentinal` and `SentinalServiceProvider.php`, while Composer attempted to load `SentinelServiceProvider`. This build standardizes them to `eloquent-works/sentinel` and `src/SentinelServiceProvider.php`.

## Requirements

- PHP 8.2+
- Laravel 12 or 13
- `eloquent-works/exile ^1.0`
- `eloquent-works/masquerade ^1.1`

## License

MIT
