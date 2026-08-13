<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Console;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'sentinel:install {--migrate} {--views} {--force}';
    protected $description = 'Install Laravel Sentinel';
    public function handle(): int
    {
        $base=['--provider'=>'EloquentWorks\\Sentinel\\SentinelServiceProvider']; if($this->option('force'))$base['--force']=true;
        $this->call('vendor:publish',$base+['--tag'=>'sentinel-config']); $this->call('vendor:publish',$base+['--tag'=>'sentinel-migrations']);
        if($this->option('views'))$this->call('vendor:publish',$base+['--tag'=>'sentinel-views']);
        if($this->option('migrate'))$this->call('migrate');
        $this->components->info('Sentinel installed. Define the `sentinel.access` Gate/permission before opening the dashboard.');
        return self::SUCCESS;
    }
}
