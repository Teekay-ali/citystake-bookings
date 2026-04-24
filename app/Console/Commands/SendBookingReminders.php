<?php

namespace App\Console\Commands;

use App\Mail\BookingReminder;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingReminders extends Command
{
    protected $signature   = 'bookings:send-reminders';
    protected $description = 'Send check-in reminder emails to guests checking in tomorrow';

    public function handle(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        $bookings = Booking::where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->whereDate('check_in', $tomorrow)
            ->with(['building', 'unitType', 'unit'])
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No check-ins tomorrow.');
            return;
        }

        $sent  = 0;
        $failed = 0;

        foreach ($bookings as $booking) {
            try {
                Mail::to($booking->guest_email)->send(new BookingReminder($booking));
                $sent++;
                Log::info('Check-in reminder sent', [
                    'booking_reference' => $booking->booking_reference,
                    'guest_email'       => $booking->guest_email,
                    'check_in'          => $booking->check_in,
                ]);
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to send check-in reminder', [
                    'booking_reference' => $booking->booking_reference,
                    'error'             => $e->getMessage(),
                ]);
            }
        }

        $this->info("Reminders sent: {$sent}. Failed: {$failed}.");
    }
}
