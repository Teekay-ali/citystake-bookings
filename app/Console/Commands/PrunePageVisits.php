<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrunePageVisits extends Command
{
    /**
     * Delete page-visit records older than the retention window so the table
     * stays lean on shared hosting.
     */
    protected $signature = 'page-visits:prune {--days=90 : Delete visits older than this many days}';

    protected $description = 'Prune old page-visit records';

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = DB::table('page_visits')->where('visited_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} page visit(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
