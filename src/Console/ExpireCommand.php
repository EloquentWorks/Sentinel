<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Console;

use Illuminate\Console\Command;

final class ExpireCommand extends Command
{
    protected $signature='sentinel:expire'; protected $description='Expire Sentinel watchlist entries and content holds';
    public function handle(): int
    { $watch=config('sentinel.models.watchlist'); $hold=config('sentinel.models.hold'); $w=$watch::query()->where('active',true)->whereNotNull('expires_at')->where('expires_at','<=',now())->update(['active'=>false,'updated_at'=>now()]); $h=$hold::query()->where('active',true)->whereNotNull('expires_at')->where('expires_at','<=',now())->update(['active'=>false,'released_at'=>now(),'updated_at'=>now()]); $this->components->info("Expired {$w} watchlist entries and {$h} content holds."); return self::SUCCESS; }
}
