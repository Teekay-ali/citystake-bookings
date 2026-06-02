<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendCheckoutReminders extends Command
{
    protected $signature   = 'bookings:send-checkout-reminders';
    protected $description = 'Notify staff of bookings that have passed checkout date without manual checkout';

    public function handle(): void
    {
        $yesterday = now()->subDay()->toDateString();

        // Bookings that are still checked_in but checkout date has passed
        $overdue = Booking::where('status', 'checked_in')
            ->whereDate('check_out', '<=', $yesterday)
            ->with(['building', 'unitType', 'unit'])
            ->get();

        if ($overdue->isEmpty()) {
            $this->info('No overdue checkouts.');
            return;
        }

        foreach ($overdue as $booking) {
            $recipients = NotificationService::getUsersByRoles(
                ['manager', 'receptionist'],
                $booking->building_id
            );

            Notification::send($recipients, new \App\Notifications\OverdueCheckoutNotification($booking));
        }

        $this->info("Overdue checkout reminders sent for {$overdue->count()} booking(s).");
    }
}
