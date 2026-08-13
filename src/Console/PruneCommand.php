<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Console;

use Illuminate\Console\Command;

final class PruneCommand extends Command
{
    protected $signature='sentinel:prune {--days=}'; protected $description='Prune old Sentinel audit logs and resolved moderation actions';
    public function handle(): int
    { $days=(int)($this->option('days')?:config('sentinel.audit.retention_days',365)); $cutoff=now()->subDays(max(1,$days)); $audit=config('sentinel.models.audit'); $action=config('sentinel.models.action'); $a=$audit::query()->where('created_at','<',$cutoff)->delete(); $b=$action::query()->whereIn('status',['revoked','failed'])->where('updated_at','<',$cutoff)->delete(); $this->components->info("Pruned {$a} audit logs and {$b} actions."); return self::SUCCESS; }
}
