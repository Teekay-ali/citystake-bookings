<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\InstallmentDueNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemindDueInstallments extends Command
{
    protected $signature   = 'bookings:remind-installments';
    protected $description = 'Notify staff of weekly-plan bookings whose current week is due or overdue and unpaid';

    public function handle(): void
    {
        // Active weekly bookings with an unpaid installment already due (its week has started)
        $bookings = Booking::where('payment_plan', 'weekly')
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->with(['building', 'installments' => fn ($q) => $q->whereNull('paid_at')->whereDate('due_date', '<=', now()->toDateString())->orderBy('week_number')])
            ->get()
            ->filter(fn ($b) => $b->installments->isNotEmpty());

        if ($bookings->isEmpty()) {
            $this->info('No due weekly payments.');
            return;
        }

        $notified = 0;
        foreach ($bookings as $booking) {
            $installment = $booking->installments->first();
            $recipients  = NotificationService::getUsersByRoles(['receptionist', 'manager', 'super-admin'], $booking->building_id);
            if ($recipients->isEmpty()) continue;

            NotificationService::send($recipients, new InstallmentDueNotification($booking, $installment));
            $notified++;
        }

        Log::info('Weekly installment reminders sent', ['bookings' => $bookings->count(), 'notified' => $notified]);
        $this->info("Sent reminders for {$notified} due weekly payment(s).");
    }
}
