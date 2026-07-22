<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Wipes all booking data so previous bookings can be re-entered manually from
 * the old platform. Dry-run by default; only deletes with --force AND a typed
 * confirmation. Runs inside a transaction.
 *
 * Deletes: bookings (+ cascaded caution charges, installments, adjustments,
 * messages), booking-tagged financial transactions, guest photo-ID documents,
 * and booking groups. Leaves maintenance/procurement finances, inspections,
 * properties, units and staff untouched. Enquiries are kept unless --with-enquiries.
 */
class PurgeBookings extends Command
{
    protected $signature = 'bookings:purge {--force : Actually delete (otherwise dry-run)} {--with-enquiries : Also delete booking enquiries}';

    protected $description = 'Clear all booking data (dry-run unless --force)';

    private const BOOKING_MORPH = 'App\\Models\\Booking';

    public function handle(): int
    {
        $counts = [
            'bookings'                    => Booking::withTrashed()->count(),
            'caution_fee_charges'         => DB::table('caution_fee_charges')->count(),
            'booking_installments'        => DB::table('booking_installments')->count(),
            'booking_adjustments'         => DB::table('booking_adjustments')->count(),
            'booking_messages'            => DB::table('booking_messages')->count(),
            'booking_groups'              => DB::table('booking_groups')->count(),
            'financial_transactions (booking)' => DB::table('financial_transactions')->where('reference_type', self::BOOKING_MORPH)->count(),
            'documents (photo IDs)'       => DB::table('documents')->where('documentable_type', self::BOOKING_MORPH)->count(),
        ];

        if ($this->option('with-enquiries')) {
            $counts['booking_enquiries'] = DB::table('booking_enquiries')->count();
        }

        $this->newLine();
        $this->info('The following rows will be deleted:');
        $this->table(['Table', 'Rows'], collect($counts)->map(fn ($c, $t) => [$t, $c])->values());
        $this->newLine();
        $this->warn('Kept: maintenance/procurement finances, inspections, properties, units, staff'
            . ($this->option('with-enquiries') ? '.' : ', booking enquiries.'));

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('Dry run — nothing deleted. Re-run with --force to execute.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('This is IRREVERSIBLE and runs against the live database.');
        if ($this->ask('Type DELETE BOOKINGS to confirm') !== 'DELETE BOOKINGS') {
            $this->info('Aborted. Nothing was deleted.');
            return self::FAILURE;
        }

        // Remove guest photo-ID files from disk before their rows go.
        $files = DB::table('documents')->where('documentable_type', self::BOOKING_MORPH)->pluck('file_path');
        foreach ($files as $path) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }

        DB::transaction(function () {
            DB::table('documents')->where('documentable_type', self::BOOKING_MORPH)->delete();
            DB::table('financial_transactions')->where('reference_type', self::BOOKING_MORPH)->delete();

            if ($this->option('with-enquiries')) {
                DB::table('booking_enquiries')->delete();
            }

            // forceDelete (not soft-delete) so the DB ON DELETE CASCADE fires,
            // clearing caution charges, installments, adjustments and messages;
            // withTrashed also removes any already soft-deleted bookings.
            Booking::withTrashed()->forceDelete();

            DB::table('booking_groups')->delete();
        });

        $this->newLine();
        $this->info('Done. All booking data cleared. You can now enter the old bookings manually.');

        return self::SUCCESS;
    }
}
