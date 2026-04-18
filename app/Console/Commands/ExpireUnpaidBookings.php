<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid';
    protected $description = 'Cancel bookings that were never paid within the hold window';

    // Minutes a pending booking is held before being released
    const HOLD_MINUTES = 30;

    public function handle(): void
    {
        $cutoff = now()->subMinutes(self::HOLD_MINUTES);

        $expired = Booking::where('payment_status', 'pending')
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired bookings found.');
            return;
        }

        $count = 0;

        foreach ($expired as $booking) {
            $booking->update([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancellation_reason' => 'Automatically cancelled — payment not completed within ' . self::HOLD_MINUTES . ' minutes.',
            ]);

            $count++;

            Log::info('Booking auto-expired', [
                'booking_reference' => $booking->booking_reference,
                'guest_email'       => $booking->guest_email,
                'created_at'        => $booking->created_at,
            ]);
        }

        $this->info("Expired {$count} unpaid booking(s).");
    }
}
