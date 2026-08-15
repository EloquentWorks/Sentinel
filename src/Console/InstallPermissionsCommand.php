<?php

namespace EloquentWorks\Sentinel\Console;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class InstallPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentinel:permissions {--guard=web}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Sentinel permissions using spatie/laravel-permission when installed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if the spatie/laravel-permission package is installed.
        if (! class_exists(Permission::class)) {
            $this->components->error('spatie/laravel-permission is not installed.');

            // Return a failure exit code if the package is not installed.
            return self::FAILURE;
        }

        // Get the guard option from the command line, defaulting to 'web'.
        $guard = (string) $this->option('guard');

        // Create permissions defined in the Sentinel configuration for the specified guard.
        foreach (config('sentinel.permissions', []) as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        // Spatie caches permission lookups, so clear the cache after seeding.
        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        // Output a success message to the console.
        $this->components->info('Sentinel permissions created.');

        // Return a success exit code.
        return self::SUCCESS;
    }
}
