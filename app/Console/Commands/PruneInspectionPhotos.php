<?php

namespace App\Console\Commands;

use App\Models\InspectionItemResult;
use App\Models\UnitInspection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneInspectionPhotos extends Command
{
    /**
     * Delete inspection photo files no longer referenced by any item result or
     * inspection — orphans left when rows are removed (e.g. FK cascade doesn't
     * clean storage). Only files older than a day are touched, so a photo being
     * uploaded right now is never removed.
     */
    protected $signature = 'inspections:prune-photos';

    protected $description = 'Delete orphaned inspection photo files';

    public function handle(): int
    {
        $referenced = InspectionItemResult::whereNotNull('photos')->pluck('photos')
            ->merge(UnitInspection::whereNotNull('photos')->pluck('photos'))
            ->flatten()->filter()->unique()->flip();

        $disk    = Storage::disk('public');
        $cutoff  = now()->subDay()->timestamp;
        $deleted = 0;

        foreach ($disk->files('inspections') as $file) {
            if ($referenced->has($file)) {
                continue;
            }
            if ($disk->lastModified($file) > $cutoff) {
                continue; // too fresh — may be mid-upload
            }
            $disk->delete($file);
            $deleted++;
        }

        $this->info("Pruned {$deleted} orphaned inspection photo(s).");

        return self::SUCCESS;
    }
}
