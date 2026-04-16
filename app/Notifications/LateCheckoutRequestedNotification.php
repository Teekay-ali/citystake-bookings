<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LateCheckoutRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Late Checkout Requested',
            'message' => "Guest {$this->booking->guest_name} (Unit {$this->booking->unit->unit_number}) has requested a late checkout",
            'url'     => route('manage.bookings.show', $this->booking->id),
            'icon'    => 'late_checkout',
        ];
    }
}
