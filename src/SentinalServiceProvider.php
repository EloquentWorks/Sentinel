<?php

namespace EloquentWorks\Sentinel;

use Illuminate\Support\ServiceProvider;

class SentinelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void Returns nothing.
     */
    public function boot(): void
    {
        // Load the package routes from the specified file.
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Load the package views from the specified directory and assign a namespace for them.
        $this->loadViewsFrom(
            __DIR__.'/../resources/views',
            'sentinel'
        );

        // Load the package migrations from the specified directory.
        $this->loadMigrationsFrom(
            __DIR__.'/../database/migrations'
        );

        // Ensure we are running in the console before publishing migrations, configuration files, and views.
        if ($this->app->runningInConsole()) {
            // Publish the package configuration file to the application's config directory.
            $this->publishes([
                __DIR__.'/../config/sentinel.php' => config_path('sentinel.php'),
            ], 'sentinel-config');

            // Publish the package migrations to the application's migrations directory.
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'sentinel-migrations');

            // Publish the package views to the application's views directory.
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/sentinel'),
            ], 'sentinel-views');
        }
    }

    /**
     * Register any package services.
     *
     * @return void Returns nothing.
     */
    public function register(): void
    {
        // Merge the package configuration with the application's configuration.
        $this->mergeConfigFrom(
            __DIR__.'/../config/sentinel.php',
            'sentinel'
        );
    }
}
