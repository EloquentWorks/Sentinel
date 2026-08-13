<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Tests;

use EloquentWorks\Sentinel\SentinelServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array { return [SentinelServiceProvider::class]; }
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default','testing');
        $app['config']->set('database.connections.testing',['driver'=>'sqlite','database'=>':memory:','prefix'=>'']);
        $app['config']->set('sentinel.routes.enabled',false);
        $app['config']->set('sentinel.audit.capture_ip',false);
        $app['config']->set('sentinel.audit.capture_user_agent',false);
    }
    protected function defineDatabaseMigrations(): void { $this->loadMigrationsFrom(__DIR__.'/../database/migrations'); }
}
