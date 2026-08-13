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

final readonly class Sentinel
{
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

    public function reports(): ReportManager { return $this->reports; }
    public function cases(): CaseManager { return $this->cases; }
    public function enforcement(): EnforcementManager { return $this->enforcement; }
    public function watchlist(): WatchlistManager { return $this->watchlist; }
    public function holds(): ContentHoldManager { return $this->holds; }
    public function automation(): AutomationEngine { return $this->automation; }
    public function macros(): MacroRunner { return $this->macros; }
    public function bulk(): BulkModerationService { return $this->bulk; }
    public function risk(): RiskScorer { return $this->risk; }
    public function dashboard(): DashboardService { return $this->dashboard; }
}

