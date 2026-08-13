<?php

use EloquentWorks\Sentinel\Authorization\GateAuthorizer;
use EloquentWorks\Sentinel\Models\AutomationRule;
use EloquentWorks\Sentinel\Models\CaseAssignment;
use EloquentWorks\Sentinel\Models\CaseNote;
use EloquentWorks\Sentinel\Models\ContentHold;
use EloquentWorks\Sentinel\Models\ModerationAction;
use EloquentWorks\Sentinel\Models\ModerationAuditLog;
use EloquentWorks\Sentinel\Models\ModerationCase;
use EloquentWorks\Sentinel\Models\ModerationMacro;
use EloquentWorks\Sentinel\Models\ModerationReport;
use EloquentWorks\Sentinel\Models\WatchlistEntry;

return [
    'user_model' => env('SENTINEL_USER_MODEL', 'App\\Models\\User'),

    'models' => [
        'report' => ModerationReport::class,
        'case' => ModerationCase::class,
        'note' => CaseNote::class,
        'assignment' => CaseAssignment::class,
        'action' => ModerationAction::class,
        'audit' => ModerationAuditLog::class,
        'watchlist' => WatchlistEntry::class,
        'hold' => ContentHold::class,
        'rule' => AutomationRule::class,
        'macro' => ModerationMacro::class,
    ],

    'tables' => [
        'reports' => 'sentinel_reports',
        'cases' => 'sentinel_cases',
        'case_reports' => 'sentinel_case_reports',
        'notes' => 'sentinel_case_notes',
        'assignments' => 'sentinel_case_assignments',
        'actions' => 'sentinel_actions',
        'audit' => 'sentinel_audit_logs',
        'watchlist' => 'sentinel_watchlist',
        'holds' => 'sentinel_content_holds',
        'rules' => 'sentinel_automation_rules',
        'macros' => 'sentinel_macros',
    ],

    'routes' => [
        'enabled' => env('SENTINEL_ROUTES_ENABLED', true),
        'prefix' => 'sentinel',
        'middleware' => ['web', 'auth', 'sentinel.can:sentinel.access'],
    ],

    'authorization' => [
        'authorizer' => GateAuthorizer::class,
        'access_ability' => 'sentinel.access',
    ],

    'views' => [
        'layout' => 'sentinel::layout',
    ],

    'reports' => [
        'auto_open_case_priority' => 'high',
        'duplicate_window_hours' => 24,
        'max_description_length' => 5000,
    ],

    'cases' => [
        'default_queue' => 'general',
        'sla_hours' => [
            'low' => 168,
            'normal' => 72,
            'high' => 24,
            'urgent' => 8,
            'critical' => 2,
        ],
    ],

    'risk' => [
        'report_weight' => 5,
        'high_priority_report_weight' => 10,
        'open_case_weight' => 15,
        'strike_point_weight' => 8,
        'watchlist_weights' => ['low' => 5, 'medium' => 15, 'high' => 30, 'critical' => 50],
        'max' => 100,
    ],

    'automation' => [
        'enabled' => true,
        'max_rules_per_event' => 50,
    ],

    'audit' => [
        'enabled' => true,
        'capture_ip' => true,
        'capture_user_agent' => true,
        'retention_days' => 365,
    ],

    'integrations' => [
        'exile' => ['enabled' => true],
        'masquerade' => ['enabled' => true, 'block_enforcement_while_masquerading' => true],
    ],

    'permissions' => [
        'sentinel.access',
        'sentinel.reports.view', 'sentinel.reports.create', 'sentinel.reports.manage',
        'sentinel.cases.view', 'sentinel.cases.manage', 'sentinel.cases.assign', 'sentinel.cases.resolve',
        'sentinel.enforcement.warn', 'sentinel.enforcement.strike', 'sentinel.enforcement.ban',
        'sentinel.enforcement.restrict', 'sentinel.enforcement.revoke',
        'sentinel.masquerade', 'sentinel.appeals.review', 'sentinel.audit.view',
        'sentinel.watchlist.manage', 'sentinel.automation.manage', 'sentinel.macros.manage', 'sentinel.bulk',
    ],
];
