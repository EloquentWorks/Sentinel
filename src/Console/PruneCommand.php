<?php

namespace EloquentWorks\Sentinel\Console;

use Illuminate\Console\Command;

final class PruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentinel:prune {--days=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old Sentinel audit logs and resolved moderation actions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Determine the number of days to retain audit logs and actions, defaulting to the configuration value or 365 days.
        $days = (int) ($this->option('days') ?: config('sentinel.audit.retention_days', 365));
        $cutoff = now()->subDays(max(1, $days));

        // Get the configured models for audit logs and actions.
        $auditModel = config('sentinel.models.audit');
        $actionModel = config('sentinel.models.action');

        // Delete audit logs older than the cutoff date and actions that are revoked or failed and older than the cutoff date.
        $auditLogs = $auditModel::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        // Delete actions that are revoked or failed and older than the cutoff date.
        $actions = $actionModel::query()
            ->whereIn('status', ['revoked', 'failed'])
            ->where('updated_at', '<', $cutoff)
            ->delete();

        // Output the results to the console.
        $this->components->info("Pruned {$auditLogs} audit logs and {$actions} actions.");

        // Return a success exit code.
        return self::SUCCESS;
    }
}
