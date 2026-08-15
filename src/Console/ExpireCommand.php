<?php

namespace EloquentWorks\Sentinel\Console;

use Illuminate\Console\Command;

final class ExpireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentinel:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire Sentinel watchlist entries and content holds';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get the configured models for watchlist entries and content holds.
        $watchlistModel = config('sentinel.models.watchlist');
        $contentHoldModel = config('sentinel.models.hold');

        // Disable watchlist entries whose configured expiration has passed.
        $expiredWatchlistEntries = $watchlistModel::query()
            ->where('active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'active' => false,
                'updated_at' => now(),
            ]);

        // Release expired content holds while retaining their audit history.
        $expiredContentHolds = $contentHoldModel::query()
            ->where('active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'active' => false,
                'released_at' => now(),
                'updated_at' => now(),
            ]);

        // Output the results to the console.
        $this->components->info(
            "Expired {$expiredWatchlistEntries} watchlist entries and {$expiredContentHolds} content holds."
        );

        // Return a success exit code.
        return self::SUCCESS;
    }
}
