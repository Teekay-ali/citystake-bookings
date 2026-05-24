<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GuestCheckedInNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Guest Checked In',
            'message' => "{$this->booking->guest_name} has checked in to Unit {$this->booking->unit->unit_number} ({$this->booking->booking_reference}).",
            'url'     => route('manage.bookings.show', $this->booking->id),
            'icon'    => 'booking',
        ];
    }
}
