<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel;

use EloquentWorks\Sentinel\Console\ExpireCommand;
use EloquentWorks\Sentinel\Console\InstallCommand;
use EloquentWorks\Sentinel\Console\InstallPermissionsCommand;
use EloquentWorks\Sentinel\Console\PruneCommand;
use EloquentWorks\Sentinel\Contracts\ModeratorAuthorizer;
use EloquentWorks\Sentinel\Events\ReportCreated;
use EloquentWorks\Sentinel\Http\Middleware\BlockEnforcementWhileMasquerading;
use EloquentWorks\Sentinel\Http\Middleware\EnsureSentinelPermission;
use EloquentWorks\Sentinel\Listeners\ExternalModerationEventRecorder;
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
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Register and bootstrap the Sentinel moderation package.
 */
final class SentinelServiceProvider extends ServiceProvider
{
    /**
     * Register package bindings and merge default configuration.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/sentinel.php',
            'sentinel',
        );

        $this->app->bind(
            ModeratorAuthorizer::class,
            fn ($app) => $app->make(
                config('sentinel.authorization.authorizer')
            ),
        );

        $this->app->singleton(
            Sentinel::class,
            fn ($app) => new Sentinel(
                reports: $app->make(ReportManager::class),
                cases: $app->make(CaseManager::class),
                enforcement: $app->make(EnforcementManager::class),
                watchlist: $app->make(WatchlistManager::class),
                holds: $app->make(ContentHoldManager::class),
                automation: $app->make(AutomationEngine::class),
                macros: $app->make(MacroRunner::class),
                bulk: $app->make(BulkModerationService::class),
                risk: $app->make(RiskScorer::class),
                dashboard: $app->make(DashboardService::class),
            ),
        );
    }

    /**
     * Bootstrap views, routes, events, middleware, publishing, and commands.
     */
    public function boot(Router $router): void
    {
        $this->registerViews();
        $this->registerMiddleware($router);
        $this->registerRoutes();
        $this->registerEventListeners();
        $this->registerPublishing();
        $this->registerCommands();
    }

    /**
     * Register package Blade views.
     */
    private function registerViews(): void
    {
        $this->loadViewsFrom(
            __DIR__.'/../resources/views',
            'sentinel',
        );
    }

    /**
     * Register Sentinel route middleware aliases.
     */
    private function registerMiddleware(Router $router): void
    {
        $router->aliasMiddleware(
            'sentinel.can',
            EnsureSentinelPermission::class,
        );

        $router->aliasMiddleware(
            'sentinel.not-masquerading',
            BlockEnforcementWhileMasquerading::class,
        );
    }

    /**
     * Register the optional built-in moderation routes.
     */
    private function registerRoutes(): void
    {
        if (! config('sentinel.routes.enabled', true)) {
            return;
        }

        Route::middleware(config('sentinel.routes.middleware', ['web', 'auth']))
            ->prefix(config('sentinel.routes.prefix', 'sentinel'))
            ->as('sentinel.')
            ->group(__DIR__.'/../routes/web.php');
    }

    /**
     * Register Sentinel and external package event listeners.
     */
    private function registerEventListeners(): void
    {
        Event::listen(ReportCreated::class, function (ReportCreated $event): void {
            app(AutomationEngine::class)->handle('report.created', [
                'report' => $event->report,
                'subject' => $event->report->subject,
                'reportable' => $event->report->reportable,
                'actor' => $event->report->reporter,
            ]);
        });

        foreach ($this->externalModerationEvents() as $eventClass) {
            Event::listen(
                $eventClass,
                [ExternalModerationEventRecorder::class, 'handle'],
            );
        }
    }

    /**
     * Return package events that should be mirrored into Sentinel's audit log.
     *
     * @return array<int, string>
     */
    private function externalModerationEvents(): array
    {
        return [
            'EloquentWorks\\Exile\\Events\\BanIssued',
            'EloquentWorks\\Exile\\Events\\BanRevoked',
            'EloquentWorks\\Exile\\Events\\BanExpired',
            'EloquentWorks\\Exile\\Events\\RestrictionIssued',
            'EloquentWorks\\Exile\\Events\\RestrictionRevoked',
            'EloquentWorks\\Exile\\Events\\StrikeIssued',
            'EloquentWorks\\Exile\\Events\\WarningIssued',
            'EloquentWorks\\Exile\\Events\\AppealSubmitted',
            'EloquentWorks\\Exile\\Events\\AppealResolved',
            'EloquentWorks\\Masquerade\\Events\\MasqueradeStarted',
            'EloquentWorks\\Masquerade\\Events\\MasqueradeEnded',
            'EloquentWorks\\Masquerade\\Events\\MasqueradeDenied',
            'EloquentWorks\\Masquerade\\Events\\MasqueradeExpired',
            'EloquentWorks\\Masquerade\\Events\\MasqueradeExtended',
        ];
    }

    /**
     * Register publishable package resources.
     */
    private function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/sentinel.php' => config_path('sentinel.php'),
        ], 'sentinel-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'sentinel-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sentinel'),
        ], 'sentinel-views');
    }

    /**
     * Register Sentinel Artisan commands for console applications.
     */
    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            InstallPermissionsCommand::class,
            PruneCommand::class,
            ExpireCommand::class,
        ]);
    }
}
