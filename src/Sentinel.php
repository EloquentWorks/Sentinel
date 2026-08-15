<?php

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

final readonly class Sentinel
{
    /**
     * Create a new class instance.
     *
     * @return void
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
    ) {
        //
    }

    /**
     * Return the report manager.
     */
    public function reports(): ReportManager
    {
        // Return the report manager instance.
        return $this->reports;
    }

    /**
     * Return the case manager.
     */
    public function cases(): CaseManager
    {
        // Return the case manager instance.
        return $this->cases;
    }

    /**
     * Return the enforcement manager.
     */
    public function enforcement(): EnforcementManager
    {
        // Return the enforcement manager instance.
        return $this->enforcement;
    }

    /**
     * Return the watchlist manager.
     */
    public function watchlist(): WatchlistManager
    {
        // Return the watchlist manager instance.
        return $this->watchlist;
    }

    /**
     * Return the content hold manager.
     */
    public function holds(): ContentHoldManager
    {
        // Return the content hold manager instance.
        return $this->holds;
    }

    /**
     * Return the automation engine.
     */
    public function automation(): AutomationEngine
    {
        // Return the automation engine instance.
        return $this->automation;
    }

    /**
     * Return the moderation macro runner.
     */
    public function macros(): MacroRunner
    {
        // Return the moderation macro runner instance.
        return $this->macros;
    }

    /**
     * Return the bulk moderation service.
     */
    public function bulk(): BulkModerationService
    {
        // Return the bulk moderation service instance.
        return $this->bulk;
    }

    /**
     * Return the risk scorer.
     */
    public function risk(): RiskScorer
    {
        // Return the risk scorer instance.
        return $this->risk;
    }

    /**
     * Return the dashboard metrics service.
     */
    public function dashboard(): DashboardService
    {
        // Return the dashboard metrics service instance.
        return $this->dashboard;
    }
}
