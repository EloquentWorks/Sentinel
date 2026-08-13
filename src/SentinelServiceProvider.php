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
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class SentinelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sentinel.php','sentinel');
        $this->app->bind(ModeratorAuthorizer::class, fn($app) => $app->make(config('sentinel.authorization.authorizer')));
        $this->app->singleton(Sentinel::class, fn($app) => new Sentinel(
            $app->make(Services\ReportManager::class), $app->make(Services\CaseManager::class), $app->make(Services\EnforcementManager::class),
            $app->make(Services\WatchlistManager::class), $app->make(Services\ContentHoldManager::class), $app->make(Services\AutomationEngine::class),
            $app->make(Services\MacroRunner::class), $app->make(Services\BulkModerationService::class), $app->make(Services\RiskScorer::class), $app->make(Services\DashboardService::class),
        ));
    }

    public function boot(Router $router): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views','sentinel');
        $router->aliasMiddleware('sentinel.can', EnsureSentinelPermission::class);
        $router->aliasMiddleware('sentinel.not-masquerading', BlockEnforcementWhileMasquerading::class);
        if (config('sentinel.routes.enabled', true)) {
            Route::middleware(config('sentinel.routes.middleware'))->prefix(config('sentinel.routes.prefix','sentinel'))->as('sentinel.')->group(__DIR__.'/../routes/web.php');
        }
        Event::listen(ReportCreated::class, function (ReportCreated $event): void {
            app(AutomationEngine::class)->handle('report.created', ['report'=>$event->report,'subject'=>$event->report->subject,'reportable'=>$event->report->reportable,'actor'=>$event->report->reporter]);
        });
        foreach ([
            'EloquentWorks\\Exile\\Events\\BanIssued','EloquentWorks\\Exile\\Events\\BanRevoked','EloquentWorks\\Exile\\Events\\StrikeIssued','EloquentWorks\\Exile\\Events\\WarningIssued','EloquentWorks\\Exile\\Events\\AppealSubmitted','EloquentWorks\\Exile\\Events\\AppealResolved',
            'EloquentWorks\\Masquerade\\Events\\MasqueradeStarted','EloquentWorks\\Masquerade\\Events\\MasqueradeStopped','EloquentWorks\\Masquerade\\Events\\MasqueradeDenied','EloquentWorks\\Masquerade\\Events\\MasqueradeExpired',
        ] as $eventClass) Event::listen($eventClass, [ExternalModerationEventRecorder::class,'handle']);

        $this->publishes([__DIR__.'/../config/sentinel.php'=>config_path('sentinel.php')],'sentinel-config');
        $this->publishes([__DIR__.'/../database/migrations'=>database_path('migrations')],'sentinel-migrations');
        $this->publishes([__DIR__.'/../resources/views'=>resource_path('views/vendor/sentinel')],'sentinel-views');
        if ($this->app->runningInConsole()) $this->commands([InstallCommand::class,InstallPermissionsCommand::class,PruneCommand::class,ExpireCommand::class]);
    }
}
