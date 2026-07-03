<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\CheckInDueNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemindPendingCheckIns extends Command
{
    protected $signature   = 'bookings:remind-checkins';
    protected $description = 'Notify building staff of confirmed, paid arrivals due today (or overdue) that have not been checked in';

    public function handle(): void
    {
        $bookings = Booking::where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->whereNull('checked_in_at')
            ->whereDate('check_in', '<=', now()->toDateString())
            ->with(['unit', 'building'])
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No pending check-ins.');
            return;
        }

        $notified = 0;
        foreach ($bookings as $booking) {
            // Escalate to the building's receptionists and managers.
            $recipients = NotificationService::getUsersByRoles(
                ['receptionist', 'manager', 'super-admin'],
                $booking->building_id
            );

            if ($recipients->isEmpty()) {
                continue;
            }

            NotificationService::send($recipients, new CheckInDueNotification($booking));
            $notified++;
        }

        Log::info('Pending check-in reminders sent', [
            'bookings'  => $bookings->count(),
            'notified'  => $notified,
        ]);

        $this->info("Sent reminders for {$notified} pending check-in(s).");
    }
}
