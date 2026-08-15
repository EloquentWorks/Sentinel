<?php

namespace EloquentWorks\Sentinel\Console;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentinel:install {--migrate} {--views} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laravel Sentinel';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Publish the Sentinel configuration, migrations, and optionally views.
        $publishOptions = [
            '--provider' => 'EloquentWorks\\Sentinel\\SentinelServiceProvider',
        ];

        // If the --force option is provided, add it to the publish options.
        if ($this->option('force')) {
            $publishOptions['--force'] = true;
        }

        // Publish the Sentinel configuration file.
        $this->call('vendor:publish', $publishOptions + [
            '--tag' => 'sentinel-config',
        ]);

        // Publish the Sentinel migrations.
        $this->call('vendor:publish', $publishOptions + [
            '--tag' => 'sentinel-migrations',
        ]);

        // If the --views option is provided, publish the Sentinel views.
        if ($this->option('views')) {
            $this->call('vendor:publish', $publishOptions + [
                '--tag' => 'sentinel-views',
            ]);
        }

        // If the --migrate option is provided, run the migrations.
        if ($this->option('migrate')) {
            $this->call('migrate');
        }

        // Output a success message and a warning about defining the `sentinel.access` Gate or permission.
        $this->components->info('Sentinel installed successfully.');
        $this->components->warn(
            'Define the `sentinel.access` Gate or permission before exposing the moderation dashboard.'
        );

        // Return a success exit code.
        return self::SUCCESS;
    }
}
