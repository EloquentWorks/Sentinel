<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Console;

use Illuminate\Console\Command;

final class InstallPermissionsCommand extends Command
{
    protected $signature = 'sentinel:permissions {--guard=web}';
    protected $description = 'Create Sentinel permissions using spatie/laravel-permission when installed';
    public function handle(): int
    {
        if (! class_exists(\Spatie\Permission\Models\Permission::class)) { $this->components->error('spatie/laravel-permission is not installed.'); return self::FAILURE; }
        $guard=(string)$this->option('guard'); $class=\Spatie\Permission\Models\Permission::class;
        foreach(config('sentinel.permissions',[]) as $permission) $class::findOrCreate($permission,$guard);
        if(class_exists(\Spatie\Permission\PermissionRegistrar::class)) app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->components->info('Sentinel permissions created.'); return self::SUCCESS;
    }
}
