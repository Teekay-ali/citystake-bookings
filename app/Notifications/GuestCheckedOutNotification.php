<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GuestCheckedOutNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $unit = $this->booking->unit?->unit_number;

        return [
            'title'   => 'Guest checked out',
            'message' => "{$this->booking->guest_name}"
                . ($unit ? " (Unit {$unit})" : '')
                . ' has checked out'
                . ($this->booking->caution_fee > 0 && ! $this->booking->caution_fee_refunded
                    ? ' — caution fee still needs settling.'
                    : '.'),
            'url'     => route('manage.bookings.show', $this->booking->booking_reference),
            'icon'    => 'booking',
        ];
    }
}
