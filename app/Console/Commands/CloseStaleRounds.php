<?php

namespace App\Console\Commands;

use App\Models\InspectionRound;
use Illuminate\Console\Command;

class CloseStaleRounds extends Command
{
    /**
     * Rounds are one-per-building-per-day and never auto-close. Any round left
     * open from a previous day is stale — cancel it so it stops showing as
     * active. Unfinished inspections rebase into the current round on resume, so
     * this only touches the (empty) round record, not inspection data.
     */
    protected $signature = 'inspections:close-stale-rounds';

    protected $description = 'Cancel inspection rounds left open from a previous day';

    public function handle(): int
    {
        $count = InspectionRound::where('status', 'in_progress')
            ->whereDate('round_date', '<', now()->toDateString())
            ->update([
                'status'       => 'cancelled',
                'completed_at' => now(),
                'note'         => 'Auto-closed — left open from a previous day.',
            ]);

        $this->info("Closed {$count} stale round(s).");

        return self::SUCCESS;
    }
}
