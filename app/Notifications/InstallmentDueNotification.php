<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\BookingInstallment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InstallmentDueNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking, public BookingInstallment $installment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $overdue = $this->installment->due_date->isBefore(now()->startOfDay());
        $amount  = '₦' . number_format((float) $this->installment->amount, 0);

        return [
            'title'   => $overdue ? 'Weekly payment overdue' : 'Weekly payment due',
            'message' => "{$this->booking->guest_name} — week {$this->installment->week_number} ({$amount}) "
                . ($overdue ? 'is overdue.' : 'is due today.'),
            'url'     => route('manage.bookings.show', $this->booking->booking_reference),
            'icon'    => 'finance',
        ];
    }
}
