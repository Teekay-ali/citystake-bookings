<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneNotifications extends Command
{
    /**
     * Delete read notifications older than the retention window so the
     * notifications table doesn't grow unbounded. Unread notifications are
     * always kept regardless of age.
     */
    protected $signature = 'notifications:prune {--days=30 : Delete read notifications older than this many days}';

    protected $description = 'Prune old read notifications';

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} read notification(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
