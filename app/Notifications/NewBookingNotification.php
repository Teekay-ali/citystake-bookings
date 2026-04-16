<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewBookingNotification extends Notification
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
            'title'   => 'New Booking',
            'message' => "{$this->booking->guest_name} booked {$this->booking->unitType->name} at {$this->booking->building->name}",
            'url'     => route('manage.bookings.show', $this->booking->id),
            'icon'    => 'booking',
        ];
    }
}
