<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel;

use EloquentWorks\Sentinel\Services\AutomationEngine;
use EloquentWorks\Sentinel\Services\BulkModerationService;
use EloquentWorks\Sentinel\Services\CaseManager;
use EloquentWorks\Sentinel\Services\ContentHoldManager;
use EloquentWorks\Sentinel\Services\DashboardService;
use EloquentWorks\Sentinel\Services\EnforcementManager;
use EloquentWorks\Sentinel\Services\MacroRunner;
use EloquentWorks\Sentinel\Services\ReportManager;
use EloquentWorks\Sentinel\Services\RiskScorer;
use EloquentWorks\Sentinel\Services\WatchlistManager;

/**
 * Main entry point for Sentinel's moderation services.
 *
 * The class intentionally exposes focused service objects instead of becoming
 * a large god object. Consumers may resolve the services directly or access
 * them through the Sentinel facade.
 */
final readonly class Sentinel
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public ReportManager $reports,
        public CaseManager $cases,
        public EnforcementManager $enforcement,
        public WatchlistManager $watchlist,
        public ContentHoldManager $holds,
        public AutomationEngine $automation,
        public MacroRunner $macros,
        public BulkModerationService $bulk,
        public RiskScorer $risk,
        public DashboardService $dashboard,
    ) {}

    /** Return the report manager. */
    public function reports(): ReportManager
    {
        return $this->reports;
    }

    /** Return the case manager. */
    public function cases(): CaseManager
    {
        return $this->cases;
    }

    /** Return the enforcement manager. */
    public function enforcement(): EnforcementManager
    {
        return $this->enforcement;
    }

    /** Return the watchlist manager. */
    public function watchlist(): WatchlistManager
    {
        return $this->watchlist;
    }

    /** Return the content hold manager. */
    public function holds(): ContentHoldManager
    {
        return $this->holds;
    }

    /** Return the automation engine. */
    public function automation(): AutomationEngine
    {
        return $this->automation;
    }

    /** Return the moderation macro runner. */
    public function macros(): MacroRunner
    {
        return $this->macros;
    }

    /** Return the bulk moderation service. */
    public function bulk(): BulkModerationService
    {
        return $this->bulk;
    }

    /** Return the risk scorer. */
    public function risk(): RiskScorer
    {
        return $this->risk;
    }

    /** Return the dashboard metrics service. */
    public function dashboard(): DashboardService
    {
        return $this->dashboard;
    }
}
